<?php

declare(strict_types=1);

/**
 * @copyright 2024 Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @author Sebastian Krupinski <krupinski01@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CalDAV;

class EventReaderRRule extends \Sabre\VObject\Recur\RRuleIterator {

	public function precision(): string {
		return $this->frequency;
	}

	public function interval(): int {
		return $this->interval;
	}

	public function concludes(): \DateTime | null {
		if (isset($this->until)) {
			return \DateTime::createFromImmutable($this->until);
		} elseif ($this->count > 0) {
			// temporarly store current reccurance date and counter
			$currentReccuranceDate = $this->currentDate;
			$currentCounter = $this->counter;
			// iterate over occurances until last one (subtract 2 from count for start and end occurance)
			while ($this->counter <= ($this->count - 2)) {
				$this->next();
			}
			// temporarly store last reccurance date
			$lastReccuranceDate = $this->currentDate;
			// restore current reccurance date and counter
			$this->currentDate = $currentReccuranceDate;
			$this->counter = $currentCounter;
			// return last recurrance date
			return \DateTime::createFromImmutable($lastReccuranceDate);
		} else {
			return null;
		}
	}

	public function concludesAfter(): int | null {
		return isset($this->count) ? $this->count : null;
	}

	public function concludesOn(): \DateTime | null {
		return isset($this->until) ? \DateTime::createFromImmutable($this->until) : null;
	}

	public function daysOfWeek(): array {
		return $this->byDay;
	}

	public function daysOfMonth(): array {
		return $this->byMonthDay;
	}

	public function daysOfYear(): array {
		return $this->byYearDay;
	}

	public function weeksOfYear(): array {
		return $this->byWeekNo;
	}

	public function monthsOfYear(): array {
		return $this->byMonth;
	}

	public function isRelative(): bool {
		return isset($this->bySetPos);
	}

	public function relativePosition(): array {
		return $this->bySetPos;
	}

}
