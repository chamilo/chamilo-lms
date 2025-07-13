<?php

$rules = [
    '@Symfony' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'blank_line_after_opening_tag' => false,
    'no_extra_blank_lines' => true,
    'multiline_comment_opening_closing' => true,
    'yoda_style' => false,
    'phpdoc_to_comment' => false,
    'phpdoc_no_package' => false,
    'phpdoc_annotation_without_dot' => false,
    'increment_style' => ['style' => 'post'],
    'no_useless_else' => false,
    'single_quote' => false,
    'no_useless_return' => true,
    'ordered_class_elements' => true,
    'ordered_imports' => true,
    'phpdoc_order' => true,
    'no_break_comment' => true,
];

$finder = PhpCsFixer\Finder::create()
    ->exclude('3rdparty')
    ->exclude('assets')
    ->exclude('layout')
    ->exclude('resources')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules(
        $rules
    )
    ->setFinder($finder);