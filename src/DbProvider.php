<?php namespace Model\LinkedTables;

use Model\Db\AbstractDbProvider;
use Model\Db\DbConnection;
use Model\DbParser\Table;

class DbProvider extends AbstractDbProvider
{
	public static function alterTableModel(DbConnection $db, string $table, Table $tableModel): Table
	{
		$linkedTables = LinkedTables::getTables($db);
		if (array_key_exists($table, $linkedTables)) {
			$customTableModel = $db->getParser()->getTable($linkedTables[$table]);
			$tableModel->loadColumns($customTableModel->columns, false);
		}

		return $tableModel;
	}
}
