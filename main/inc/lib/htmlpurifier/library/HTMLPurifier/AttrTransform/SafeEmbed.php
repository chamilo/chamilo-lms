<?php

class HTMLPurifier_AttrTransform_SafeEmbed extends HTMLPurifier_AttrTransform
{
    public $name = "SafeEmbed";

    public function transform($attr, $config, $context) {
        $attr['allowscriptaccess'] = 'never';
        $attr['allownetworking'] = 'internal';
        $attr['type'] = 'application/x-shockwave-flash';

        if (!$config->get('HTML.FlashAllowFullScreen') || !$attr['allowfullscreen'] == 'true') {
            unset($attr['allowfullscreen']); // if omitted, assume to be 'false'
        }

        return $attr;
    }
}

// vim: et sw=4 sts=4
