<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\ContactMethodType;
use App\Model\Table\MemberContactMethodsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MemberContactMethodsTable Test Case
 */
class MemberContactMethodsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MemberContactMethodsTable
     */
    protected $MemberContactMethods;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Members',
        'app.MemberContactMethods',
        'app.Groups',
        'app.Teams',
        'app.EmailGroups',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('MemberContactMethods') ? [] : ['className' => MemberContactMethodsTable::class];
        $this->MemberContactMethods = $this->getTableLocator()->get('MemberContactMethods', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->MemberContactMethods);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\MemberContactMethodsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $contact = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'new@example.com',
            'contact_method_type' => '1',
        ]);
        $this->assertEmpty($contact->getErrors());
        $this->assertSame(ContactMethodType::Email, $contact->contact_method_type);

        $invalid = $this->MemberContactMethods->newEntity([
            'member_id' => 'invalid',
            'contact_method' => '',
            'contact_method_type' => 999,
        ]);
        $this->assertArrayHasKey('member_id', $invalid->getErrors());
        $this->assertArrayHasKey('contact_method', $invalid->getErrors());
        $this->assertArrayHasKey('contact_method_type', $invalid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\MemberContactMethodsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $duplicate = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'ada@example.com',
            'contact_method_type' => 1,
        ]);
        $this->assertFalse($this->MemberContactMethods->save($duplicate));

        $missingMember = $this->MemberContactMethods->newEntity([
            'member_id' => '99999999-9999-4999-8999-999999999999',
            'contact_method' => 'missing@example.com',
            'contact_method_type' => 1,
        ]);
        $this->assertFalse($this->MemberContactMethods->save($missingMember));
        $this->assertArrayHasKey('member_id', $missingMember->getErrors());
    }

    public function testEmailGroupMustExistWhenProvided(): void
    {
        $contact = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'group@example.com',
            'contact_method_type' => ContactMethodType::EmailGroup->value,
            'email_group_id' => '99999999-9999-4999-8999-999999999999',
        ]);

        $this->assertFalse($this->MemberContactMethods->save($contact));
        $this->assertArrayHasKey('email_group_id', $contact->getErrors());
    }

    public function testOnlyEmailGroupContactMethodsCanReferenceEmailGroups(): void
    {
        $emailAlias = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'alias@district.example.org',
            'contact_method_type' => ContactMethodType::EmailAlias->value,
            'email_group_id' => '66666666-6666-4666-8666-666666666661',
        ]);

        $this->assertFalse($this->MemberContactMethods->save($emailAlias));
        $this->assertArrayHasKey('email_group_id', $emailAlias->getErrors());

        $emailGroup = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'digital-leaders@district.example.org',
            'contact_method_type' => ContactMethodType::EmailGroup->value,
            'email_group_id' => '66666666-6666-4666-8666-666666666661',
        ]);

        $this->assertNotFalse($this->MemberContactMethods->save($emailGroup));
    }

    public function testPhoneNumbersAreNormalizedAndInvalidFormatsAreRejected(): void
    {
        foreach (
            [
            '07804918252',
            '07804 918252',
            '+44 7804 918252',
            '+447804918252',
            '+44 (0)7804-918-252',
            '0044 7804 918252',
            ] as $phoneNumber
        ) {
            $contact = $this->MemberContactMethods->newEntity([
                'member_id' => '33333333-3333-4333-8333-333333333331',
                'contact_method' => $phoneNumber,
                'contact_method_type' => ContactMethodType::PhoneNumber->value,
            ]);

            $this->assertEmpty($contact->getErrors());
            $this->assertSame('+44 7804 918252', $contact->contact_method);
        }

        foreach (['020 7946 0958', '+44 20 7946 0958'] as $phoneNumber) {
            $contact = $this->MemberContactMethods->newEntity([
                'member_id' => '33333333-3333-4333-8333-333333333331',
                'contact_method' => $phoneNumber,
                'contact_method_type' => ContactMethodType::PhoneNumber->value,
            ]);

            $this->assertEmpty($contact->getErrors());
            $this->assertSame('+44 2079460958', $contact->contact_method);
        }

        $contact = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => '+33 1 42 68 53 00',
            'contact_method_type' => ContactMethodType::PhoneNumber->value,
        ]);

        $this->assertArrayHasKey('phoneNumberFormat', $contact->getErrors()['contact_method']);
    }

    public function testEmailContactMethodsAreLowercased(): void
    {
        foreach (
            [
            ContactMethodType::Email,
            ContactMethodType::EmailAlias,
            ContactMethodType::EmailGroup,
            ] as $contactMethodType
        ) {
            $contact = $this->MemberContactMethods->newEntity([
                'member_id' => '33333333-3333-4333-8333-333333333331',
                'contact_method' => 'Team.Lead+Alias@EXAMPLE.ORG',
                'contact_method_type' => $contactMethodType->value,
            ]);

            $this->assertEmpty($contact->getErrors());
            $this->assertSame('team.lead+alias@example.org', $contact->contact_method);
        }
    }

    public function testNonGroupEmailFlagIsCalculatedAndPersistedOnSave(): void
    {
        $groupEmail = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'leader@GROUP.EXAMPLE.ORG',
            'contact_method_type' => ContactMethodType::Email->value,
        ]);
        $this->MemberContactMethods->saveOrFail($groupEmail);
        $this->assertFalse($groupEmail->is_non_group_email);

        $personalEmail = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'leader@example.com',
            'contact_method_type' => ContactMethodType::EmailAlias->value,
        ]);
        $this->MemberContactMethods->saveOrFail($personalEmail);
        $this->assertTrue($personalEmail->is_non_group_email);
        $this->assertTrue($this->MemberContactMethods->get($personalEmail->id)->is_non_group_email);

        $phone = $this->MemberContactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333332',
            'contact_method' => '07804 918252',
            'contact_method_type' => ContactMethodType::PhoneNumber->value,
        ]);
        $this->MemberContactMethods->saveOrFail($phone);
        $this->assertFalse($phone->is_non_group_email);
    }
}
