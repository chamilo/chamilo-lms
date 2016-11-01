<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Component;

@trigger_error('The '.__NAMESPACE__.'\NativeSlugify class is deprecated since version 2.3 and will be removed in 4.0.', E_USER_DEPRECATED);

/**
 * Do not use this class, it is only here to be BC.
 *
 * @deprecated
 *
 * @author Thomas Rabaix <thomas.rabaix@gmail.com>
 */
class NativeSlugify
{
    /**
     * @param $text
     *
     * @return mixed|string
     *
     * @deprecated
     */
    public function slugify($text)
    {
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        return $text;
    }
}
