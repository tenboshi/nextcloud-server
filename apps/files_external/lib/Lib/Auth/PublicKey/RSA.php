<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External\Lib\Auth\PublicKey;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\StorageConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use phpseclib3\Crypt\RSA as RSACrypt;

/**
 * RSA public key authentication
 */
class RSA extends AuthMechanism {

	/** @var IConfig */
	private $config;

	public function __construct(IL10N $l, IConfig $config) {
		$this->config = $config;

		$this
			->setIdentifier('publickey::rsa')
			->setScheme(self::SCHEME_PUBLICKEY)
			->setText($l->t('RSA public key'))
			->addParameters([
				new DefinitionParameter('user', $l->t('Login')),
				new DefinitionParameter('public_key', $l->t('Public key')),
				(new DefinitionParameter('private_key', 'private_key'))
					->setType(DefinitionParameter::VALUE_HIDDEN),
			])
			->addCustomJs('public_key')
		;
	}

	/**
	 * @return void
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
		try {
			$auth = RSACrypt::loadPrivateKey(
				$storage->getBackendOption('private_key'),
				$this->config->getSystemValue('secret', '')
			);
		} catch (\Throwable) {
			// Add fallback routine for a time where secret was not enforced to be exists
			$auth = RSACrypt::loadPrivateKey($storage->getBackendOption('private_key'));
		}

		$storage->setBackendOption('public_key_auth', $auth);
	}

	/**
	 * Generate a keypair
	 *
	 * @param int $keyLenth
	 */
	public function createKey($keyLength): RSACrypt\PrivateKey {
		$rsa = new RSACrypt();
		
		if ($keyLength !== 1024 && $keyLength !== 2048 && $keyLength !== 4096) {
			$keyLength = 1024;
		}

		return $rsa->createKey($keyLength)
			->withPassword($this->config->getSystemValue('secret', ''));
	}
}
