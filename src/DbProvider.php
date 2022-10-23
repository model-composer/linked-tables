<?php namespace Model\LinkedTables;

use Model\Db\AbstractDbProvider;
use Model\Db\DbConnection;
use Model\DbParser\Table;

class DbProvider extends AbstractDbProvider
{
	public static function alterSelect(DbConnection $db, string $table, array $where, array $options): array
	{
		$linkedTables = LinkedTables::getTables($db);
		if (isset($linkedTables[$table])) {
			if (!isset($options['joins']))
				$options['joins'] = [];

			$tableModel = $db->getParser()->getTable($table);
			$customTableModel = $db->getParser()->getTable($linkedTables[$table]);

			$customFields = [];
			foreach ($customTableModel->columns as $column_name => $column) {
				if ($column_name === $customTableModel->primary[0])
					continue;
				$customFields[] = $column_name;
			}

			$options['joins'][] = [
				'type' => 'LEFT',
				'table' => $linkedTables[$table],
				'alias' => ($options['alias'] ?? $table) . '_custom',
				'on' => [
					$tableModel->primary[0] => $customTableModel->primary[0],
				],
				'fields' => $customFields,
			];
		}

		return [$where, $options];
	}

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
