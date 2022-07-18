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

use Craft;
use craft\base\Model;

/**
 * @author    WebDNA
 * @package   MYOB
 * @since     1.0.0
 */
class Settings extends Model
{
	// Public Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public string $templatePath = '';

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function defineRules(): array
	{
		return [
			[['templatePath'], 'string'],
		];
	}
}
