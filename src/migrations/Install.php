<?php
/**
 * Reports plugin for Craft CMS 3.x
 *
 * Reports
 *
 * @link      https://webdna.co.uk
 * @copyright Copyright (c) 2022 WebDNA
 */
 
namespace webdna\reports\migrations;

use webdna\reports\Reports;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
	public $driver;
	
	// Public Methods
	// =========================================================================
	
	public function safeUp(): bool
	{
		$this->driver = Craft::$app->getConfig()->getDb()->driver;
	
		$this->createTables();
		$this->createIndexes();
		$this->addForeignKeys();
	
		// Refresh the db schema caches
		Craft::$app->db->schema->refresh();
		$this->insertDefaultData();
	
		return true;
	}
	
	public function safeDown(): bool
	{
		$this->driver = Craft::$app->getConfig()->getDb()->driver;
		$this->dropForeignKeys();
		$this->dropTables();
	
		return true;
	}
	
	// Protected Methods
	// =========================================================================
	
	protected function createTables(): void
	{
		$tableSchema = Craft::$app->db->schema->getTableSchema('{{%reports}}');
		if ($tableSchema === null) {
			$this->createTable(
				'{{%reports}}',
				[
					'id' => $this->primaryKey(),
					'name' => $this->string(255)->notNull(),
					'type' => $this->string(255)->notNull(),
					'options' => $this->text(),
					'data' => $this->longText(),
					'isGenerating' => $this->boolean(),
					'lastGenerated' => $this->dateTime(),
					'dateUpdated' => $this->dateTime()->notNull(),
					'dateCreated' => $this->dateTime()->notNull(),
					'uid' => $this->uid(),
				]
			);
		}
	}
	
	protected function createIndexes(): void
	{
	}
	
	protected function addForeignKeys(): void
	{
	}
	
	protected function insertDefaultData(): void
	{
	}
	
	protected function dropTables(): void
	{
		$this->dropTableIfExists("{{%reports}}");
	}
	
	public function dropForeignKeys(): void
	{
	}
}