# Reports

## Requirements

This plugin requires Craft CMS 4 or 5.


## Installation

Install the plugin either via the plugin store or via composer:

`composer require webdna\reports && php craft plugin\install reports`


## Overview

This plugin allows a user to create/generate/view/export reports based on a report type. These report types are created as twig templates in the frontend templates folder allowing the website developer full control over the report types. A few basic example types are packaged in the plugin for coping in to the report types folder.


## Settings

The only setting is the location within the frontend template folder to the report types folder. If not specified a default location of `/_reports` will be used.


## Usage

Please see the example report types.


## Generating from the console

One command generates a report and writes its export to a folder, so a report can be produced
on a schedule:

```
php craft dnareports/reports/generate --report=monthly-orders --path=@storage/reports
```

It prints the path of the file it wrote and exits 0. If anything stops it — no such report, a
report type whose `data` template does not return JSON, a folder it cannot write — it prints why
and exits non-zero, so a scheduler records the run as failed.

| Option | |
|---|---|
| `--report` | Required. The report's id, its name (`"Monthly Orders"`, any case) or its name in kebab case (`monthly-orders`). A name shared by two reports is refused — use the id. |
| `--path` | Required. The folder to write to. Aliases and environment variables are parsed (`@storage/reports`, `'$REPORTS_EXPORT_PATH'` — quoted, see below); a relative path is taken from the project root. A missing folder is created, and a folder that cannot be written is refused before the report is generated. |
| `--format` | `csv` (default), `json`, `xml` or `xlsx`. The file is the same as the control panel's Export for that format. |
| `--filename` | The file name without its extension. Defaults to the report name and the time it was generated (`Monthly-Orders-2026-10-20_02-00-00.csv`), so each run adds a file. Set it to overwrite one file each run instead. |
| `--generate` | `0` exports the data from the report's last run without generating it again. |

The file is written under a temporary name and renamed into place, so anything reading it never
sees half a file.

### With Schedule-it

[Schedule-it](https://github.com/webdna/schedule-it) runs Craft console commands on a schedule
from one cron entry. Add the job to `config/schedule-it.php`:

```php
<?php
use webdna\scheduleit\Job;

return [
    'jobs' => [
        // A new dated CSV every night.
        Job::command('dnareports/reports/generate --report=monthly-orders --path=@storage/reports')
            ->dailyAt('02:00')
            ->description('Nightly orders report'),

        // One spreadsheet, replaced every Monday morning, in a folder set per environment.
        Job::command("dnareports/reports/generate --report=12 --path='\$REPORTS_EXPORT_PATH' --format=xlsx --filename=weekly-members")
            ->weekly()->mondays()->at('06:00'),
    ],
];
```

`php craft schedule-it/list` shows each job and when it next runs.

**Put an environment variable in single quotes** (`--path='$REPORTS_EXPORT_PATH'`). Schedule-it
and cron both run the command through a shell, which otherwise replaces the variable itself — and
under cron, which does not load `.env`, replaces it with nothing. Quoted, it reaches Craft as
written and is read from `.env`.

### With cron

Without Schedule-it, call the command from the server's crontab:

```
0 2 * * * cd /path/to/project && php craft dnareports/reports/generate --report=monthly-orders --path=@storage/reports
```

### A folder under the web root is public

Writing to a folder under the web root (`--path=@webroot/exports --filename=orders-latest`)
gives the report a stable URL (`https://example.com/exports/orders-latest.csv`) — and makes it
readable by **anyone who learns that URL**. Reports usually hold personal data: customers,
members, orders. Keep them in `@storage` unless the report is meant to be public, or put the
folder behind the web server's authentication.