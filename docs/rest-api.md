# Xataface HTTP/REST API — CRUD From Outside the UI

Xataface is not just a web UI: every controller in Xataface is an **action** that
can be driven directly over HTTP. This means you can **create, read, update and
delete** records from an external program (a script, a mobile app, another
server, `curl`, Postman, etc.) instead of using the Xataface screens.

You drive the same `new`, `edit` and `delete` controllers the UI uses, but add
`-response=json` so they return JSON instead of an HTML page, and read with
`export_json`. This is exactly how the official
[Codename One client library (`cn1-xataface`)](https://github.com/shannah/cn1-xataface)
(`com.xataface.query.XFClient`) talks to a Xataface backend.

> **TL;DR**
> * Read → `-action=export_json`
> * Create → `-action=new` + `-response=json`
> * Update → `-action=edit` + `-response=json`
> * Delete → `-action=delete` + `-response=json`

All endpoints are served by the application's front controller — usually
`index.php` at the root of your app — and are selected with the `-action`
query‑string parameter.

---

## 1. Authentication

The API is **not a separate, unauthenticated gateway** — it runs inside your
normal Xataface application and obeys the same login and permission rules as the
browser UI. A request that isn't authenticated acts as the *anonymous* user and
will only be allowed to do what an anonymous visitor can do.

> This section mirrors the **"API Authentication"** section of the
> [Xataface Definitive Guide, Security chapter](https://shannah.github.io/xataface-manual/#api-authentication).
> See the Guide for the canonical write‑up.

**Step 1** — `POST` your credentials to the `login` action with `--no-prompt=1`.
On success it returns a JSON body containing a **token**:

```bash
curl \
  -d "-action=login" \
  -d "UserName=myuser" \
  -d "Password=mypassword" \
  -d "--no-prompt=1" \
  https://yourapp.example.com/index.php
```

```json
{ "code": 200, "token": "576646...2dDRuOGUycGI=", "message": "Logged in" }
```

A failure returns a non‑200 `code` with a message, e.g.
`{"code":500,"message":"No UserName provided."}`.

**Step 2** — include that token in the `Authorization` header of every
subsequent request as a bearer token:

```bash
curl -H "Authorization: Bearer 576646...2dDRuOGUycGI=" \
  "https://yourapp.example.com/index.php?-table=contacts&-action=export_json"
```

Xataface parses the `Authorization: Bearer <token>` header
(`Dataface/Application.php`, `getBearerToken()`).

To log out, call `?-action=logout&--no-prompt=1`.

> **The examples throughout this document use bearer‑token auth.** They assume
> you captured the login token into a shell variable, e.g.
> `TOKEN=576646...2dDRuOGUycGI=`, and then pass `-H "Authorization: Bearer
> $TOKEN"` on each request.

> **Tip:** The authenticated user must have the relevant table/field
> **permissions** (see §6). If a `create` call fails with a "Permission denied"
> message, the user simply isn't granted the `new` permission on that table.

---

## 2. Read records — `export_json`

`export_json` returns a JSON array of records selected using the standard
[Xataface URL conventions](https://www.xataface.com/wiki/URL_Conventions)
(filters, sorting, paging, etc.). It is a `GET` request.

```bash
# All records in the "contacts" table
curl -H "Authorization: Bearer $TOKEN" \
  "https://yourapp.example.com/index.php?-table=contacts&-action=export_json"
```

```json
[
  {"contact_id":"1","first_name":"John","last_name":"Doe","email":"john@example.com"},
  {"contact_id":"2","first_name":"Jane","last_name":"Roe","email":"jane@example.com"}
]
```

Useful parameters:

| Parameter | Description |
|-----------|-------------|
| `-table` | Table to read from (required). |
| `--fields` | Space‑separated list of columns to include (default: all native/grafted fields). |
| Any column name | Acts as a filter, e.g. `&last_name=Doe`. Prefix the value with `=` for an exact match: `&last_name==Doe`. |
| `-sort` | Sort order, e.g. `-sort=last_name asc`. |
| `-skip` / `-limit` | Paging (offset / page size). |
| `--displayMethod` | `val` (raw, default), `display` (human‑readable, resolves vocabularies), or `htmlValue`. |
| `--single` | Return a single object instead of a one‑element array. |
| `-mode=browse&-recordid=<id>` | Return just one record by its Xataface record ID (see §7). |

Example — fetch one record by ID, only two fields, human‑readable values:

```bash
curl -H "Authorization: Bearer $TOKEN" \
  "https://yourapp.example.com/index.php?-table=contacts&-action=export_json&-mode=browse&-recordid=contacts%3Fcontact_id%3D1&--fields=first_name%20last_name&--displayMethod=display&--single=1"
```

> **Permissions:** a field is only included if the user has both the `view` and
> `export_json` permissions on it.

---

## 3. Create a record — `new` action in JSON mode

`POST` to `-action=new` with `-response=json`, the target `-table`, and one
parameter per column. This drives the same new‑record form controller as the UI,
so all validation and business rules in your delegate class are respected.

```bash
curl -H "Authorization: Bearer $TOKEN" \
  -d "-action=new" \
  -d "-table=contacts" \
  -d "-response=json" \
  --data-urlencode "first_name=John" \
  --data-urlencode "last_name=Doe" \
  --data-urlencode "email=john@example.com" \
  https://yourapp.example.com/index.php
```

Success response:

```json
{
  "response_code": 200,
  "record_data": { "contact_id": "42", "first_name": "John", "last_name": "Doe", "email": "john@example.com", "__id__": "contacts?contact_id=42", "__title__": "John Doe" },
  "response_message": "Record Successfully Saved"
}
```

`record_data` contains the saved values, including any generated primary key and
the record's `__id__` (see §7). On a validation or save error the action returns
a non‑200 `response_code` with a descriptive message.

---

## 4. Update a record — `edit` action in JSON mode

`POST` to `-action=edit` with `-response=json`, identify the record with
`--recordid` (see §7 for the ID format), and send the columns you want to
change.

```bash
curl -H "Authorization: Bearer $TOKEN" \
  -d "-action=edit" \
  -d "-table=contacts" \
  -d "-response=json" \
  --data-urlencode "--recordid=contacts?contact_id=42" \
  --data-urlencode "email=john.doe@example.com" \
  https://yourapp.example.com/index.php
```

Success response (same shape as create):

```json
{
  "response_code": 200,
  "record_data": { "contact_id": "42", "first_name": "John", "last_name": "Doe", "email": "john.doe@example.com" },
  "response_message": "Record Successfully Saved"
}
```

Optional: pass `-fields=col1 col2` (space‑separated) to restrict the edit form
to specific columns.

---

## 5. Delete a record — `delete` action in JSON mode

`POST` to `-action=delete` with `-response=json`, and identify the record by its
primary key value(s) using `--__keys__[<pk_column>]=<value>`:

```bash
curl -H "Authorization: Bearer $TOKEN" \
  -d "-action=delete" \
  -d "-table=contacts" \
  -d "-response=json" \
  --data-urlencode "--__keys__[contact_id]=42" \
  https://yourapp.example.com/index.php
```

```json
{ "code": 200, "message": "Record successfully deleted" }
```

A foreign‑key constraint failure returns `code: 500` with an explanatory
message.

---

## 6. Permissions & security

The API enforces the **same permission model** as the UI, defined by the
`getPermissions()` method of your table/application delegate class (or
`permissions.ini.php`). For each operation the relevant permission must be
granted to the authenticated user/role:

| Operation | Required permission(s) |
|-----------|------------------------|
| Read (`export_json`) | `view` + `export_json` (per field) |
| Create (`new`) | `new` (table and per field) |
| Update (`edit`) | `edit` (table and per field) |
| Delete (`delete`) | `delete` |

There is **nothing to "turn on"** to enable the API — these actions ship with
Xataface. Enabling API access for a client is purely a matter of giving that
client's user account the appropriate permissions. Conversely, if you want to
*prevent* API writes, restrict those permissions.

---

## 7. Xataface record ID format

The `edit` action and single‑record reads identify a record by its **Xataface
record ID**, which encodes the table and primary key like a query string:

```
tablename?key1=value1&key2=value2
```

For a `contacts` table with primary key `contact_id = 42` the record ID is:

```
contacts?contact_id=42
```

When placing a record ID in a URL, URL‑encode it
(`contacts%3Fcontact_id%3D42`). When sending it as a POST field with
`curl --data-urlencode`, use the raw form and let curl encode it. In JSON
responses this value is returned as `__id__`.

---

## 8. Reference implementation: the Codename One client

The [`cn1-xataface`](https://github.com/shannah/cn1-xataface) library
(`com.xataface.query.XFClient`) is a complete, working client that performs full
CRUD against a Xataface backend from a Codename One mobile app, using exactly the
endpoints described above:

| Operation | XFClient call | HTTP request |
|-----------|---------------|--------------|
| Log in | `login()` | `POST -action=login` + `UserName`, `Password`, `--no-prompt=1` |
| Create | `save()` (new record) | `POST -action=new` + `-response=json` + field values |
| Read/query | `find()` | `GET` standard find query + `-response=json` (returns `results` + `metaData`) |
| Update | `save()` (existing record) | `POST -action=edit` + `--recordid` + `-response=json` + field values |
| Delete | `delete()` | `POST -action=delete` + `-response=json` |

A minimal usage example lives in the
[`cn1-mysql-demo`](https://github.com/shannah/cn1-mysql-demo) project
(`MySQLContactsDemo.java`). If you are building against the API, that client is
the best end‑to‑end example to read.

---

## Quick reference

| Task | Method | Action | Key parameters |
|------|--------|--------|----------------|
| Log in | POST | `login` | `UserName`, `Password`, `--no-prompt=1` |
| Read many | GET | `export_json` | `-table`, filters, `--fields`, `-sort`, `-skip`, `-limit` |
| Read one | GET | `export_json` | `-table`, `-mode=browse`, `-recordid`, `--single=1` |
| Create | POST | `new` | `-table`, `-response=json`, column values |
| Update | POST | `edit` | `-table`, `-response=json`, `--recordid`, column values |
| Delete | POST | `delete` | `-table`, `-response=json`, `--__keys__[pk]=value` |
