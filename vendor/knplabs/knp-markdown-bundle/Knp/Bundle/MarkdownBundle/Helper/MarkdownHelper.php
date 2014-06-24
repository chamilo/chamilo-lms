<?php

namespace Knp\Bundle\MarkdownBundle\Helper;

use Symfony\Component\Templating\Helper\HelperInterface;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;

class MarkdownHelper implements HelperInterface
{
    /**
     * @var MarkdownParserInterface[]
     */
    private $parsers = array();
    private $charset = 'UTF-8';

    /**
     * @param MarkdownParserInterface $parser
     * @param string                  $alias
     */
    public function addParser(MarkdownParserInterface $parser, $alias)
    {
        $this->parsers[$alias] = $parser;
    }

    /**
     * Transforms markdown syntax to HTML
     *
     * @param string      $markdownText The markdown syntax text
     * @param null|string $parserName
     *
     * @return string                   The HTML code
     *
     * @throws \RuntimeException
     */
    public function transform($markdownText, $parserName = null)
    {
        if (null === $parserName) {
            $parserName = 'default';
        }

        if (!isset($this->parsers[$parserName])) {
            throw new \RuntimeException(sprintf('Unknown parser selected ("%s"), available are: %s', $parserName, implode(', ', array_keys($this->parsers))));
        }

        $parser = $this->parsers[$parserName];

        return $parser->transformMarkdown($markdownText);
    }

    /**
     * Sets the default charset.
     *
     * @param string $charset The charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * Gets the default charset.
     *
     * @return string The default charset
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'markdown';
    }
}
