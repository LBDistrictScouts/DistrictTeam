<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/** Completed source files imported into the directory. */
class ImportFilesTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('import_files');
        $this->setPrimaryKey('id');
        $this->hasMany('ImportRecords', ['foreignKey' => 'import_file_id', 'dependent' => true]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->scalar('filename')->maxLength('filename', 255)
            ->requirePresence('filename', 'create')->notEmptyString('filename');
        $validator->dateTime('imported_at')->requirePresence('imported_at', 'create')
            ->notEmptyDateTime('imported_at');
        $countFields = [
            'source_record_count', 'record_count', 'member_count', 'contact_count',
            'appointment_count', 'warning_count',
        ];
        foreach ($countFields as $field) {
            $validator->nonNegativeInteger($field)->requirePresence($field, 'create');
        }

        return $validator;
    }
}
