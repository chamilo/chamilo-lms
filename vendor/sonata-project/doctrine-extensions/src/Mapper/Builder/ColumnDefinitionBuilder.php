<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Doctrine\Mapper\Builder;

final class ColumnDefinitionBuilder
{
    /**
     * @var array<string, mixed>
     */
    private $options = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function add(string $key, $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
