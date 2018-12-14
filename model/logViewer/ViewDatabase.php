<?php

namespace c00\logViewer;


use c00\log\channel\sql\Database;
use c00\log\channel\sql\SqlSettings;

class ViewDatabase extends Database {

	public static function new(SqlSettings $config): ViewDatabase {
		$db = new ViewDatabase($config);

		if (!$db->isConnected()) throw LogViewerException::new("Can't connect to database");

		return $db;
	}
}