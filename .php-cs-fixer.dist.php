<?php

$header = '/* For licensing terms, see /license.txt */';

$rules = [
    '@Symfony' => true,
    //'@Symfony:risky' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    /*'header_comment' => [
        'header' => $header,
    ],*/
    'blank_line_after_opening_tag' => false,
    'no_extra_blank_lines' => true,
    'multiline_comment_opening_closing' => true,
    'yoda_style' => false,
    'phpdoc_to_comment' => false,
    'phpdoc_no_package' => false,
    'phpdoc_annotation_without_dot' => false,
    'increment_style' => ['style' => 'post'],
    'no_useless_else' => false,
    //'no_php4_constructor' => true,
    'single_quote' => false,
    'no_useless_return' => true,
    'ordered_class_elements' => true,
    'ordered_imports' => true,
    'phpdoc_order' => true,
    'no_break_comment' => true,
    //'@PHP56Migration' => true,
    //'@PHP56Migration:risky' => true,
    //'@PHPUnit57Migration:risky' => true,
    // To be tested before insertion:
//    'strict_comparison' => true,
//    'strict_param' => true,
//    'php_unit_strict' => true,
];

$finder = PhpCsFixer\Finder::create()
    ->exclude('app/')
    ->exclude('node_modules/')
    ->exclude('assets')
    ->exclude('bin')
    ->exclude('documentation')
    ->exclude('main/auth/cas/lib')
    ->exclude('main/auth/shibboleth')
    ->exclude('main/auth/openid')
    ->exclude('main/default_course_document')
    ->exclude('main/fonts')
    ->exclude('main/inc/lib/browser')
    ->exclude('main/inc/lib/freemindflashbrowser')
    ->exclude('main/inc/lib/internationalization_database')
    ->exclude('main/inc/lib/javascript')
    ->exclude('main/inc/lib/kses-0.2.2')
    ->exclude('main/inc/lib/mimetex')
    ->exclude('main/inc/lib/nanogong')
    ->exclude('main/inc/lib/nusoap')
    ->exclude('main/inc/lib/opengraph')
    ->exclude('main/inc/lib/ppt2png')
    ->exclude('main/inc/lib/phpseclib')
    ->exclude('main/inc/lib/pear')
    ->exclude('main/inc/lib/svg-edit')
    ->exclude('main/inc/lib/swfobject')
    ->exclude('main/inc/lib/wami-recorder')
    ->exclude('main/inc/lib/xajax')
    ->exclude('main/lp/packaging')
    ->exclude('main/template')
    ->exclude('main/img')
    ->exclude('main/lang')
    ->exclude('plugin/buycourses/src/Culqi')
    ->exclude('plugin/buycourses/src/Requests')
    ->exclude('plugin/vchamilo/cli')
    ->exclude('plugin/kannelsms/vendor')
    ->exclude('plugin/clockworksms/vendor')
    ->exclude('plugin/pens/lib')
    ->exclude('plugin/bbb/lib')
    ->exclude('plugin/ims_lti')
    ->exclude('plugin/sepe/src/wsse')
    ->exclude('plugin/test2pdf/class')
    ->exclude('plugin/jcapture/src')
    ->exclude('plugin/jcapture/lib')
    ->exclude('tests')
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('web')

    ->notPath('main/admin/db.php')
    ->notPath('main/admin/ldap_synchro.php')
    ->notPath('main/chat/emoji_strategy.php')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules(
        $rules
    )
    ->setFinder($finder);