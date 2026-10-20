<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */

namespace webdna\reports\console\controllers;

use webdna\reports\Reports;
use webdna\reports\services\Reports as ReportsService;

use Craft;
use craft\console\Controller;
use craft\helpers\App;
use craft\helpers\FileHelper;
use yii\console\ExitCode;
use yii\helpers\Console;


/**
 * Generates reports from the console.
 */
class ReportsController extends Controller
{
	// Public Properties
	// =========================================================================

	public int $reportId = 0;

	/**
	 * @var string The report: its id, its name, or its name in kebab case.
	 */
	public string $report = '';

	/**
	 * @var string The folder to write the export to. Aliases and environment variables are parsed; a relative path is taken from the project root.
	 */
	public string $path = '';

	/**
	 * @var string The export format: csv, json, xml or xlsx.
	 */
	public string $format = 'csv';

	/**
	 * @var string The file name without its extension. Defaults to the report name and the time it was generated.
	 */
	public string $filename = '';

	/**
	 * @var bool Whether to generate the report first. Set to 0 to export the data from its last run.
	 */
	public bool $generate = true;

	// Public Methods
	// =========================================================================

	public function options($actionID): array
	{
		$options = parent::options($actionID);

		if ($actionID === 'generate-report') {
			$options[] = 'reportId';
		}

		if ($actionID === 'generate') {
			array_push($options, 'report', 'path', 'format', 'filename', 'generate');
		}

		return $options;
	}

	public function actionGenerateReport(): int
	{
		$report = Reports::$plugin->service->getReportById($this->reportId);

		Reports::$plugin->service->generateReport($report);

		return ExitCode::OK;
	}

	/**
	 * Generates a report and writes its export to a folder.
	 *
	 * ```
	 * php craft dnareports/reports/generate --report=monthly-orders --path=@storage/reports
	 * ```
	 */
	public function actionGenerate(): int
	{
		if ($this->report === '' || $this->path === '') {
			$this->stderr('Both --report and --path are required.' . PHP_EOL, Console::FG_RED);
			return ExitCode::USAGE;
		}

		if (!in_array($this->format, ReportsService::EXPORT_FORMATS, true)) {
			$this->stderr("Unknown format “{$this->format}”; use one of " . implode(', ', ReportsService::EXPORT_FORMATS) . '.' . PHP_EOL, Console::FG_RED);
			return ExitCode::USAGE;
		}

		$service = Reports::$plugin->service;

		try {
			$report = $service->findReport($this->report);
			$dir = $this->exportDirectory();

			if ($this->generate) {
				$this->stdout("Generating “{$report->name}” … ");
				$service->generateReport($report);
				$this->stdout('done' . PHP_EOL, Console::FG_GREEN);
			} elseif (!$report->lastGenerated) {
				throw new \Exception("“{$report->name}” has never been generated; run without --generate=0.");
			}

			$content = $service->exportReport($report, $this->format);
			$file = $this->writeExport($dir, $content, $service->getExportFilename($report, $this->format, $this->filename));
		} catch (\Throwable $e) {
			$this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
			return ExitCode::UNSPECIFIED_ERROR;
		}

		$this->stdout($file . PHP_EOL);

		return ExitCode::OK;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Resolves --path, creating the folder, and refuses one it cannot write - before any report is generated.
	 */
	private function exportDirectory(): string
	{
		$dir = App::parseEnv($this->path);

		if (!preg_match('#^([/\\\\]|[A-Za-z]:[/\\\\])#', $dir)) {
			$dir = Craft::getAlias('@root') . DIRECTORY_SEPARATOR . $dir;
		}

		$dir = FileHelper::normalizePath($dir);
		try {
			FileHelper::createDirectory($dir);
		} catch (\Throwable) {
		}

		if (!is_dir($dir) || !is_writable($dir)) {
			throw new \Exception("Cannot write to {$dir}.");
		}

		return $dir;
	}

	/**
	 * Writes the export beside its destination and renames it into place, so a reader never sees half a file.
	 */
	private function writeExport(string $dir, string $content, string $filename): string
	{

		$file = $dir . DIRECTORY_SEPARATOR . $filename;
		$tmp = $file . '.' . uniqid('tmp', true);

		if (@file_put_contents($tmp, $content) === false || !@rename($tmp, $file)) {
			@unlink($tmp);
			throw new \Exception("Could not write {$file}.");
		}

		return $file;
	}
}
