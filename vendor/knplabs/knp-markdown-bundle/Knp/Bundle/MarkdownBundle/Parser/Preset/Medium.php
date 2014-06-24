<?php

namespace Knp\Bundle\MarkdownBundle\Parser\Preset;

use Knp\Bundle\MarkdownBundle\Parser\MarkdownParser;

/**
 * Medium featured Markdown Parser
 */
class Medium extends MarkdownParser
{
    /**
     * @var array Enabled features
     */
    protected $features = array(
        'header' => true,
        'list' => true,
        'horizontal_rule' => true,
        'table' => false,
        'foot_note' => true,
        'fenced_code_block' => true,
        'abbreviation' => true,
        'definition_list' => false,
        'inline_link' => true, // [link text](url "optional title")
        'reference_link' => true, // [link text] [id]
        'shortcut_link' => true, // [link text]
        'html_block' => false,
        'block_quote' => false,
        'code_block' => true,
        'auto_link' => true,
        'auto_mailto' => false,
        'entities' => false,
        'no_html' => false,
    );
}
