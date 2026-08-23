<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Team Entity
 *
 * @property string $id
 * @property string $team_name
 * @property string|null $slug
 * @property string|null $team_parent_id
 *
 * @property int|null $tree_left
 * @property int|null $tree_right
 * @property int|null $tree_level
 *
 * @property \App\Model\Entity\Team $parent_team
 * @property array<\App\Model\Entity\Team> $sub_teams
 * @property array<\App\Model\Entity\Role> $roles
 * @property \App\Model\Entity\Role|null $team_lead
 */
class Team extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'team_name' => true,
        'slug' => false,
        'team_parent_id' => true,
        'team_parent' => true,
    ];

    /**
     * Set the team name and generate its URL-encoded slug.
     *
     * @param string $teamName Team name.
     * @return string
     */
    protected function _setTeamName(string $teamName): string
    {
        $this->slug = $teamName
                |> strtolower(...)
                |> (fn($x) => str_replace(' ', '-', $x))
                |> (fn($x) => str_replace('&', 'and', $x))
                |> urlencode(...)
                |> (fn($x) => str_replace('---', '-', $x));

        return $teamName;
    }
}
