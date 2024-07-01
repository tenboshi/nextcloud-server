<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Sharded;

use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\CompositeExpression;
use OC\DB\QueryBuilder\Parameter;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\SystemConfig;
use Psr\Log\LoggerInterface;

class ShardedQueryBuilder extends QueryBuilder {
	private mixed $shardKey = null;
	private mixed $primaryKey = null;
	private ?ShardDefinition $shardDefinition = null;
	/** @var bool Run the query across all shards */
	private bool $allShards = false;

	/**
	 * @param ConnectionAdapter $connection
	 * @param SystemConfig $systemConfig
	 * @param LoggerInterface $logger
	 * @param ShardDefinition[] $shardDefinitions
	 */
	public function __construct(
		ConnectionAdapter $connection,
		SystemConfig      $systemConfig,
		LoggerInterface   $logger,
		private array $shardDefinitions,
	) {
		parent::__construct($connection, $systemConfig, $logger);
	}

	public function getShardKeys(): array {
		return $this->getKeyValue($this->shardKey);
	}

	public function getPrimaryKeys(): array {
		return $this->getKeyValue($this->primaryKey);
	}

	private function getKeyValue($value): array {
		if ($value instanceof Parameter) {
			$value = (string)$value;
		}
		if (is_string($value) && str_starts_with($value, ':')) {
			$param = $this->getParameter(substr($value, 1));
			if (is_array($param)) {
				return $param;
			} else {
				return [$param];
			}
		} elseif ($value !== null) {
			return [$value];
		} else {
			return [];
		}
	}

	public function where(...$predicates) {
		foreach ($predicates as $predicate) {
			$this->tryLoadShardKey($predicate);
		}
		parent::where(...$predicates);
		return $this;
	}

	public function andWhere(...$where) {
		foreach ($where as $predicate) {
			$this->tryLoadShardKey($predicate);
		}
		parent::andWhere(...$where);
		return $this;
	}

	private function tryLoadShardKey($predicate): void {
		if (!$this->shardDefinition) {
			return;
		}
		if ($key = $this->tryExtractShardKey($predicate, $this->shardDefinition->shardKey)) {
			$this->shardKey = $key;
		}
		if ($key = $this->tryExtractShardKey($predicate, $this->shardDefinition->primaryKey)) {
			$this->primaryKey = $key;
		}
	}

	private function tryExtractShardKey($predicate, string $column): ?string {
		if ($predicate instanceof CompositeExpression) {
			// todo extract from composite expressions
			return null;
		}
		$predicate = (string)$predicate;
		// expect a condition in the form of 'alias1.column1 = placeholder' or 'alias1.column1 in placeholder'
		if (substr_count($predicate, ' ') > 2) {
			return null;
		}
		if (str_contains($predicate, ' = ')) {
			$parts = explode(' = ', $predicate);
			if ($parts[0] === "`{$column}`" || str_ends_with($parts[0], "`.`{$column}`")) {
				return $parts[1];
			} else {
				return null;
			}
		}

		if (str_contains($predicate, ' IN ')) {
			$parts = explode(' IN ', $predicate);
			if ($parts[0] === "`{$column}`" || str_ends_with($parts[0], "`.`{$column}`")) {
				return trim(trim($parts[1], '('), ')');
			} else {
				return null;
			}
		}

		return null;
	}

	public function setValue($column, $value) {
		if ($this->shardDefinition) {
			if ($column === $this->shardDefinition->primaryKey) {
				$this->primaryKey = $value;
			}
			if ($column === $this->shardDefinition->shardKey) {
				$this->shardKey = $value;
			}
		}
		return parent::setValue($column, $value);
	}

	public function values(array $values) {
		foreach ($values as $column => $value) {
			$this->setValue($column, $value);
		}
		return $this;
	}

	private function actOnTable(string $table): void {
		foreach ($this->shardDefinitions as $shardDefinition) {
			if ($shardDefinition->table === $table) {
				$this->shardDefinition = $shardDefinition;
			}
		}
	}

	public function from($from, $alias = null) {
		if ($from) {
			$this->actOnTable($from);
		}
		return parent::from($from, $alias);
	}

	public function update($update = null, $alias = null) {
		if ($update) {
			$this->actOnTable($update);
		}
		return parent::update($update, $alias);
	}

	public function insert($insert = null) {
		if ($insert) {
			$this->actOnTable($insert);
		}
		return parent::insert($insert);
	}

	public function delete($delete = null, $alias = null) {
		if ($delete) {
			$this->actOnTable($delete);
		}
		return parent::delete($delete, $alias);
	}

	private function checkJoin(string $table): void {
		if ($this->shardDefinition) {
			if (!$this->shardDefinition->hasTable($table)) {
				throw new InvalidShardedQueryException("Sharded query on {$this->shardDefinition->table} isn't allowed to join on $table");
			}
		}
	}

	public function innerJoin($fromAlias, $join, $alias, $condition = null) {
		$this->checkJoin($join);
		return parent::innerJoin($fromAlias, $join, $alias, $condition);
	}

	public function leftJoin($fromAlias, $join, $alias, $condition = null) {
		$this->checkJoin($join);
		return parent::leftJoin($fromAlias, $join, $alias, $condition);
	}

	public function rightJoin($fromAlias, $join, $alias, $condition = null) {
		if ($this->shardDefinition) {
			throw new InvalidShardedQueryException("Sharded query on {$this->shardDefinition->table} isn't allowed to right join");
		}
		return parent::rightJoin($fromAlias, $join, $alias, $condition);
	}

	public function join($fromAlias, $join, $alias, $condition = null) {
	/**
	 * @throws InvalidShardedQueryException
	 */
		return $this->innerJoin($fromAlias, $join, $alias, $condition);
	}

	public function runAcrossAllShards() {
		$this->allShards = true;
		return $this;
	}

	/**
	 * @throws InvalidShardedQueryException
	 */
	public function validate(): void {
		if ($this->shardDefinition && !$this->allShards) {
			if (empty($this->getShardKeys()) && empty($this->getPrimaryKeys())) {
				throw new InvalidShardedQueryException("No shard key or primary key set for query");
			}
		}
	}

	public function execute() {
		$this->validate();
		return parent::execute();
	}
}
