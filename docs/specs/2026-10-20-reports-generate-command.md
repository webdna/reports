---
spec: Reports - generate and export from the console
slug: reports-generate-command
status: built
version: 0.2
date: 2026-10-20
author: Claude (with Sam Birch)
client: webdna (Craft plugin)
approver: Sam Birch
related: [webdna/reports#2]
---

# Reports - generate and export from the console

> **Status:** built - **Version:** 0.2
> One console command generates a report and writes its export to a folder, so a report can
> be produced on a schedule (schedule-it or a plain cron line) and picked up from disk or a URL.

---

## 1. How it works

`php craft dnareports/reports/generate --report=<report> --path=<dir>` finds the report, runs
its `data` template exactly as the control panel's Run button does, saves the result as the
report's latest data, and writes that data to `<dir>` in the chosen format. The file is the
same bytes the control panel's Export button would download for that report and format, because
both build it through one service method.

The command prints the path it wrote and exits 0. Anything that stops it - no such report, a
template that does not return JSON, a listener refusing the report, a folder it cannot write -
prints why and exits non-zero, so a scheduler records the run as failed.

## 2. Scope

**In:** the `generate` console action; `--report`, `--path`, `--format`, `--filename`,
`--generate`; one service path for generating and one for exporting, shared by the control
panel; a README section with schedule-it and cron examples.

**Out:** a report handle column (reports are found by id or name, see R1); scheduling inside
the plugin (that is schedule-it's or cron's job); emailing or uploading the file; pruning old
exports from the folder; a Craft volume as the destination.

## 3. User journeys

- **J1 - a nightly file.** A developer adds `Job::command('dnareports/reports/generate
  --report=orders --path=@storage/reports')->dailyAt('02:00')` to `config/schedule-it.php`.
  Each night a new timestamped CSV appears in `storage/reports/`.
- **J2 - a stable URL.** The same job with `--path=@webroot/exports --filename=orders-latest`
  overwrites one file each run, so `https://site/exports/orders-latest.csv` always serves the
  newest. (Anyone with the URL can read it - R9.)
- **J3 - by hand.** An operator runs the command in a terminal to get a file without the
  control panel.

## 4. Data model

No change. A report is still `id`, `name`, `type`, `options`, `data`, `lastGenerated`,
`isGenerating` in `{{%dnareports}}`.

## 5. Rules

- **R1 - finding the report.** `--report` is matched, in order, against the id (when it is all
  digits), the name (case-insensitive) and the name in kebab case (`Monthly Orders` →
  `monthly-orders`, so a cron line needs no quoting). A value matching more than one report is
  refused with the matching ids listed; no match is refused.
- **R2 - paths.** `--path` is read with `App::parseEnv()`, so `@storage/reports`,
  `$EXPORT_PATH` and `@webroot/exports` all work. A relative path is taken from the project
  root (`@root`), not the shell's working directory, so a scheduler's working directory does
  not matter. A missing folder is created; one that cannot be written is refused before the
  report is generated. Schedule-it and cron run the line through a shell, so the README tells
  people to single-quote an environment variable (`--path='$REPORTS_EXPORT_PATH'`) - unquoted,
  the shell expands it, and under cron (no `.env`) to nothing.
- **R3 - format.** `--format` is `csv` (default), `json`, `xml` or `xlsx`; anything else is a
  usage error before any work is done.
- **R4 - file name.** Default `<report name>-<lastGenerated as Y-m-d_H-i-s>.<format>`, as the
  control panel's download. `--filename=<name>` replaces the part before the extension. The
  name is passed through `FileHelper::sanitizeFilename()` so a report name cannot leave the
  folder.
- **R5 - the write is atomic.** The file is written beside its destination under a temporary
  name and renamed into place, so a reader of a stable URL never sees half a file. An existing
  file of the same name is replaced.
- **R6 - generating.** Generating marks the report `isGenerating`, renders its `data` template,
  requires the result to decode as JSON, stores it and clears `isGenerating`, firing
  `EVENT_AFTER_REPORT_GENERATED` exactly as today. If any step fails `isGenerating` is cleared
  and the stored data is left as it was.
- **R7 - export only.** `--generate=0` skips generating and exports the data from the last
  run; a report never generated is refused.
- **R8 - one path.** The control panel's Run (via the existing `generate-report` action) and
  Export use the same service methods as the command; there is one generator and one exporter.
- **R9 - the README warns** that a folder under the web root is public: a report of members
  or orders written there is readable by anyone who learns the URL.

## 6. Interfaces

- Console: `dnareports/reports/generate` (new), `dnareports/reports/generate-report
  --reportId=<id>` (existing, unchanged behaviour).
- Service (`Reports::$plugin->service`): `findReport(string $ref): ReportModel`,
  `generateReport(ReportModel $report): void`, `exportReport(ReportModel $report, string
  $format): string`, `getExportFilename(ReportModel $report, string $format, ?string $base =
  null): string`. Generate and find throw an exception carrying the reason; the command turns it
  into a message and an exit code.

## 7. Test plan

All in the craft5 sandbox (`~/projects/craft5`, lowercase) with the plugin's `users` example
type copied to `templates/_reports/users/`.

| # | Check | Expect |
|---|---|---|
| T1 | `generate --report=<id> --path=@storage/reports` | exit 0, CSV with a header row and one row per user; `lastGenerated` moved |
| T2 | same by name, by kebab name | exit 0, same report |
| T3 | `--format=json`, `xml`, `xlsx` | each file opens as its format |
| T4 | `--path='$REPORTS_EXPORT_PATH'` set in `.env`; a relative `--path=storage/rel` | written to the env value; written under the project root |
| T5 | `--filename=latest` twice | one file, replaced |
| T6 | unknown report; two reports with one name; `--format=pdf`; unwritable path | non-zero exit and a reason each |
| T7 | `--generate=0` | `lastGenerated` unchanged, file written |
| T8 | a `data` template returning invalid JSON | non-zero exit, `isGenerating` false, old data kept |
| T9 | CP Export of the same report and format after T1 | byte-identical to the command's file |
| T10 | the schedule-it job from the README in craft5's `config/schedule-it.php`, `schedule-it/list` | listed with its expression |

## 8. Build order

### Tasks

- [x] **1.1 Service: find, generate, export.** `src/services/Reports.php`. R1, R4, R6, R8.
  Check: `php -l`; T1 once 1.2 exists.
- [x] **1.2 The command.** `src/console/controllers/ReportsController.php` - `actionGenerate`
  with the five options; `generate-report` delegates to the service. R2, R3, R5, R7.
  Check: T1-T8.
- [x] **1.3 CP export through the service.** `src/controllers/ReportsController.php`
  `actionExport`. R8. Check: T9.
- [x] **1.4 Docs.** README section (schedule-it, cron, the public-folder warning), CHANGELOG
  2.5.0 entry. R9. Check: T10.

---

# Appendix B - build scaffolding

## B1. Before writing anything

Reports has no tests, PHPStan or ECS of its own; verification is `php -l` plus the craft5 runs.
`main` is one ahead of origin (4b50efd) - do not push without Sam.

## B3. Guardrails

- Do not add a handle column; R1 is the agreed lookup.
- Do not change what the CP Run button does beyond routing through the shared generator.
- Never commit in craft5.

## B5. Verification

```
# from ~/projects/craft5 (lowercase)
ddev exec php -l vendor/webdna/reports/src/services/Reports.php
ddev craft dnareports/reports/generate --report=<id> --path=@storage/reports
# expect: per §7
```

## Change log

- 0.1 (20 Oct 2026) - first draft, from webdna/reports#2.
- 0.2 (20 Oct 2026) - built. T1-T10 passed in craft5: four formats; id, name and kebab name;
  `$REPORTS_EXPORT_PATH`, `@webroot` (served 200) and a relative path from `/tmp`; ambiguous
  name, unknown report, bad format, missing option and unwritable folder each refused; invalid
  JSON leaves `isGenerating` 0; CP Export byte-identical for csv/json/xml; CP Run works through
  the shared generator; both README jobs listed by `schedule-it/list`. Found while building:
  an unwritable folder used to fail only after regenerating (now refused first, R2), and the
  env-var quoting trap (R2). Then end to end: two jobs fired through `schedule-it/run` (alias
  CSV, quoted `$REPORTS_EXPORT_PATH` xlsx), both exit 0 with their files written. Released as
  2.5.0, folding the never-tagged 2.4.3 into it.
