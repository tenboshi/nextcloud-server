<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 *
 */
class Version30000Date20240101084401 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('sec_signatory')) {
			$table = $schema->createTable('sec_signatory');
			$table->addColumn('id', Types::BIGINT, [
				'notnull' => true,
				'length' => 64,
				'autoincrement' => true,
				'unsigned' => true,
			]);
			// key_id_sum will store a hash version of the key_id, more appropriate for search/index
			$table->addColumn('key_id_sum', Types::STRING, [
				'notnull' => true,
				'length' => 127,
			]);
			$table->addColumn('key_id', Types::STRING, [
				'notnull' => true,
				'length' => 512
			]);
			// host/provider_id/account will help generate a unique entry, not based on key_id
			// this way, a spoofed instance cannot publish a new key_id for same host+provider_id
			// account will be used only to stored multiple keys for the same provider_id/host
			$table->addColumn('host', Types::STRING, [
				'notnull' => true,
				'length' => 127
			]);
			$table->addColumn('provider_id', Types::STRING, [
				'notnull' => true,
				'length' => 31,
			]);
			$table->addColumn('account', Types::STRING, [
				'notnull' => false,
				'length' => 127,
				'default' => ''
			]);
			$table->addColumn('public_key', Types::TEXT, [
				'notnull' => true,
				'default' => ''
			]);
			$table->addColumn('metadata', Types::TEXT, [
				'notnull' => true,
				'default' => '[]'
			]);
			// type+status are informative about the trustability of remote instance and status of the signatory
			$table->addColumn('type', Types::SMALLINT, [
				'notnull' => true,
				'length' => 2,
				'default' => 9
			]);
			$table->addColumn('status', Types::SMALLINT, [
				'notnull' => true,
				'length' => 2,
				'default' => 0,
			]);
			$table->addColumn('creation', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->addColumn('last_updated', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
				'default' => 0,
				'unsigned' => true,
			]);

			$table->setPrimaryKey(['id'], 'sec_sig_id');
			$table->addUniqueIndex(['provider_id', 'host', 'account'], 'sec_sig_unic');
			$table->addIndex(['key_id_sum', 'provider_id'], 'sec_sig_key');

			return $schema;
		}

		return null;
	}
}
