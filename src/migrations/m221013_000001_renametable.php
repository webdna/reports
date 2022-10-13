<?php

namespace webdna\reports\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m221013_000001_renametable migration.
 */
class m221013_000001_renametable extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp(): bool
	{
		MigrationHelper::renameTable('{{%reports}}', '{{%dnareports}}');
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown(): bool
	{
		echo "m221013_000001_renametable cannot be reverted.\n";
		return false;
	}
}
