# JSON REST API

The application exposes its stored data through a read-only JSON API under `/api`. No authentication layer currently exists, so write operations are deliberately not exposed.

Send `Accept: application/json`. The `.json` suffix is also supported.

## Endpoints

| Resource | Collection | Individual record |
| --- | --- | --- |
| Teams | `GET /api/teams` | `GET /api/teams/{uuid}` |
| Roles | `GET /api/roles` | `GET /api/roles/{uuid}` |
| Members | `GET /api/members` | `GET /api/members/{uuid}` |
| Contact methods | `GET /api/member-contact-methods` | `GET /api/member-contact-methods/{uuid}` |
| Appointments | `GET /api/appointments` | `GET /api/appointments/{uuid}` |

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
