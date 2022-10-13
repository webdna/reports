<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports\records;

use Craft;
use craft\db\ActiveRecord;

class Report extends ActiveRecord
{
	// Public Static Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return '{{%dnareports}}';
	}
}