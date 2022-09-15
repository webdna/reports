<?php

namespace webdna\reports\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220915_000001_datatype migration.
 */
class m220915_000001_datatype extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp(): bool
	{
		$this->alterColumn('{{%reports}}', 'data', $this->longText());
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown(): bool
	{
		echo "m220915_000001_datatype cannot be reverted.\n";
		return false;
	}
}
