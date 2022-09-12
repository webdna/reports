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

use yii\base\Event;

class Reports extends Plugin
{
	public static $plugin;

	public $schemaVersion = '1.0.0';

	public $hasCpSettings = true;

	public $hasCpSection = true;


	public function init(): void
	{
		parent::init();
		self::$plugin = $this;

		$this->setComponents([
			'service' => ReportsService::class,
		]);

		if (Craft::$app instanceof ConsoleApplication) {
			$this->controllerNamespace = 'webdna\reports\console\controllers';
		}

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			function (RegisterUrlRulesEvent $event) {
				$event->rules['reports'] = 'reports/reports/index';
				$event->rules['reports/view/<id:\d+>'] = 'reports/reports/view';
				$event->rules['reports/export'] = 'reports/reports/export';
				$event->rules['reports/<type:[-\w]+>'] = 'reports/reports/edit';
				$event->rules['reports/<type:[-\w]+>/<id:\d+>'] = 'reports/reports/edit';
			}
		);

		Event::on(
			UserPermissions::class,
			UserPermissions::EVENT_REGISTER_PERMISSIONS,
			function (RegisterUserPermissionsEvent $event) {
				$event->permissions[Craft::t('reports', 'Reports')] = [
					'reports-viewReports' => [
						'label' => Craft::t('reports', 'View Reports'),
					],
					'reports-editReports' => [
						'label' => Craft::t('reports', 'Edit Reports'),
					],
					'reports-generateReports' => [
						'label' => Craft::t('reports', 'Generate Reports'),
					],
					'reports-exportReports' => [
						'label' => Craft::t('reports', 'Export Reports'),
					],
				];
			}
		);

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			function (Event $event) {
				/** @var CraftVariable $variable */
				$variable = $event->sender;
				$variable->set('reports', ReportsVariable::class);
			}
		);

		Craft::info(Craft::t('reports', '{name} plugin loaded', ['name' => $this->name]), __METHOD__);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function createSettingsModel()
	{
		return new Settings();
	}

	/**
	 * @inheritdoc
	 */
	protected function settingsHtml(): string
	{
		return Craft::$app->view->renderTemplate(
			'reports/settings',
			[
				'settings' => $this->getSettings()
			]
		);
	}
}
