<?php namespace Model\LinkedTables;

use Model\Db\AbstractDbProvider;
use Model\Db\DbConnection;
use Model\DbParser\Table;
use Model\Multilang\Ml;

class DbProvider extends AbstractDbProvider
{
	public static function alterInsert(DbConnection $db, array $queries): array
	{
		$new = [];
		foreach ($queries as $query) {
			[$customTable, $multilang] = self::searchForCustomTable($db, $query['table']);

			if ($customTable) {
				$customTableModel = $db->getParser()->getTable($customTable);

				$customFields = [];
				foreach ($customTableModel->columns as $column_name => $column) {
					if ($column_name === $customTableModel->primary[0])
						continue;
					$customFields[] = $column_name;
				}

				foreach ($query['rows'] as $row) {
					$mainRow = [];
					$customRow = [];
					foreach ($row as $k => $v) {
						if (in_array($k, $customFields))
							$customRow[$k] = $v;
						else
							$mainRow[$k] = $v;
					}

					$mainRowIdx = count($new);
					$new[] = [
						'table' => $query['table'],
						'rows' => [$mainRow],
						'options' => $query['options'],
					];

					$new[] = [
						'table' => $customTable,
						'rows' => [$customRow],
						'options' => array_merge($query['options'], [
							'replace_ids' => [
								[
									'from' => $mainRowIdx,
									'field' => $customTableModel->primary[0],
								],
							],
						]),
					];
				}
			} else {
				$new[] = $query;
			}
		}

		return $new;
	}

	public static function alterUpdate(DbConnection $db, array $queries): array
	{
		// TODO
		return $queries;
	}

	public static function alterSelect(DbConnection $db, string $table, array|int $where, array $options): array
	{
		[$customTable, $multilang] = self::searchForCustomTable($db, $table);

		if ($customTable) {
			$tableModel = $db->getParser()->getTable($table);
			$customTableModel = $db->getParser()->getTable($customTable);

			$customFields = [];
			foreach ($customTableModel->columns as $column_name => $column) {
				if ($column_name === $customTableModel->primary[0])
					continue;
				$customFields[] = $column_name;
			}

			if (!isset($options['joins']))
				$options['joins'] = [];

			$options['joins'][] = [
				'type' => 'LEFT',
				'table' => $customTable,
				'alias' => ($options['alias'] ?? $table) . '_custom',
				'on' => [
					$tableModel->primary[0] => $customTableModel->primary[0],
				],
				'fields' => $customFields,
				'injected' => true,
			];

			if ($multilang) {
				$joinedMlTableName = ($options['alias'] ?? $table) . '_lang';
				$isMlJoined = false;
				foreach ($options['joins'] as $join) {
					if (($join['alias'] ?? $join['table']) === $joinedMlTableName) {
						$isMlJoined = true;
						break;
					}
				}

				if ($isMlJoined) {
					$mlTableModel = $db->getParser()->getTable($table . $multilang['table_suffix']);
					$mlCustomTableModel = $db->getParser()->getTable($customTable . $multilang['table_suffix']);

					$mlFields = [];
					foreach ($multilang['fields'] as $f) {
						if (isset($mlCustomTableModel->columns[$f]))
							$mlFields[] = $f;
					}

					$options['joins'][] = [
						'type' => 'LEFT',
						'table' => $customTable . $multilang['table_suffix'],
						'alias' => ($options['alias'] ?? $table) . '_custom_lang',
						'on' => [
							$joinedMlTableName . '.' . $mlTableModel->primary[0] => $mlCustomTableModel->primary[0],
						],
						'fields' => $mlFields,
						'injected' => true,
					];
				}
			}
		}

		return [$where, $options];
	}

	private static function searchForCustomTable(DbConnection $db, string $table): array
	{
		$linkedTables = LinkedTables::getTables($db);

		$customTable = null;
		$multilang = null;

		if (array_key_exists($table, $linkedTables)) {
			// Direct query to table
			$customTable = $linkedTables[$table];

			if (class_exists('\\Model\\Multilang\\Ml')) {
				$mlTables = Ml::getTables($db);
				if (isset($mlTables[$table]))
					$multilang = $mlTables[$table];
			}
		} elseif (class_exists('\\Model\\Multilang\\Ml')) {
			// In case of multilang tables, if I select from bar_texts it must select also from bar_custom_texts
			$mlTables = Ml::getTables($db);
			foreach ($mlTables as $mlTable => $mlTableOptions) {
				if ($mlTable . $mlTableOptions['table_suffix'] === $table and array_key_exists($mlTable, $linkedTables)) {
					$customTable = $linkedTables[$mlTable] . $mlTableOptions['table_suffix'];
					break;
				}
			}
		}

		return [$customTable, $multilang];
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

	public static function getDependencies(): array
	{
		return ['model/multilang'];
	}
}
