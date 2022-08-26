<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ReportsAsset extends AssetBundle
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init(): void
	{
		$this->sourcePath = "@webdna/reports/assetbundles/dist";

		$this->depends = [
			CpAsset::class,
		];

		$this->js = [
			'js/Reports.js',
			'js/Export.js',
			'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js',
			'js/DataTable.js',
		];

		$this->css = [
			'css/Reports.css',
		];

		parent::init();
	}
}