<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license AGPL-3.0 or later
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

namespace OCP\ConfigLexicon;

/**
 * Model that represent config values within an app config lexicon.
 *
 * @see IConfigLexicon
 * @since 30.0.0
 */
class ConfigLexiconEntry implements IConfigLexiconEntry {
	private string $definition = '';
	private ?string $default = null;

	/**
	 * @param string $key config key
	 * @param ConfigLexiconValueType $type type of config value
	 * @param string $definition optional description of config key available when using occ command
	 * @param bool $lazy set config value as lazy
	 * @param bool $sensitive set config value as sensitive
	 * @param bool $deprecated set config key as deprecated
	 * @since 30.0.0
	 */
	public function __construct(
		private readonly string $key,
		private readonly ConfigLexiconValueType $type,
		string $definition = '',
		private readonly bool $lazy = false,
		private readonly bool $sensitive = false,
		private readonly bool $deprecated = false
	) {
		/** @psalm-suppress UndefinedClass */
		if (\OC::$CLI) { // only store definition if ran from CLI
			$this->definition = $definition;
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @return string config key
	 * @since 30.0.0
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 * @inheritDoc
	 *
	 * @return ConfigLexiconValueType
	 * @see self::TYPE_STRING and others
	 * @since 30.0.0
	 */
	public function getValueType(): ConfigLexiconValueType {
		return $this->type;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $default
	 *
	 * @return self
	 * @since 30.0.0
	 */
	public function withDefaultString(string $default): self {
		$this->default = $default;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @param int $default
	 *
	 * @return self
	 * @since 30.0.0
	 */
	public function withDefaultInt(int $default): self {
		$this->default = (string) $default;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @param float $default
	 *
	 * @return self
	 * @since 30.0.0
	 */
	public function withDefaultFloat(float $default): self {
		$this->default = (string) $default;
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @param bool $default
	 *
	 * @return self
	 * @since 30.0.0
	 */
	public function withDefaultBool(bool $default): self {
		$this->default = ($default) ? '1' : '0';
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $default
	 *
	 * @return self
	 * @since 30.0.0
	 */
	public function withDefaultArray(array $default): self {
		$this->default = json_encode($default);
		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string|null NULL if no default is set
	 * @since 30.0.0
	 */
	public function getDefault(): ?string {
		return $this->default;
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getDefinition(): string {
		return $this->definition;
	}

	/**
	 * @inheritDoc
	 *
	 * @see IAppConfig for details on lazy config values
	 * @return bool TRUE if config value is lazy
	 * @since 30.0.0
	 */
	public function isLazy(): bool {
		return $this->lazy;
	}

	/**
	 * @inheritDoc
	 *
	 * @see IAppConfig for details on sensitive config values
	 * @return bool TRUE if config value is sensitive
	 * @since 30.0.0
	 */
	public function isSensitive(): bool {
		return $this->sensitive;
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool TRUE if config si deprecated
	 * @since 30.0.0
	 */
	public function isDeprecated(): bool {
		return $this->deprecated;
	}
}
