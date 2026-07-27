<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Member;
use App\Model\Entity\Role;
use App\Model\Entity\Team;
use App\Model\Enum\ContactMethodType;
use Cake\TestSuite\TestCase;

/**
 * Entity accessor and enum tests.
 */
class EntityTest extends TestCase
{
    public function testMemberFullName(): void
    {
        $member = new Member([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);

        $this->assertSame('Ada Lovelace', $member->full_name);
        $this->assertSame('Ada Lovelace', $member->toArray()['full_name']);
    }

    public function testRoleSlugIsGenerated(): void
    {
        $role = new Role([
            'name' => 'Research & Development',
        ]);

        $this->assertSame('research-and-development', $role->slug);
    }

    public function testTeamSlugIsGenerated(): void
    {
        $team = new Team([
            'team_name' => 'People & Culture',
        ]);

        $this->assertSame('people-and-culture', $team->slug);
    }

    public function testContactMethodTypeValuesAndLabels(): void
    {
        $this->assertSame(1, ContactMethodType::Email->value);
        $this->assertSame('Email', ContactMethodType::Email->label());
        $this->assertSame('Email Alias', ContactMethodType::EmailAlias->label());
        $this->assertSame('Email Group', ContactMethodType::EmailGroup->label());
        $this->assertSame('Phone Number', ContactMethodType::PhoneNumber->label());
    }
}
