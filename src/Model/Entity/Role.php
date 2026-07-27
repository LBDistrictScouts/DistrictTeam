<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Role Entity
 *
 * @property string $id
 * @property string $team_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $currently_filled
 *
 * @property \App\Model\Entity\Team $team
 */
class Role extends Entity
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
        'team_id' => true,
        'name' => true,
        'slug' => false,
        'description' => true,
        'currently_filled' => true,
        'team' => true,
    ];

    /**
     * Set the role name and generate its URL-encoded slug.
     *
     * @param string $name Role name.
     * @return string
     */
    protected function _setName(string $name): string
    {
        $this->slug = $name
                |> strtolower(...)
                |> (fn($x) => str_replace(' ', '-', $x))
                |> (fn($x) => str_replace('&', 'and', $x))
                |> urlencode(...)
                |> (fn($x) => str_replace('---', '-', $x));

        return $name;
    }
}
