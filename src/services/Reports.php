<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports\services;

use webdna\reports\Reports as Plugin;
use webdna\reports\models\Report as ReportModel;
use webdna\reports\records\Report as ReportRecord;
use webdna\reports\queue\GenerateReport;
use webdna\reports\assetbundles\ReportsAsset;

use Craft;
use craft\base\Component;
use yii\base\Exception;
use yii\db\Expression;
use craft\web\View;
use craft\db\Query;
use craft\mail\Message;
use craft\helpers\StringHelper;
use craft\helpers\FileHelper;
use craft\helpers\Template as TemplateHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Db;
use craft\web\Response as WebResponse;
use webdna\reports\events\ReportGenerationEvent;
use DateTime;
use yii\base\InvalidArgumentException;

class Reports extends Component
{

	/**
     * @event ReportGenerationEvent The event that is triggered when a report is generated before it is saved.
     *
     * You may set [[webdna\reports\events::$isValid]] to `false` to prevent the report from being saved.
     *
     * @since 2.4.3
     */
    public const EVENT_AFTER_REPORT_GENERATED = 'afterReportGenerated';

	/**
	 * The formats a report can be exported in.
	 */
	public const EXPORT_FORMATS = [
		WebResponse::FORMAT_CSV,
		WebResponse::FORMAT_JSON,
		WebResponse::FORMAT_XML,
		WebResponse::FORMAT_XLSX,
	];

	private array $_data = [];
	
	private string $defaultPath = '_reports';
	
	// Public Methods
	// =========================================================================
	
	public function getReportById(int $id): ?ReportModel
	{
		$result = $this->_createReportQuery()
			->where(['id' => $id])
			->one();

		if (empty($result)) {
			return null;
		}
		return new ReportModel($result);
	}
	
	/**
	 * Finds a report by its id, its name (case-insensitive) or its name in kebab case.
	 *
	 * @throws InvalidArgumentException if no report, or more than one, matches
	 */
	public function findReport(string $ref): ReportModel
	{
		$ref = trim($ref);

		if (ctype_digit($ref) && ($report = $this->getReportById((int)$ref))) {
			return $report;
		}

		$matches = array_values(array_filter($this->getAllReports(), function(ReportModel $report) use ($ref) {
			return mb_strtolower((string)$report->name) === mb_strtolower($ref)
				|| StringHelper::toKebabCase((string)$report->name) === StringHelper::toKebabCase($ref);
		}));

		if (count($matches) === 1) {
			return $matches[0];
		}

		if (empty($matches)) {
			throw new InvalidArgumentException("No report matches “{$ref}”.");
		}

		$ids = implode(', ', array_map(fn(ReportModel $report) => $report->id, $matches));
		throw new InvalidArgumentException("More than one report matches “{$ref}” (ids {$ids}); use the id.");
	}

	/**
	 * Runs a report's data template and saves the result as its latest data.
	 *
	 * @throws Exception if the template is missing, does not return JSON, or the report is not saved
	 */
	public function generateReport(ReportModel $report): void
	{
		Db::update('{{%dnareports}}', ['isGenerating' => true], ['id' => $report->id]);

		$previous = [$report->data, $report->lastGenerated];
		$saved = false;

		try {
			$data = $this->renderTemplate($report, 'data');

			if ($data === null) {
				throw new Exception("Report type “{$report->type}” has no data template.");
			}

			$data = StringHelper::replaceAll($data, ["\n","\t","\r"], ['','','']);

			try {
				$data = Json::decode($data);
			} catch (InvalidArgumentException $e) {
				throw new Exception("Report type “{$report->type}” did not return valid JSON: {$e->getMessage()}");
			}

			if (!is_array($data)) {
				throw new Exception("Report type “{$report->type}” did not return a JSON array.");
			}

			$report->data = Json::encode($data);
			$report->lastGenerated = new DateTime();
			$report->isGenerating = false;

			$saved = $this->saveReport($report);

			if (!$saved) {
				throw new Exception('The report was not saved: ' . ($report->hasErrors() ? implode(' ', $report->getFirstErrors()) : 'a listener refused it.'));
			}
		} finally {
			if (!$saved) {
				[$report->data, $report->lastGenerated] = $previous;
				$report->isGenerating = false;
				Db::update('{{%dnareports}}', ['isGenerating' => false], ['id' => $report->id]);
			}
		}
	}

	/**
	 * Formats a report's latest data as a file's contents - the same bytes the control panel's export downloads.
	 *
	 * @throws InvalidArgumentException if the format is not one of EXPORT_FORMATS
	 * @throws Exception if the report has never been generated
	 */
	public function exportReport(ReportModel $report, string $format): string
	{
		if (!in_array($format, self::EXPORT_FORMATS, true)) {
			throw new InvalidArgumentException("Unknown format “{$format}”; use one of " . implode(', ', self::EXPORT_FORMATS) . '.');
		}

		if (!$report->lastGenerated) {
			throw new Exception('Report has not been generated yet');
		}

		$response = new WebResponse();
		$response->format = $format;
		$response->data = is_string($report->data) ? Json::decode($report->data) : $report->data;

		$formatter = $response->formatters[$format];
		if (!is_object($formatter)) {
			$formatter = Craft::createObject($formatter);
		}

		$language = Craft::$app->language;

		switch ($format) {
			case WebResponse::FORMAT_JSON:
				$formatter->prettyPrint = true;
				break;
			case WebResponse::FORMAT_XML:
				Craft::$app->language = 'en-US';
				$formatter->rootTag = StringHelper::toCamelCase($report->name);
				break;
		}

		try {
			$formatter->format($response);
		} finally {
			Craft::$app->language = $language;
		}

		return (string)$response->content;
	}

	/**
	 * The export's file name: `<name>-<lastGenerated>.<format>`, or `<base>.<format>`.
	 */
	public function getExportFilename(ReportModel $report, string $format, ?string $base = null): string
	{
		if ($base === null || $base === '') {
			$base = $report->name . '-' . DateTimeHelper::toDateTime($report->lastGenerated)->format('Y-m-d_H-i-s');
		}

		return FileHelper::sanitizeFilename($base . '.' . $format, ['asciiOnly' => false]);
	}

	public function getAllReports(): array
	{
		$reports = [];
		$results = $this->_createReportQuery()->all();
	
		foreach ($results as $row) {
			$reports[] = new ReportModel($row);
		}
		
		return $reports;
	}
	
	public function saveReport(ReportModel $model): bool
	{
		if ($model->id) {
			$record = ReportRecord::findOne($model->id);
	
			if (!$record) {
				throw new Exception(Craft::t('dnareports', 'No report exists with the ID "{id}"', ['id' => $model->id]));
			}
		} else {
			$record = new ReportRecord();
		}
	
		if (!$model->validate()) {
			Craft::info('Report could not save due to validation error.', __METHOD__);
			return false;
		}
	
		$record->name = $model->name;
		$record->type = $model->type;
		$record->options = $model->options;
		if ($model->data) {
			$record->data = $model->data;
		}
		$record->lastGenerated = $model->lastGenerated;
		$record->isGenerating = $model->isGenerating;

		if ($model->isGenerating == false && !empty($model->data)) {
			// lets give plugins/modules the chance to do something (readonly) with report once generated
			if ($this->hasEventHandlers(self::EVENT_AFTER_REPORT_GENERATED)) {
				$event = new ReportGenerationEvent([
					'report' => $model,
					'isValid' => true,
				]);
				$this->trigger(self::EVENT_AFTER_REPORT_GENERATED, $event);

				if (!$event->isValid) {
					return false;
				}
			}
		}
		
		//Craft::dd($record->options);
	
		$record->save(false);
	
		$model->id = $record->id;
	
		return true;
	}
	
	public function deleteReportById(int $id): bool
	{
		$record = ReportRecord::findOne($id);
	
		if ($record) {
			return (bool)$record->delete();
		}
	
		return false;
	}
	
	public function runReportById(int $id): void
	{
		
		Db::Update('{{%dnareports}}', [
			'isGenerating' => true,
		], ['id' => $id]);

		/*Craft::$app->queue->ttr(1800)->push(new GenerateReport([
			'reportId' => $id
		]));*/
		
		//Craft::$app->queue->run();
			
		Plugin::$plugin->controllerNamespace = 'webdna\reports\console\controllers';
		$controller = Craft::$app->controller;
		
		$command = $controller->run("reports/generate-report", ["report-id"=>$id]);
	}
	
	public function getTypes(): ?array
	{
		$types = [];
	
		$path = $this->getTemplatePath();
	
		try {
			$folders = FileHelper::findDirectories(FileHelper::normalizePath($path));
		} catch (\Exception $e) {
			Craft::warning('Reports folder not found','reports');
			Craft::$app->session->setError($e->getMessage());
			return null;
		}
	
		foreach($folders as $folder){
			$types[] = pathinfo($folder, PATHINFO_BASENAME);
		}
	
		asort($types);
	
		return $types;
	}
	
	public function getOptions(ReportModel $report): ?string
	{
		$view = Craft::$app->getView();
		$view->registerAssetBundle(ReportsAsset::class);
			
		$options = $view->renderTemplate('dnareports/options', [
			'options' => $report->parsedOptions,
			'report' => $report,
		]);
		
		return $options.$this->renderTemplate($report, 'options');
	}
	
	public function renderTemplate(ReportModel $report, string $template): ?string
	{
		$path = $this->getTemplatePath(false).$report->type;
		
		$view = Craft::$app->getView();
		$oldMode = $view->getTemplateMode();
		$view->setTemplateMode(View::TEMPLATE_MODE_SITE);
		
		if (!$view->resolveTemplate($path."/".$template)) {
			$view->setTemplateMode($oldMode);
			return null;
		}
			
		$html = $view->renderTemplate($path."/".$template, [
			'options' => $report->parsedOptions,
			'report' => $report,
		]);
		
		$view->setTemplateMode($oldMode);
		
		return $html;
	}
	
	public function getTemplate(ReportModel $report): string
	{
		$path = $this->getTemplatePath().$report->type;
	
		$view = Craft::$app->getView();
	
		$result = $view->renderTemplate($path."/results", [ 
			'options' => $report->parsedOptions,
		]);
	
		return $result;
	}
	
	// Private Methods
	// =========================================================================
	
	public function getTemplatePath(bool $fullPath=true): string
	{
		$path = Plugin::$plugin->getSettings()->templatePath;
			
		if (!$path) {
			$path = $this->defaultPath;
		}
		
		if ($fullPath) {
			return Craft::$app->getPath()->getSiteTemplatesPath()."/".$path."/";
		} else {
			return $path."/";
		}
	}
	
	private function _createReportQuery(): Query
	{
		return (new Query())
			->select([
				'id',
				'name',
				'type',
				'options',
				'data',
				'isGenerating',
				'lastGenerated',
			])
			->from(['{{%dnareports}}']);
	}
}