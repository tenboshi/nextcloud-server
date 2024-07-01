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

class PartitionQuery {
	const JOIN_MODE_INNER = 'inner';
	const JOIN_MODE_LEFT = 'left';

	public function __construct(
		public IQueryBuilder $query,
		public string $joinFromColumn,
		public string $joinToColumn,
		public string $joinMode,
	) {
		$this->query->select($joinFromColumn);
	}

	/**
	 * @param array $rows
	 * @return array
	 * @throws \OCP\DB\Exception
	 */
	public function mergeWith(array $rows): array {
		// strip table/alias from column names
		$joinFromColumn = preg_replace('/\w+\./', '', $this->joinFromColumn);
		$joinToColumn = preg_replace('/\w+\./', '', $this->joinToColumn);

		$joinToValues = array_map(function (array $row) use ($joinToColumn) {
			return $row[$joinToColumn];
		}, $rows);
		$this->query->andWhere($this->query->expr()->in($this->joinFromColumn, $this->query->createNamedParameter($joinToValues, IQueryBuilder::PARAM_STR_ARRAY)));

		$partitionedRows = $this->query->executeQuery()->fetchAll();
		$partitionedRowsByKey = [];
		foreach ($partitionedRows as $partitionedRow) {
			$partitionedRowsByKey[$partitionedRow[$joinFromColumn]] = $partitionedRow;
		}
		$result = [];
		foreach ($rows as $row) {
			if (isset($partitionedRowsByKey[$row[$joinToColumn]])) {
				$result[] = array_merge($row, $partitionedRowsByKey[$row[$joinToColumn]]);
			} elseif ($this->joinMode === self::JOIN_MODE_LEFT) {
				$result[] = $row;
			}
		}
		return $result;
	}
}
