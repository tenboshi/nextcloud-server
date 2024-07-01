<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB\QueryBuilder\Sharded;

use OC\DB\QueryBuilder\Sharded\InvalidShardedQueryException;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OC\DB\QueryBuilder\Sharded\ShardedQueryBuilder;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class SharedQueryBuilderTest extends TestCase {
	private IDBConnection $connection;
	private SystemConfig $systemConfig;
	private LoggerInterface $logger;

	protected function setUp(): void {
		$this->connection = Server::get(IDBConnection::class);
		$this->systemConfig = Server::get(SystemConfig::class);
		$this->logger = Server::get(LoggerInterface::class);
	}


	private function getQueryBuilder(string $table, string $shardColumn, string $primaryColumn, array $companionTables = []): ShardedQueryBuilder {
		return new ShardedQueryBuilder(
			$this->connection,
			$this->systemConfig,
			$this->logger,
			[
				new ShardDefinition($table, $primaryColumn, $shardColumn, $companionTables),
			],
		);
	}

	public function testGetShardKeySingleParam() {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter(10, IQueryBuilder::PARAM_INT)));

		$this->assertEquals([], $query->getPrimaryKeys());
		$this->assertEquals([10], $query->getShardKeys());
	}

	public function testGetPrimaryKeyParam() {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->in('fileid', $query->createNamedParameter([10, 11], IQueryBuilder::PARAM_INT)));

		$this->assertEquals([10, 11], $query->getPrimaryKeys());
		$this->assertEquals([], $query->getShardKeys());
	}

	public function testValidateWithShardKey() {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter(10)));

		$query->validate();
		$this->assertTrue(true);
	}

	public function testValidateWithPrimaryKey() {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->in('fileid', $query->createNamedParameter([10, 11], IQueryBuilder::PARAM_INT)));

		$query->validate();
		$this->assertTrue(true);
	}

	public function testValidateWithNoKey() {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('fileid', 'path')
			->from('filecache')
			->where($query->expr()->lt('size', $query->createNamedParameter(0)));

		$this->expectException(InvalidShardedQueryException::class);
		$query->validate();
		$this->fail("exception expected");
	}

	public function testValidateNonSharedTable() {
		$query = $this->getQueryBuilder('filecache', 'storage', 'fileid');
		$query->select('configvalue')
			->from('appconfig')
			->where($query->expr()->eq('configkey', $query->createNamedParameter('test')));

		$query->validate();
		$this->assertTrue(true);
	}
}
