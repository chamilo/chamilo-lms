<?php

namespace Knp\Bundle\MarkdownBundle\Parser\Preset;

/**
 * Copyrights KnpBundle.com
 */
class Flavored extends Max
{
    /**
     * {@inheritDoc}
     */
    public function transformMarkdown($text)
    {
        $types    = array();
        $markdown = preg_replace_callback("@```[ ]*([^\n]*)(.+?)```@smi", function ($m) use (&$types) {
            $types[] = trim($m[1]);

            return '    '.str_replace("\n", "\n    ", trim($m[2], "\r\n"));
        }, parent::transformMarkdown($text));

        return $markdown;
    }
}
