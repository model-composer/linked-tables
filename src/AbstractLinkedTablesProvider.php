<?php namespace Model\LinkedTables;

use Model\ProvidersFinder\AbstractProvider;

abstract class AbstractLinkedTablesProvider extends AbstractProvider
{
	abstract public static function tables(): array;
}
