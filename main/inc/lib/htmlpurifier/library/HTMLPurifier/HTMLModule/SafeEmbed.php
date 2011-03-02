<?php

/**
 * A "safe" embed module. See SafeObject. This is a proprietary element.
 */
class HTMLPurifier_HTMLModule_SafeEmbed extends HTMLPurifier_HTMLModule
{

    public $name = 'SafeEmbed';

    public function setup($config) {

        $max = $config->get('HTML.MaxImgLength');
        $attr = array(
                'src*' => 'URI#embedded',
                'type' => 'Enum#application/x-shockwave-flash',
                'width' => 'Pixels#' . $max,
                'height' => 'Pixels#' . $max,
                'allowscriptaccess' => 'Enum#never',
                'allownetworking' => 'Enum#internal',
                'flashvars' => 'Text',
                'wmode' => 'Enum#window,transparent,opaque',
                'name' => 'ID',
        );
        if ($config->get('HTML.FlashAllowFullScreen')) {
            $attr['allowfullscreen'] = 'Enum#true,false';
        }

        $embed = $this->addElement(
            'embed', 'Inline', 'Empty', 'Common', $attr
        );
        $embed->attr_transform_post[] = new HTMLPurifier_AttrTransform_SafeEmbed();

    }

}

// vim: et sw=4 sts=4
