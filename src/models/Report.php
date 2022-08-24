<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports\models;

use webdna\reports\Reports;
use webdna\reports\records\Report as ReportRecord;

use Craft;
use craft\base\Model;
use craft\validators\UniqueValidator;
use craft\helpers\Json;
use craft\helpers\DateTimeHelper;


class Report extends Model
{
	// Public Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public $id;
	public $name;
	public $type;
	public $options;
	public $data;
	public $lastGenerated;
	public $isGenerating;

	// Public Methods
	// =========================================================================
	
	public function getParsedOptions(): ?array
	{
		$options = Json::decodeIfJson($this->options);
	
		if ($options) {
			foreach ($options as $key => $option) {
				if (
					is_string($option) &&
					preg_match(
						'/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/',
						$option
					)
				) {
					$options[$key] = DateTimeHelper::toDateTime($option);
				}
			}
		}
	
		return $options;
	}
	
	public function checkDates(): void
	{
		$options = Json::decodeIfJson($this->options);
	
		if (!empty($options['dateRange']['startDate']) && !empty($options['dateRange']['endDate'])) {
			if ($options['dateRange']['startDate'] > $options['dateRange']['endDate']) {
				$this->addError(
					'options.dateRange.endDate',
					'End date must be after the start date'
				);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function defineRules(): array
	{
		$rules = parent::defineRules();

		$rules[] = [['name', 'type',], 'required'];
		$rules[] = ['options', 'checkDates'];

		return $rules;
	}
}
