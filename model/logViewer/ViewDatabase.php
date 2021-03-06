<?php

namespace c00\logViewer;


use c00\common\CovleDate;
use c00\log\channel\sql\Database;
use c00\log\channel\sql\LogQuery;
use c00\log\channel\sql\SqlSettings;
use c00\QueryBuilder\Qry;

class ViewDatabase extends Database {
	const TABLE_BAG_EXTENDED = 'bag_extended';

	public static function new(SqlSettings $config): ViewDatabase
	{
		$db = new ViewDatabase($config);

		if (!$db->isConnected()) throw LogViewerException::new("Can't connect to database");

		return $db;
	}

	private function checkView() {
		if (!$this->hasTable($this->getTable(self::TABLE_BAG_EXTENDED))) {
			//Create view

			$sql = "CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `log_v2_bag_extended` AS select `log_v2_bag`.`id` AS `id`,`log_v2_bag`.`ip` AS `ip`,`log_v2_bag`.`verb` AS `verb`,`log_v2_bag`.`userId` AS `userId`,`log_v2_bag`.`date` AS `date`,substring_index(`log_v2_bag`.`url`,'?',1) AS `url`,if(locate('?',`log_v2_bag`.`url`) = 0,NULL,substring_index(`log_v2_bag`.`url`,'?',-1)) AS `path` from `log_v2_bag`;";
			$this->db->exec($sql);
		}
	}

	public function getTags() {
		$q = Qry::select('tag', true)
			->from($this->getTable(self::TABLE_ITEM))
			;

		return $this->getValues($q);
	}

	public function getLevelStats(LogQuery $query) {
		$q = Qry::select('i.level')
				->count('i.id', 'itemCount', 'DISTINCT')
				->count('b.id', 'bagCount', 'DISTINCT')
				->from(['b' => $this->getTable(self::TABLE_BAG)])
				->join(['i' => $this->getTable(self::TABLE_ITEM)], 'b.id', '=', 'i.bagId')
			->groupBy('i.level')
		;

		if ($query->since) $q->where('b.date', '>', $query->since->toSeconds());
		if ($query->until) $q->where('b.date', '<', $query->until->toSeconds());

		if ($query->levels) $q->whereIn('i.level', $query->levels);


		return $this->getRows($q);

	}

	public function getUrlStats(LogQuery $query) {
		$this->checkView();

		$q = Qry::select(['b.verb', 'b.url', 'i.level'])
				->count('i.id', 'itemCount', 'DISTINCT')
				->count('b.id', 'bagCount', 'DISTINCT')
				->from(['b' => $this->getTable(self::TABLE_BAG_EXTENDED)])
				->join(['i' => $this->getTable(self::TABLE_ITEM)], 'b.id', '=', 'i.bagId')
				->groupBy(['b.verb', 'b.url', 'i.level'])
		;

		if ($query->since) $q->where('b.date', '>', $query->since->toSeconds());
		if ($query->until) $q->where('b.date', '<', $query->until->toSeconds());

		if ($query->levels) $q->whereIn('i.level', $query->levels);

		return $this->getRows($q);

	}

	public function getCount(LogQuery $query): int
	{
		$q = Qry::select()
			->count('b.id', null, 'DISTINCT')
			->from(['b' => $this->getTable(self::TABLE_BAG)])
			->join(['i' => $this->getTable(self::TABLE_ITEM)], 'b.id', '=', 'i.bagId')
			;

		if ($query->since){
			$q->where('b.date', '>', $query->since->toSeconds());
		}

		if ($query->until){
			$q->where('b.date', '<', $query->until->toSeconds());
		}

		if ( count($query->levels) > 0){
			$q->whereIn('i.level', $query->levels);
		}

		if (count($query->tags) > 0) {
			$q->whereIn('i.tag', $query->tags);
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