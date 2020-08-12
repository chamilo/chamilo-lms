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

namespace Sonata\Exporter\Test;

use PHPUnit\Framework\TestCase;
use Sonata\Exporter\Writer\TypedWriterInterface;

/**
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
abstract class AbstractTypedWriterTestCase extends TestCase
{
    /**
     * @var TypedWriterInterface
     */
    private $writer;

    protected function setUp(): void
    {
        $this->writer = $this->getWriter();
    }

    final public function testFormatIsString(): void
    {
        $this->assertIsString($this->writer->getFormat());
    }

    final public function testDefaultMimeTypeIsString(): void
    {
        $this->assertIsString($this->writer->getDefaultMimeType());
    }

    abstract protected function getWriter(): TypedWriterInterface;
}
