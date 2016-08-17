<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Test;

/**
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
abstract class AbstractTypedWriterTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WriterInterface
     */
    private $writer;

    protected function setUp()
    {
        $this->writer = $this->getWriter();
    }

    public function testFormatIsString()
    {
        $this->assertInternalType('string', $this->writer->getFormat());
    }

    public function testDefaultMimeTypeIsString()
    {
        $this->assertInternalType('string', $this->writer->getDefaultMimeType());
    }

    /**
     * Should return a very simple instance of the writer (no need for complex
     * configuration).
     *
     * @return WriterInterface
     */
    abstract protected function getWriter();
}
