<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber\Steps;

	/**
	 * Class ObjectsFields шаг сбора ссылок из строковых полей объектов
	 * @package UmiCms\Classes\System\Utils\Links\Grabber\Steps
	 */
	class ObjectsFields extends ObjectsNames {

		/** @const string STEP_NAME имя шага */
		const STEP_NAME = 'ObjectsFields';

		/** @inheritdoc */
		public function getName() {
			return self::STEP_NAME;
		}

		/** @inheritdoc */
		public function grab() {
			if ($this->isComplete()) {
				return $this;
			}

			$limit = (int) $this->getLimit();
			$offset = (int) $this->getOffset();

			$connection = $this->getConnection();
			$sql = <<<SQL
SELECT
	`obj_id`,
	`varchar_val`,
	`text_val`
FROM
	`cms3_object_content`
WHERE
	`varchar_val` IS NOT NULL
OR
	`text_val` IS NOT NULL
LIMIT
	$offset, $limit;
SQL;
			$result = $connection->queryResult($sql);
			$objectFieldsLinks = [];

			if ($result->length() == 0) {
				$this->setResult($objectFieldsLinks)
					->setCompleteStatus(true);
			}

			foreach ($result as $row) {
				$varchar = trim($row['varchar_val']);
				$varcharUrls = $this->parseUrlsFromString($varchar);

				$text = trim($row['text_val']);
				$textUrls = $this->parseUrlsFromText($text);

				$urls = array_merge($varcharUrls, $textUrls);

				if (umiCount($urls) === 0) {
					continue;
				}

				$id = $row['obj_id'];
				$linkToEdit = $this->getObjectEditLinkByObjectId($id);

				if (isset($objectFieldsLinks[$linkToEdit])) {
					foreach ($objectFieldsLinks[$linkToEdit] as $url) {
						$urls[] = $url;
					}
				}

				$objectFieldsLinks[$linkToEdit] = $urls;
			}

			return $this->setResult($objectFieldsLinks)
				->setOffset($offset + $limit);
		}
	}
