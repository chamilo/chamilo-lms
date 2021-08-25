<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * Read-only dictionary mapping RFC 5646 language tags to translated strings.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class LanguageMap implements \ArrayAccess, \Countable
{
    private $map;

    /**
     * Creates a language map from the given dictionary.
     *
     * Keys are RFC 5646 language tags and each value is a string in the
     * language specified by the key.
     */
    public static function create(array $map): self
    {
        $languageMap = new self();
        $languageMap->map = $map;

        return $languageMap;
    }

    /**
     * Creates a new language map based on the current map including the given entry.
     *
     * An existing entry will be overridden if the given language tag is already
     * present in this language map.
     */
    public function withEntry(string $languageTag, string $value): self
    {
        $languageMap = clone $this;
        $languageMap->map[$languageTag] = $value;

        return $languageMap;
    }

    /**
     * Returns an unordered list of all language tags being used as keys
     * in this language map.
     *
     * @return string[]
     */
    public function languageTags(): array
    {
        return array_keys($this->map);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($languageTag): bool
    {
        return isset($this->map[$languageTag]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($languageTag): string
    {
        if (!isset($this->map[$languageTag])) {
            throw new \InvalidArgumentException(sprintf('The requested language tag "%s" does not exist in this language map.', $languageTag));
        }

        return $this->map[$languageTag];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($languageTag, $value): void
    {
        throw new \LogicException('The values of a language map cannot be modified. Use withEntry() instead to retrieve a new language map with the added or modified value.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($languageTag): void
    {
        throw new \LogicException('Entries of a language map cannot be removed.');
    }

    public function count(): int
    {
        return count($this->map);
    }

    public function equals(LanguageMap $languageMap): bool
    {
        if (count($this->map) !== count($languageMap->map)) {
            return false;
        }

        foreach ($this->map as $languageTag => $value) {
            if (!isset($languageMap[$languageTag])) {
                return false;
            }

            if ($value !== $languageMap[$languageTag]) {
                return false;
            }
        }

        return true;
    }
}
