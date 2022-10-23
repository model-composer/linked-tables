<?php namespace Model\LinkedTables;

use Model\DbParser\Table;

abstract class AbstractLinkedTablesProvider
{
	abstract public static function tables(): array;
}
