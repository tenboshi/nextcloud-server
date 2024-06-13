<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\DB\QueryBuilder\Partitioned;

use OCP\DB\QueryBuilder\IQueryBuilder;

class SplitPartition {
	const JOIN_MODE_INNER = 'inner';
	const JOIN_MODE_LEFT = 'left';

	private string $joinFromColumn;
	private string $joinToColumn;

	public function __construct(
		public IQueryBuilder $query,
		string $joinFromColumn,
		string $joinToColumn,
		public string $joinMode,
	) {
		$this->query->select($joinFromColumn);
		// strip table/alias from column names
		$this->joinFromColumn = preg_replace('/\w+\./', '', $joinFromColumn);
		$this->joinToColumn = preg_replace('/\w+\./', '', $joinToColumn);
	}

	/**
	 * @param array $rows
	 * @return array
	 * @throws \OCP\DB\Exception
	 */
	public function mergeWith(array $rows): array {
		$joinToValues = array_map(function (array $row) {
			return $row[$this->joinToColumn];
		}, $rows);
		$this->query->andWhere($this->query->expr()->in($this->joinFromColumn, $this->query->createNamedParameter($joinToValues, IQueryBuilder::PARAM_STR_ARRAY)));

		$partitionedRows = $this->query->executeQuery()->fetchAll();
		$partitionedRowsByKey = [];
		foreach ($partitionedRows as $partitionedRow) {
			$partitionedRowsByKey[$partitionedRow[$this->joinFromColumn]] = $partitionedRow;
		}
		$result = [];
		foreach ($rows as $row) {
			if (isset($partitionedRowsByKey[$row[$this->joinToColumn]])) {
				$result[] = array_merge($row, $partitionedRowsByKey[$row[$this->joinToColumn]]);
			} elseif ($this->joinMode === self::JOIN_MODE_LEFT) {
				$result[] = $row;
			}
		}
		return $result;
	}
}
