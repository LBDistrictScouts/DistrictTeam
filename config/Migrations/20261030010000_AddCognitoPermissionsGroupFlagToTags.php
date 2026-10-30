<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCognitoPermissionsGroupFlagToTags extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('tags')
            ->addColumn('is_cognito_permissions_group', 'boolean', ['default' => false, 'null' => false])
            ->update();
    }
}
