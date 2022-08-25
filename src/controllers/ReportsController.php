<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports\controllers;

use webdna\reports\Reports;
use webdna\reports\models\Report;
use webdna\reports\assetbundles\ReportsAsset;

use Craft;
use craft\web\Controller;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\helpers\StringHelper;
use craft\helpers\Json;
use yii\web\Response;

class ReportsController extends Controller
{
	// Protected Properties
	// =========================================================================
	
	protected $allowAnonymous = [];
	
	// Public Methods
	// =========================================================================
	
	public function actionIndex(): Response
	{
		$variables = [
			'types' => Reports::$plugin->service->getTypes(),
			'reports' => Reports::$plugin->service->getAllReports(),
		];
	
		return $this->renderTemplate('reports/index', $variables);
	}
	
	public function actionEdit($type, $id=null): Response
	{
		$this->requirePermission('reports-editReports');
		
		if ($id) {
			$report = Reports::$plugin->service->getReportById($id);
	
			if (!$report) {
				throw new NotFoundHttpException('Report not found');
			}
		} else {
			$report = new Report();
			$report->type = $type;
		}
	
		return $this->renderTemplate('reports/edit', [
			'report' => $report,
			'options' => Reports::$plugin->service->getOptions($report),
		]);
	}
	
	public function actionView($id): Response
	{
		$this->requirePermission('reports-viewReports');
		
		$report = Reports::$plugin->service->getReportById($id);
	
		if (!$report) {
			throw new NotFoundHttpException('Report not found');
		}
	
		$report->data = Json::decode($report->data);
	
		Craft::$app->getView()->registerAssetBundle(ReportsAsset::class);
			
		$data = Reports::$plugin->service->renderTemplate($report, 'view');
	
		return $this->renderTemplate('reports/view', [
			'report' => $report,
			'data' => $data,
		]);
	}
	
	public function actionSave(): void
	{
		$this->requirePermission('reports-editReports');
		
		$this->requirePostRequest();
	
		$request = Craft::$app->getRequest();
	
		$report = new Report();
		if ($id = $request->getParam('id')) {
			$report = Reports::$plugin->service->getReportById($id);
		}
	
		$options = $request->getParam('options',[]);
		
	
		foreach($options as $key => $option)
		{
			
			if (is_array($option) && array_key_exists('date', $option)) {
				$options[$key] = DateTimeHelper::toIso8601($option);
			}
			if (!empty($option['startDate'])) {
				//$options[$key]['startDate'] = DateTimeHelper::toIso8601($option['startDate']);
				$options[$key]['startDate'] = (new \DateTime($option['startDate']))->format('Y-m-d');
			}
			if (!empty($option['endDate'])) {
				//$options[$key]['endDate'] = DateTimeHelper::toIso8601($option['endDate']);
				$options[$key]['endDate'] = (new \DateTime($option['endDate']))->format('Y-m-d');
			}
		}
		
		//Craft::dd($options);
	
		$user = Craft::$app->getUser()->getIdentity();
	
		$report->name = $request->getParam('name');
		$report->type = $request->getParam('type');
		$report->options = $options;
		//$report->email = StringHelper::stripWhitespace($request->getParam('email', $user->email));
	
		if (Reports::$plugin->service->saveReport($report)) {
			$this->redirect("reports");
		}
		
		Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save report.'));
		
		// Send the entry back to the template
		Craft::$app->getUrlManager()->setRouteParams([
			'report' => $report,
			'options' => Reports::$plugin->service->getOptions($report)
		]);
	}
	
	public function actionRun(): Response
	{
		$this->requirePermission('reports-generateReports');
		
		$this->requirePostRequest();
		$this->requireAcceptsJson();
	
		$id = Craft::$app->getRequest()->getRequiredBodyParam('id');
	
		Reports::$plugin->service->runReportById($id);
	
		return $this->asJson(['success' => true]);
	}
	
	public function actionDelete(): Response
	{
		$this->requirePermission('reports-editReports');
		
		$this->requirePostRequest();
		$this->requireAcceptsJson();
	
		$id = Craft::$app->getRequest()->getRequiredBodyParam('id');
	
		Reports::$plugin->service->deleteReportById($id);
	
		return $this->asJson(['success' => true]);
	}
	
	public function actionGetStatus(): Response
	{
		$this->requirePostRequest();
		$this->requireAcceptsJson();
	
		$id = Craft::$app->getRequest()->getRequiredBodyParam('id');
	
		$report = Reports::$plugin->service->getReportById($id);
	
		return $this->asJson([
			'isGenerating' => $report->isGenerating,
			'lastGenerated' => $report->lastGenerated ? DateTimeHelper::toDateTime($report->lastGenerated)->format('d/m/Y H:i') : null,
		]);
	}
	
	public function actionExport(): Response
	{
		$this->requirePermission('reports-exportReports');
		
		$this->requirePostRequest();
	
		$id = $this->request->getRequiredBodyParam('id');
	
		$report = Reports::$plugin->service->getReportById($id);
	
		if (!$report) {
			throw new NotFoundHttpException('Report not found');
		}
	
		$this->response->format = $this->request->getBodyParam('format', 'csv');
	
		$filename = $report->name . '-'.(DateTimeHelper::toDateTime($report->lastGenerated)->format('Y-m-d_H-i-s'));
		$filename .= '.'.$this->response->format;
	
		switch ($this->response->format) {
			case Response::FORMAT_JSON:
				$this->response->formatters[Response::FORMAT_JSON]['prettyPrint'] = true;
				break;
		}
	
		$this->response->setDownloadHeaders($filename);
	
		$this->response->data = Json::decode($report->data);
	
	
		return $this->response;
	}
}