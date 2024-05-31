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

use OCP\Mail\Provider\Attachment;
use Test\TestCase;

class AttachmentTest extends TestCase {

	/** @var Attachment*/
	private $attachment;

	protected function setUp(): void {
		parent::setUp();

		$this->attachment = new Attachment(
			'This is the contents of a file',
			'example1.txt',
			'text/plain',
			false
		);

	}

	public function testName(): void {
		
		// test set by constructor
        $this->assertEquals('example1.txt', $this->attachment->getName());
		// test set by setter
		$this->attachment->setName('example2.txt');
        $this->assertEquals('example2.txt', $this->attachment->getName());

	}

	public function testType(): void {
		
		// test set by constructor
        $this->assertEquals('text/plain', $this->attachment->getType());
		// test set by setter
		$this->attachment->setType('text/html');
        $this->assertEquals('text/html', $this->attachment->getType());

	}

	public function testContents(): void {
		
		// test set by constructor
        $this->assertEquals('This is the contents of a file', $this->attachment->getContents());
		// test set by setter
		$this->attachment->setContents('This is the modified contents of a file');
        $this->assertEquals('This is the modified contents of a file', $this->attachment->getContents());

	}

	public function testEmbedded(): void {
		
		// test set by constructor
        $this->assertEquals(false, $this->attachment->getEmbedded());
		// test set by setter
		$this->attachment->setEmbedded(true);
        $this->assertEquals(true, $this->attachment->getEmbedded());

	}

}
