<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Enum\ContactMethodType;
use ArrayObject;
use Cake\Database\Type\EnumType;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * MemberContactMethods Model
 *
 * @property \Cake\ORM\Association\BelongsTo<\App\Model\Table\MembersTable> $Members
 * @method \App\Model\Entity\MemberContactMethod newEmptyEntity()
 * @method \App\Model\Entity\MemberContactMethod newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\MemberContactMethod> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MemberContactMethod get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MemberContactMethod findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MemberContactMethod patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\MemberContactMethod> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MemberContactMethod|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MemberContactMethod saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\MemberContactMethod>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MemberContactMethod>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MemberContactMethod>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MemberContactMethod> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MemberContactMethod>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MemberContactMethod>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\MemberContactMethod>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MemberContactMethod> deleteManyOrFail(iterable $entities, array $options = [])
 */
class MemberContactMethodsTable extends Table
{
    /**
     * Cached configured group email domains.
     *
     * @var list<string>|null
     */
    private ?array $cachedGroupDomains = null;

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('member_contact_methods');
        $this->setDisplayField('contact_method');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType(
            'contact_method_type',
            EnumType::from(ContactMethodType::class),
        );

        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Normalize an accepted UK phone number for storage.
     *
     * @param string $phoneNumber Phone number supplied by a user or import.
     * @return string|null Normalized number, or null when its format is invalid.
     */
    public static function normalizePhoneNumber(string $phoneNumber): ?string
    {
        $phoneNumber = trim($phoneNumber);
        // CSV exports use inconsistent spacing and may omit the space after +44.
        // Allow presentation-only punctuation, then validate the underlying number.
        if (!preg_match('/^[+()\-\s\d]+$/D', $phoneNumber)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phoneNumber);
        if ($digits === null) {
            return null;
        }
        if (str_starts_with($digits, '0044')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '44')) {
            $digits = substr($digits, 2);
            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            $digits = '0' . $digits;
        }

        if (!preg_match('/^0[1-9]\d{8,9}$/D', $digits)) {
            return null;
        }

        if (preg_match('/^0(7\d{3})(\d{6})$/D', $digits, $matches)) {
            return '+44 ' . $matches[1] . ' ' . $matches[2];
        }

        return '+44 ' . substr($digits, 1);
    }

    /**
     * Whether a contact type is stored as a lowercase email value.
     *
     * @param mixed $contactMethodType Contact method type value.
     * @return bool
     */
    private static function isEmailType(mixed $contactMethodType): bool
    {
        if ($contactMethodType instanceof ContactMethodType) {
            $contactMethodType = $contactMethodType->value;
        }

        return in_array((int)$contactMethodType, [
            ContactMethodType::Email->value,
            ContactMethodType::EmailAlias->value,
            ContactMethodType::EmailGroup->value,
        ], true);
    }

    /**
     * Normalize phone numbers before validation and persistence.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \ArrayObject<string, mixed> $data Request data.
     * @param \ArrayObject<string, mixed> $options Marshal options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (!is_string($data['contact_method'] ?? null)) {
            return;
        }

        $contactMethodType = $data['contact_method_type'] ?? null;
        if ((int)$contactMethodType === ContactMethodType::PhoneNumber->value) {
            $normalized = self::normalizePhoneNumber($data['contact_method']);
            if ($normalized !== null) {
                $data['contact_method'] = $normalized;
            }
        } elseif (self::isEmailType($contactMethodType)) {
            $data['contact_method'] = strtolower($data['contact_method']);
        }
    }

    /**
     * Mark email contact methods whose domain is not configured by any group.
     *
     * The result is stored so appointment views can highlight it without
     * recalculating the organisation's configured domains for every request.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\Datasource\EntityInterface $entity Contact method being saved.
     * @param \ArrayObject<string, mixed> $options Save options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $contactMethod = $entity->get('contact_method');
        if (!self::isEmailType($entity->get('contact_method_type')) || !is_string($contactMethod)) {
            $entity->set('is_non_group_email', false);

            return;
        }

        $isNonGroupEmail = !in_array(
            $this->emailDomain($contactMethod),
            $this->groupDomains(),
            true,
        );
        $entity->set('is_non_group_email', $isNonGroupEmail);
    }

    /**
     * Recalculate email availability after group domains have been synchronized.
     *
     * @return int Number of contact methods whose stored flag changed.
     */
    public function refreshNonGroupEmailFlags(): int
    {
        $this->cachedGroupDomains = null;
        $groupDomains = $this->groupDomains();
        $updated = 0;
        $contactMethods = $this->find()->select([
            'id',
            'contact_method',
            'contact_method_type',
            'is_non_group_email',
        ]);
        foreach ($contactMethods as $contactMethod) {
            if (!$contactMethod instanceof EntityInterface) {
                continue;
            }
            $value = $contactMethod->get('contact_method');
            $isNonGroupEmail = self::isEmailType($contactMethod->get('contact_method_type'))
                && is_string($value)
                && !in_array($this->emailDomain($value), $groupDomains, true);
            if ($contactMethod->get('is_non_group_email') === $isNonGroupEmail) {
                continue;
            }
            $this->updateAll(['is_non_group_email' => $isNonGroupEmail], ['id' => $contactMethod->get('id')]);
            $updated++;
        }

        return $updated;
    }

    /**
     * @return list<string> Lowercase email domains configured by groups.
     */
    private function groupDomains(): array
    {
        if ($this->cachedGroupDomains !== null) {
            return $this->cachedGroupDomains;
        }
        $groupDomains = [];
        foreach (TableRegistry::getTableLocator()->get('Groups')->find()->select(['domains']) as $group) {
            foreach ($group->domains ?? [] as $groupDomain) {
                if (is_string($groupDomain)) {
                    $groupDomains[] = strtolower($groupDomain);
                }
            }
        }

        return $this->cachedGroupDomains = array_values(array_unique($groupDomains));
    }

    /**
     * @param string $email Email address.
     * @return string Lowercase domain component.
     */
    private function emailDomain(string $email): string
    {
        return ltrim(strtolower((string)strrchr($email, '@')), '@');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('member_id')
            ->notEmptyString('member_id');

        $validator
            ->scalar('contact_method')
            ->maxLength('contact_method', 255)
            ->requirePresence('contact_method', 'create')
            ->notEmptyString('contact_method');

        $validator->add('contact_method', 'phoneNumberFormat', [
            'rule' => function (mixed $value, array $context): bool {
                if ((int)($context['data']['contact_method_type'] ?? 0) !== ContactMethodType::PhoneNumber->value) {
                    return true;
                }

                return is_string($value) && self::normalizePhoneNumber($value) !== null;
            },
            'message' => __('Enter a UK phone number, for example 020 7946 0958 or +44 7804 918252.'),
        ]);

        $validator
            ->enum('contact_method_type', ContactMethodType::class)
            ->notEmptyString('contact_method_type');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(
            $rules->isUnique(['member_id', 'contact_method']),
            [
                'errorField' => 'member_id',
                'message' => __('This combination of member_id and contact_method already exists'),
            ],
        );
        $rules->add($rules->existsIn(['member_id'], 'Members'), ['errorField' => 'member_id']);

        return $rules;
    }
}
