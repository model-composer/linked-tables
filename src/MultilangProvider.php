<?php namespace Model\LinkedTables;

use Model\Db\DbConnection;
use Model\Multilang\AbstractMultilangProvider;
use Model\Multilang\Ml;

class MultilangProvider extends AbstractMultilangProvider
{
	public static function tables(DbConnection $db): array
	{
		$mlTables = Ml::getTablesConfig($db, ['model/linked-tables']);
		$linkedTables = LinkedTables::getTables($db);

		$customMultilangTables = [];
		foreach ($mlTables as $table => $tableOptions) {
			if (isset($linkedTables[$table]))
				$customMultilangTables[] = $linkedTables[$table];
		}

		return $customMultilangTables;
	}
}
