# CakePHP Application Skeleton

![Build Status](https://github.com/cakephp/app/actions/workflows/ci.yml/badge.svg?branch=5.x)
[![Total Downloads](https://img.shields.io/packagist/dt/cakephp/app.svg?style=flat-square)](https://packagist.org/packages/cakephp/app)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

A skeleton for creating applications with [CakePHP](https://cakephp.org) 5.x.

The framework source code can be found here: [cakephp/cakephp](https://github.com/cakephp/cakephp).

## Installation

1. Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
2. Run `php composer.phar create-project --prefer-dist cakephp/app [app_name]`.

If Composer is installed globally, run

```bash
composer create-project --prefer-dist cakephp/app
```

In case you want to use a custom app dir name (e.g. `/myapp/`):

```bash
composer create-project --prefer-dist cakephp/app myapp
```

You can now either use your machine's webserver to view the default home page, or start
up the built-in webserver with:

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the welcome page.

## Demo app

Check out the [5.x-demo branch](https://github.com/cakephp/app/tree/5.x-demo), which contains demo migrations and a seeder.
See the [README](https://github.com/cakephp/app/blob/5.x-demo/README.md) on how to get it running.

## Update

Since this skeleton is a starting point for your application and various files
would have been modified as per your needs, there isn't a way to provide
automated upgrades, so you have to do any updates manually.

## Configuration

Read and edit the environment specific `config/app_local.php` and set up the
`'Datasources'` and any other configuration relevant for your application.
Other environment agnostic settings can be changed in `config/app.php`.

## Layout

The app skeleton uses [Milligram](https://milligram.io/) (v1.3) minimalist CSS
framework by default. You can, however, replace it with any other library or
custom styles.

### Importing members

Open **Members → Upload CSV** (`/members/upload`) and select a UTF-8 export
like either CSV in `config/Examples/` (up to 10 MB). Column order does not matter.
Only Membership number, First name, Last name and Start date are required.
Other columns can be omitted; extra columns are ignored. Include Communication email
or Contact number for new members needing appointments; existing members can reuse
an existing email/phone contact. Missing source-context columns appear blank in the
mapping screen and may require new mappings, since their source combinations differ.
Uploading shows a mapping screen without changing app records. Each distinct CSV
unit, parent team, team, role and role type combination has a destination selector
showing existing **team / role** combinations. Select a destination or explicitly
leave roles unmapped to import members and contacts only, or explicitly choose
**Skip appointment** to remember that decision. Skipped rows
still import member and contact details. No teams or roles are created.

Select the units to import using the unit checkboxes. Unselected units are excluded
on the server, including their members, contacts, appointments and mapping changes.
Click **Import selected units** to create members, contacts and mapped appointments
in one transaction. Invalid rows report their row number and roll back the import;
you can correct mappings and retry. Pending uploads are stored in your session,
replaced by a new upload, and cleared after successful import.

Members match by numeric membership number (leading zeroes are ignored by the
existing integer schema). A nonblank Preferred name takes precedence over First name when creating or updating
a member; blank or omitted Preferred name falls back to First name.
Matching membership numbers update first and last names; other existing member details
are preserved. If a member appears with different names in one file, the last row wins. A new member's join date is the earliest Start date in the file. Dates
use `02 Dec 2024`. Contacts are reused by member and contact value; email is
preferred for appointments. Appointments match by member, mapped role and start
date; repeat imports update their contact and any supplied end date.
Start date becomes `effective_start_date`; End date becomes `effective_end_date`.
An omitted End date column preserves existing dates, whereas a present but blank
End date clears the date. New appointments without End date are open-ended.
A missing Start date column is rejected with instructions to include it; dates are
never inferred from relative day counts. Invalid dates and end-before-start fail. Mapping several source
combinations to the same role can therefore combine matching appointments.
Imported appointments are enabled; their effective dates determine whether they
are current. End dates never mark members as having left. Imports do not remove
records absent from the file or change role leadership flags. Role status, length
of service and address columns are not mapped.

Blank mappings do not overwrite previously saved choices.
Successful imports remember each explicitly mapped source combination's destination (including Skip)
in `csv_role_mappings`. These defaults are shared across uploads and sessions and
remain editable on the mapping screen. Failed imports leave saved mappings unchanged.
Deleting a destination role clears its saved mappings. Apply the schema change with
`bin/cake migrations migrate` when deploying this feature.
