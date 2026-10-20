# Reports Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.5.0 - 2026-10-20
### Added
- Added the `dnareports/reports/generate` console command, which generates a report and writes its export (CSV, JSON, XML or XLSX) to a folder, so reports can be produced on a schedule with Schedule-it or cron. ([#2](https://github.com/webdna/reports/issues/2))
- Added getReportById function to dnareports craft variable
- Added ReportGenerationEvent to allow plugins/modules access to report after generation.

### Changed
- The control panel's Run and Export, and the new command, share one generator and one exporter.
- Exported file names are sanitised, so spaces in a report name become hyphens.

### Fixed
- A report whose type returns invalid JSON no longer stays marked as generating.
- Fixed a false “Couldn’t save report.” error after a report saved successfully.
- Fixed “report not found” throwing a class-not-found error instead of a 404.
- Fixed exporting a report that has never been generated causing a server error.
- Fixed reports with no date range option failing to generate or list.

## 2.4.2 - 2024-07-19
### Fixed
- Fixed a typo when selecting the "This Financial Year" date range.

## 2.4.1 - 2024-05-16
### Added
- Craft 5 compatibility

## 2.4.0 - 2023-09-19
### Added
- checkDateInRange function
- Last Week & Last Month date options
### Changed
- optimised variable methods

## 2.3.3 - 2023-04-21
### Fixed
- all starts have time set to 0

## 2.3.2 - 2022-11-07
### Fixed
- example data template

## 2.3.1 - 2022-11-02
### Fixed
- table name in update function

## 2.3.0 - 2022-10-13
### Change
- Plugin name

## 2.2.0 - 2022-10-13
### Added
- xml export type

## 2.1.6 - 2022-09-30
### Added
- yesterday date range option
### Fixed
- overwrite data with empty data

## 2.1.4 - 2022-09-15
### Added
- result pagination
### Changed
- Data column type

## 2.1.3 - 2022-09-13
### Added
- Default view column heading format
- Default date range option

## 2.1.1 - 2022-09-13
### Added
- Default view with ability to override per report type

## 2.1.0 - 2022-09-13
### Changed
- Process reports via the console

## 2.0.2 - 2022-09-01
### Fixed
- Permissions

## 2.0.0 - 2022-08-25
### Added
- Initial release
