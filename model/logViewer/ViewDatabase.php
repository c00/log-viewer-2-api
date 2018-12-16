<?php

namespace c00\logViewer;


use c00\common\CovleDate;
use c00\log\channel\sql\Database;
use c00\log\channel\sql\SqlSettings;
use c00\QueryBuilder\Qry;

class ViewDatabase extends Database {

	public static function new(SqlSettings $config): ViewDatabase
	{
		$db = new ViewDatabase($config);

		if (!$db->isConnected()) throw LogViewerException::new("Can't connect to database");

		return $db;
	}

	public function getTags() {
		$q = Qry::select('tag', true)
			->from($this->getTable(self::TABLE_ITEM))
			;

		return $this->getValues($q);
	}

	public function getCount(CovleDate $since, CovleDate $until = null): int
	{
		$q = Qry::select()
			->count('id')
			->from($this->getTable(self::TABLE_BAG))
			->where('date', '>', $since->toSeconds());

		if ($until) {
			$q->where('date', '<', $until->toSeconds());
		}

		return (int) $this->getValue($q);
	}

	public function getFirstLogDate(): CovleDate
	{
		$q = Qry::select('date')
			->from($this->getTable(self::TABLE_BAG))
			->orderBy('id')
			->limit(1);

		$date = $this->getValue($q);

		if (!$date) $date = 0;

		return CovleDate::fromSeconds($date);
	}

	public function getLastLogDate(): CovleDate
	{
		$q = Qry::select('date')
				->from($this->getTable(self::TABLE_BAG))
				->orderBy('id', false)
				->limit(1);

		$date = $this->getValue($q);

		if (!$date) $date = 0;

		return CovleDate::fromSeconds($date);
	}
}