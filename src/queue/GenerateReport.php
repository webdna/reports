<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports\queue;

use webdna\reports\Reports;
use webdna\reports\models\Report;

use Craft;
use craft\queue\BaseJob;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Db;

class GenerateReport extends BaseJob
{
	public int $reportId;

	// Public Methods
	// =========================================================================

	public function execute($queue): void
	{
		try {
			$this->setProgress( $queue, 0.5 );
			
			$report = Reports::$plugin->service->getReportById($this->reportId);

			$data = Reports::$plugin->service->renderTemplate($report, 'data');

			$data = StringHelper::replaceAll($data, ["\n","\t","\r"], ['','','']);
			$data = Json::decode($data);
			
			$report->data = $data;
			$report->lastGenerated = new \DateTime();
			$report->isGenerating = false;
			
			Reports::$plugin->service->saveReport($report);

			$this->setProgress( $queue, 1 );
			

		} catch (\Exception $e) {
			Craft::error($e->getMessage(), __METHOD__);
		}
	}

	protected function defaultDescription(): ?string
	{
		return 'Generating Report.';
	}
}
