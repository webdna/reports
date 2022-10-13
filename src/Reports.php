<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports;

use webdna\reports\models\Settings;
use webdna\reports\services\Reports as ReportsService;
use webdna\reports\variables\Reports as ReportsVariable;

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\web\twig\variables\CraftVariable;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;

use yii\base\Event;

class Reports extends Plugin
{
	public static $plugin;
	
	public string $schemaVersion = '1.0.2';
	
	public bool $hasCpSettings = true;
	
	public bool $hasCpSection = true;
	
	
	public function init(): void
	{
		parent::init();
		self::$plugin = $this;
			
		$this->setComponents([
			'service' => ReportsService::class,
		]);
		
		if (Craft::$app->getRequest()->getIsConsoleRequest()) {
			$this->controllerNamespace = 'webdna\reports\console\controllers';
		}
		
		Event::on(
			UrlManager::class, 
			UrlManager::EVENT_REGISTER_CP_URL_RULES, 
			function (RegisterUrlRulesEvent $event) {
				$event->rules['dnareports'] = 'dnareports/reports/index';
				$event->rules['dnareports/view/<id:\d+>'] = 'dnareports/reports/view';
				$event->rules['dnareports/export'] = 'dnareports/reports/export';
				$event->rules['dnareports/<type:[-\w]+>'] = 'dnareports/reports/edit';
				$event->rules['dnareports/<type:[-\w]+>/<id:\d+>'] = 'dnareports/reports/edit';
			}
		);
		
		Event::on(
			UserPermissions::class, 
			UserPermissions::EVENT_REGISTER_PERMISSIONS, 
			function (RegisterUserPermissionsEvent $event) {
				$event->permissions[] = [
					'heading' => Craft::t('dnareports', 'Reports'),
					'permissions' => [
						'dnareports-viewReports' => [
							'label' => Craft::t('dnareports', 'View Reports'),
						],
						'dnareports-editReports' => [
							'label' => Craft::t('dnareports', 'Edit Reports'),
						],
						'dnareports-generateReports' => [
							'label' => Craft::t('dnareports', 'Generate Reports'),
						],
						'dnareports-exportReports' => [
							'label' => Craft::t('dnareports', 'Export Reports'),
						],
					]
				];
			}
		);
		
		Event::on(
			CraftVariable::class, 
			CraftVariable::EVENT_INIT, 
			function (Event $event) {
				/** @var CraftVariable $variable */
				$variable = $event->sender;
				$variable->set('dnareports', ReportsVariable::class);
			}
		);
		
		Event::on(
			Cp::class,
			Cp::EVENT_REGISTER_CP_NAV_ITEMS,
			function(RegisterCpNavItemsEvent $event) {
				foreach($event->navItems as $key => $value) {
					if ($value['url'] == 'dnareports') {
						$event->navItems[$key]['label'] = 'Reports';
					}
				}
			}
		);
		
		Craft::info(Craft::t('dnareports', '{name} plugin loaded', ['name' => $this->name]), __METHOD__);
	}
	
	// Protected Methods
	// =========================================================================
	
	/**
	 * @inheritdoc
	 */
	protected function createSettingsModel(): Settings
	{
		return new Settings();
	}
	
	/**
	 * @inheritdoc
	 */
	protected function settingsHtml(): string
	{
		return Craft::$app->view->renderTemplate(
			'dnareports/settings',
			[
				'settings' => $this->getSettings()
			]
		);
	}
}