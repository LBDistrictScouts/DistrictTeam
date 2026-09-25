<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RestrictEmailGroupReferencesToEmailGroupContactMethods extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('member_contact_methods')
            ->addCheckConstraint(
                'email_group_id IS NULL OR contact_method_type = 3',
                ['name' => 'member_contact_methods_email_group_type_check'],
            )
            ->update();
    }
}
