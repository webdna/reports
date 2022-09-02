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

class Reports extends Component
{
	private array $_data = [];
	
	private string $defaultPath = '_reports';
	
	// Public Methods
	// =========================================================================
	
	public function getReportById(int $id): ReportModel
	{
		$result = $this->_createReportQuery()
			->where(['id' => $id])
			->one();
	
		return new ReportModel($result);
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
	
			if (!$record->id) {
				throw new Exception(Craft::t('reports', 'No report exists with the ID "{id}"', ['id' => $model->id]));
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
		
		Db::Update('{{%reports}}', [
			'isGenerating' => true,
		], ['id' => $id]);

		Craft::$app->queue->ttr(1800)->push(new GenerateReport([
			'reportId' => $id
		]));
		
		//Craft::$app->queue->run();
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
			
		$options = $view->renderTemplate('reports/options', [
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
			->from(['{{%reports}}']);
	}
}