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

use craft\helpers\Json;
use craft\helpers\StringHelper;
use yii\console\Controller;
use yii\console\ExitCode;
use DateTime;

class ReportsController extends Controller
{

    public $reportId = 0;

    // Public Methods
    // =========================================================================
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID === 'generate-report') {
            $options[] = 'reportId';
        }

        return $options;
    }

    public function actionGenerateReport(): int
    {
        $report = Reports::$plugin->service->getReportById($this->reportId);

        $data = Reports::$plugin->service->renderTemplate($report, 'data');

        $data = StringHelper::replaceAll($data, ["\n","\t","\r"], ['','','']);
        $data = Json::decode($data);

        $report->data = $data;
        $report->lastGenerated = new DateTime();
        $report->isGenerating = false;

        Reports::$plugin->service->saveReport($report);

        return ExitCode::OK;
    }

}
