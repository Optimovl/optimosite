<?php

	class calendarIndex {

		public
			$timeStart, $timeEnd;

		protected
			$selector,
			$index,
			$year, $month;

		public function __construct(selector $sel) {
			$this->selector = $sel;
		}

		public function index($fieldName, $year = null, $month = null) {
			$this->setFieldName($fieldName);

			$this->year = $year ? (int) $year : date('Y');
			$this->month = $month ? (int) $month : date('m');

			$this->timeStart = strtotime($this->year . '-' . $this->month . '-' . 1);
			$this->timeEnd = strtotime($this->year . '-' . ($this->month + 1) . '-' . 1);

			$this->selector->where($fieldName)->between($this->timeStart, $this->timeEnd);
			$index = $this->run();

			$result = [];
			$days = round($this->timeEnd - $this->timeStart) / (3600 * 24);
			for ($i = 1; $i <= $days; $i++) {
				$result[$i] = (int) (isset($index[$i]) ? $index[$i] : 0);
			}

			return [
				'year' => $this->year,
				'month' => $this->month,
				'first-day' => ((int) date('w', $this->timeStart) + 6) % 7,
				'days' => $result
			];
		}

		protected function run() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$mode = $this->selector->__get('mode');

			$connection->startTransaction('Get calendar index');

			try {
				$connection->query('DROP TABLE IF EXISTS `calendar_index`');

				$sql = 'CREATE TABLE `calendar_index` (';
				$sql .= 'id int  unsigned not null,
			`rel_id` int(10) unsigned DEFAULT NULL)';

				$connection->query($sql);
				$query = $this->selector->query();
				$connection->query("INSERT INTO `calendar_index` {$query}");
				$fieldId = $this->fieldId;

				if ($mode == 'pages') {
					$sql = <<<SQL
SELECT
	COUNT(`h`.`id`),
	DATE_FORMAT(FROM_UNIXTIME(`oc`.`int_val`), '%d') as `day`
FROM
	`calendar_index` `tmp`,
	`cms3_objects` `o`,
	`cms3_hierarchy` `h`,
	`cms3_object_content` `oc`
WHERE
	`h`.`id` = `tmp`.`id` AND
	`o`.`id` = `h`.`obj_id` AND
	`oc`.`obj_id` = `o`.`id` AND
	`oc`.`field_id` = '{$fieldId}' AND
	`oc`.`int_val` BETWEEN '{$this->timeStart}' AND '{$this->timeEnd}'
GROUP BY
	`day`
ORDER BY
	`day` ASC
SQL;
				} else {
					$sql = <<<SQL
SELECT
	COUNT(`o`.`id`),
	DATE_FORMAT(FROM_UNIXTIME(`oc`.`int_val`), '%d') as `day`
FROM
	`calendar_index` `tmp`,
	`cms3_objects` `o`,
	`cms3_object_content` `oc`
WHERE
	`o`.`id` = `tmp`.`id` AND
	`oc`.`obj_id` = `o`.`id` AND
	`oc`.`field_id` = '{$fieldId}' AND
	`oc`.`int_val` BETWEEN '{$this->timeStart}' AND '{$this->timeEnd}'
GROUP BY
	`day`
ORDER BY
	`day` ASC
SQL;
				}

				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$index = [];

				foreach ($result as $row) {
					list($count, $day) = $row;
					$index[(int) $day] = $count;
				}

				$connection->query('DROP TABLE IF EXISTS `calendar_index`');
			} catch (Exception $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			return $index;
		}

		protected function setFieldName($fieldName) {
			$fieldId = $this->selector->searchField($fieldName);

			if ($fieldId) {
				$this->fieldId = $fieldId;
			} else {
				throw new coreException("No field \"{$fieldName}\" not found in selector types list");
			}
		}
	}

