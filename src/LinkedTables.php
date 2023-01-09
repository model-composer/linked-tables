<?php namespace Model\LinkedTables;

use Model\Cache\Cache;
use Model\Db\DbConnection;
use Model\ProvidersFinder\Providers;

class LinkedTables
{
	private static array $cache = [];

	public static function getTables(DbConnection $db): array
	{
		if (!isset(self::$cache[$db->getName()])) {
			$cache = Cache::getCacheAdapter();
			self::$cache[$db->getName()] = $cache->get('model.linked-tables.tables.' . $db->getName(), function (\Symfony\Contracts\Cache\ItemInterface $item) use ($db) {
				$item->expiresAfter(3600 * 24);
				return self::doGetTables($db);
			});
		}

		return self::$cache[$db->getName()];
	}

	private static function doGetTables(DbConnection $db): array
	{
		$tables = [];
		$providers = Providers::find('LinkedTablesProvider');
		foreach ($providers as $provider) {
			$providerTables = $provider['provider']::tables()[$db->getName()] ?? [];
			foreach ($providerTables as $k => $v)
				$tables[is_numeric($k) ? $v : $k] = is_numeric($k) ? $v . '_custom' : $v;
		}

		return $tables;
	}
}
