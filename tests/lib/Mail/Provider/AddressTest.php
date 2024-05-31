<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @author Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @license AGPL-3.0-or-later
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

namespace Test\Mail\Provider;

use OCP\Mail\Provider\Address;
use Test\TestCase;

class AddressTest extends TestCase {

	/** @var Address*/
	private $address;

	protected function setUp(): void {
		parent::setUp();

		$this->address = new Address('user1@testing.com', 'User One');

	}

	public function testAddress(): void {
		
		// test set by constructor
        $this->assertEquals('user1@testing.com', $this->address->getAddress());
		// test set by setter
		$this->address->setAddress('user2@testing.com');
        $this->assertEquals('user2@testing.com', $this->address->getAddress());

	}

	public function testLabel(): void {
		
		// test set by constructor
        $this->assertEquals('User One', $this->address->getLabel());
		// test set by setter
		$this->address->setLabel('User Two');
        $this->assertEquals('User Two', $this->address->getLabel());

	}

}
