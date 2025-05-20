<?php

/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2025 WebDNA
 */

namespace webdna\reports\events;

use craft\base\Event;
use webdna\reports\models\Report;

/**
 * ReportGenerationEvent class.
 *
 * @since 2.4.3
 */
class ReportGenerationEvent extends Event
{
    /**
     * @var array The report that triggered this event when it was saved.
     */
    public Report $report;

    /**
     * @var bool Whether to continue performing the action that called this event
     */
    public bool $isValid;
}
