<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNonGroupEmailFlagToMemberContactMethods extends BaseMigration
{
    /**
     * Persist whether a contact email belongs to one of the configured group domains.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('member_contact_methods')
            ->addColumn('is_non_group_email', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->update();

        $this->execute(
            "UPDATE member_contact_methods
            SET is_non_group_email = contact_method_type IN (1, 2, 3)
                AND NOT EXISTS (
                    SELECT 1
                    FROM groups,
                    jsonb_array_elements_text(groups.domains::jsonb) AS configured_domain(domain)
                    WHERE lower(configured_domain.domain) = lower(
                        split_part(member_contact_methods.contact_method, '@', 2)
                    )
                )",
        );
    }
}
