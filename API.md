# JSON REST API

The application exposes its stored data through a read-only JSON API under `/api`. No authentication layer currently exists, so write operations are deliberately not exposed.

Send `Accept: application/json`. The `.json` suffix is also supported.

## District teams only

`GET /api/teams` returns only teams whose group has `type: "district"` in
DistrictCoreData. Filtering happens before sorting and pagination. Teams linked
to district-owned sections are included. Groups classified as `"group"`, teams
without a group, and groups not yet refreshed with classification data are excluded.

`GET /api/teams/{uuid}` returns 404 for excluded teams. Embedded `sub_teams` and
`parent_team` use the same classification filter; a parent outside the district
is returned as null. Excluded child teams' lead roles are not merged into the
team's role list. Existing group/section IDs and `sort_order` remain in responses.

Classification comes entirely from imported core data, with no configured UUID,
name matching, or sort-order heuristic. All groups marked `"district"` qualify.
Changing a group's type on the next sync changes which teams the API includes.
The admin team pages and other API resources retain their existing scope.

Embedded group objects include `type` as the lowercase string `"group"` or
`"district"`, and `domains` as an array of hostnames. Shared UUIDs are unchanged.

## Shared group and section UUIDs

Use each team's **`group_id` and `section_id`** to join with DistrictCoreData or
DistrictBadges. These are the original shared UUIDs imported from core data.
`team.id` identifies the local team; it is independent of the group/section IDs.

The embedded `group.id` and `section.id` repeat those same shared UUIDs, and
`section.group_id` identifies the section's group. Numeric `group_osm_id` and
`section_osm_id` are separate OSM identifiers, not the shared keys.

```json
{
  "id": "11111111-1111-4111-8111-111111111112",
  "group_id": "48d34b95-7058-5bbf-a3ec-a543309f6c52",
  "section_id": "cccccccc-cccc-4ccc-8ccc-cccccccccccc",
  "team_name": "Section Leadership Team",
  "sort_order": 2,
  "group": {
    "id": "48d34b95-7058-5bbf-a3ec-a543309f6c52",
    "group_name": "Letchworth, Baldock & Ashwell",
    "type": "district",
    "domains": ["lbdscouts.org.uk", "lba-scouts.org.uk"]
  },
  "section": {
    "id": "cccccccc-cccc-4ccc-8ccc-cccccccccccc",
    "group_id": "48d34b95-7058-5bbf-a3ec-a543309f6c52",
    "section_name": "District Explorers",
    "section_osm_id": 123
  }
}
```

This abbreviated example also applies to embedded `parent_team`, `sub_teams`,
and the `team` returned by role endpoints: each includes its own group/section
IDs and related objects. District and group teams can have a `group_id` with
`section_id: null` and `section: null`.

## Endpoints

| Resource | Collection | Individual record |
| --- | --- | --- |
| Teams | `GET /api/teams` | `GET /api/teams/{uuid}` |
| Teams for a group | `GET /api/group-teams/{groupUUID}` | — |
| Roles | `GET /api/roles` | `GET /api/roles/{uuid}` |
| Roles for a group | `GET /api/group-roles/{groupUUID}` | — |
| Members | `GET /api/members` | `GET /api/members/{uuid}` |
| Contact methods | `GET /api/member-contact-methods` | `GET /api/member-contact-methods/{uuid}` |
| Appointments | `GET /api/appointments` | `GET /api/appointments/{uuid}` |

`GET /api/group-teams/{groupUUID}` takes the group's shared core-data UUID and
returns the same `data` array and `pagination` envelope as `/api/teams`. It includes
both group-level teams and section teams belonging to that group, ordered by
`sort_order` ascending with UUID as the tiebreaker. Team fields, `group`, `section`,
`team_lead`, `parent_team`, and `sub_teams` match the district collection structure;
the full `roles` list is not included in collection responses.

Parent and child team objects are limited to the requested group. An out-of-group
parent is null; out-of-group children are omitted. `page` and `limit` work as on
`/api/teams`. A known group without teams returns an empty `data` array; invalid or
unknown group UUIDs return 404. Either group type (`group` or `district`) can be
requested. The endpoint is read-only and accepts `.json` or `Accept: application/json`.

Example: `GET /api/group-teams/48d34b95-7058-5bbf-a3ec-a543309f6c52.json?limit=20&page=1`.

`GET /api/group-roles/{groupUUID}` takes the same group UUID and returns the
same `data` array and `pagination` envelope as `/api/roles`. It includes roles
whose team belongs to the requested group, ordered as `/api/roles` does. A known
group without roles returns an empty `data` array; invalid or unknown group UUIDs
return 404. The endpoint is read-only and accepts `.json` or `Accept: application/json`.

Example: `GET /api/group-roles/48d34b95-7058-5bbf-a3ec-a543309f6c52.json?limit=20&page=1`.

Collection responses use this envelope:

```json
{
  "data": [],
  "pagination": {
    "page": 1,
    "page_count": 1,
    "per_page": 20,
    "total": 0
  }
}
```

Individual responses contain the record in `data`:

```json
{
  "data": {
    "id": "33333333-3333-4333-8333-333333333331",
    "first_name": "Ada",
    "last_name": "Lovelace"
  }
}
```

Use `?page=2&limit=10` to paginate a collection. CakePHP caps `limit` at 100 by default. Related records are embedded where useful: teams include their parent and children; roles include their team and current active appointment; members include contact methods; contact methods include their member; and appointments include their role, member, and contact method. A role's `current_appointment` also embeds its member and contact method, and is `null` when no appointment is active and effective today.

Unknown UUIDs and unregistered write routes return `404 Not Found`.

Team collection and detail responses include required `group_id` and nullable `section_id`,
and the related `group` and `section` objects. A group-only team has no section;
a section team belongs to the section's group. These links are independent of
`team_parent_id` and are managed through the team add/edit forms.

Team collections are ordered by `sort_order` ascending, with UUID as a stable
tiebreaker. Embedded `sub_teams` use the same ordering. Team records expose the
integer `sort_order` in collection and detail responses, including embedded
`parent_team` and `sub_teams` records:

```json
{
  "id": "11111111-1111-4111-8111-111111111111",
  "team_name": "District Team",
  "sort_order": 1,
  "sub_teams": [
    {
      "id": "11111111-1111-4111-8111-111111111112",
      "team_name": "Digital Team",
      "sort_order": 2
    }
  ]
}
```

These values come from **Teams → Reorder teams**. Ordering is saved among
siblings; the collection is a flat list sorted numerically, while `sub_teams`
provides the parent/child grouping. Sort values need not be consecutive or unique.

Internal tree coordinates (`tree_left`, `tree_right`, `tree_level`) are omitted
from every serialized team, including nested teams. Use `team_parent_id`,
`parent_team`, `sub_teams`, and `sort_order` for structure and display ordering.
