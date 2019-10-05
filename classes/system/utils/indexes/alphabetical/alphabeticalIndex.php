<?php

	class alphabeticalIndex {

		public static $letters = 'абвгдежзийклмнопрстуфхцчшщыэюяabcdefghijklmnopqrstuvwxyz0123456789';

		protected $selector;

		protected $index;

		public function __construct(selector $sel) {
			$this->selector = $sel;
		}

		public function index($pattern = 'a-zа-я0-9') {
			$index = $this->run();
			$result = [];

			if (preg_match_all('/(([A-zА-я0-9])-([A-zА-я0-9]))/u', $pattern, $out)) {
				for ($i = 0; $i < umiCount($out[2]); $i++) {
					$from = mb_strpos(self::$letters, $out[2][$i]);
					$to = mb_strpos(self::$letters, $out[3][$i]);

					if ($from === false || $to === false) {
						continue;
					}

					for ($j = $from; $j <= $to; $j++) {
						$char = mb_substr(self::$letters, $j, 1);
						$result[$char] = isset($index[$char]) ? $index[$char] : 0;
					}
				}
			}

			return $result;
		}

		protected function run() {
			$mode = $this->selector->__get('mode');

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction('Get alphabetical index');

			try {
				$connection->query('DROP TABLE IF EXISTS `alphabetical_index`');

				$sql = 'CREATE TEMPORARY TABLE `alphabetical_index` (';

				if ($mode == 'pages') {
					$sql .= '`id` int(10) unsigned not null,';
					$sql .= '`pid` int(10) unsigned not null';
				} else {
					$sql .= '`id` int(10) unsigned not null,';
					$sql .= '`name` varchar(255),';
					$sql .= '`type_id` int(10) unsigned not null,';
					$sql .= '`is_locked` tinyint(1) unsigned not null,';
					$sql .= '`owner_id` int(10) unsigned not null,';
					$sql .= '`guid` varchar(64),';
					$sql .= '`type_guid` varchar(64),';
					$sql .= '`updatetime` int(11),';
					$sql .= '`ord` int(10)';
				}

				$sql .= ') ENGINE=MyISAM DEFAULT CHARSET=utf8;';

				$connection->query($sql);

				$query = $this->selector->query();
				$sql = "INSERT INTO `alphabetical_index` {$query}";

				$connection->query($sql);

				if ($mode == 'pages') {
					$sql = <<<SQL
SELECT LEFT(LOWER(`o`.`name`), 1) AS `letter`, COUNT(*) AS `cnt`
	FROM `alphabetical_index` `ai`, `cms3_hierarchy` `h`, `cms3_objects` `o`
	WHERE `h`.`id` = `ai`.`id` AND `o`.`id` = `h`.`obj_id`
	GROUP BY `letter`
	ORDER BY `letter`;
SQL;
				} else {
					$sql = <<<SQL
SELECT LEFT(LOWER(`o`.`name`), 1) AS `letter`, COUNT(*) AS `cnt`
	FROM `alphabetical_index` `ai`, `cms3_objects` `o`
	WHERE `o`.`id` = `ai`.`id`
	GROUP BY `letter`
	ORDER BY `letter`;
SQL;
				}

				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$index = [];

				foreach ($result as $row) {
					list($letter, $count) = $row;
					$index[$letter] = (int) $count;
				}

				$connection->query('DROP TABLE IF EXISTS `alphabetical_index`');
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			return $index;
		}
	}
