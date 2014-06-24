<?php

namespace Knp\Bundle\MarkdownBundle\Tests\Performance;

use Knp\Bundle\MarkdownBundle\Parser\Preset\Max as Parser;

/**
 * Run tests with full-featured Markdown Parser
 */
class Max extends Base
{
    /**
     * @return Parser
     */
    protected function getParser()
    {
        return new Parser();
    }
}
