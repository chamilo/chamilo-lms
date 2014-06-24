<?php

namespace Knp\Bundle\MarkdownBundle\Tests\Performance;

use Knp\Bundle\MarkdownBundle\Parser\MarkdownParser as Parser;

abstract class Base
{
    protected $buffer;

    public function run($iterations = null)
    {
        $this->buffer = '';
        $parser = $this->getParser();
        $iterations = $iterations ? $iterations : $this->getIterations();
        $text = $this->getText();

        $this->output(sprintf('%s : %d chars, %d iterations', get_class($this), strlen($text), $iterations));

        $start = microtime(true);
        for ($it = 1; $it < $iterations; $it++) {
            $parser->transform($text);
        }
        $time = 1000 * (microtime(true) - $start);

        $this->output(sprintf('Unit Time   %01.2f ms.', $time/$iterations));

        return $this->buffer;
    }

    /**
     * @return Parser
     */
    protected abstract function getParser();

    /**
     * @return int
     */
    protected function getIterations()
    {
        return 100;
    }

    /**
     * @return string
     */
    protected function getText()
    {
        return file_get_contents(__DIR__.'/../fixtures/big_text.markdown');
    }

    public function output($text)
    {
        $this->buffer .= $text."\n";
    }

}

