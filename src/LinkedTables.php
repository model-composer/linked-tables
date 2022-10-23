<?php namespace Model\LinkedTables;

use Model\Db\DbConnection;
use Model\ProvidersFinder\Providers;

class LinkedTables
{
	private static array $cache = [];

	public static function getTables(DbConnection $db): array
	{
		if (!isset(self::$cache[$db->getName()])) {
			self::$cache[$db->getName()] = [];
			$providers = Providers::find('LinkedTablesProvider');
			foreach ($providers as $provider) {
				$providerTables = $provider['provider']::tables()[$db->getName()] ?? [];
				foreach ($providerTables as $k => $v)
					self::$cache[$db->getName()][is_numeric($k) ? $v : $k] = is_numeric($k) ? $v . '_custom' : $v;
			}
		}

		return self::$cache[$db->getName()];
	}
}
