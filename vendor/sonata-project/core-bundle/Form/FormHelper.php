<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Form;

use Symfony\Component\Form\Form;

class FormHelper
{
    /**
     * This function remove fields available if there are not present in the $data array
     * The data array might come from $request->request->all().
     *
     * This can be usefull if you don't want to send all fields will building an api. As missing
     * fields will be threated like null values.
     *
     * @param array $data
     * @param Form  $form
     */
    public static function removeFields(array $data, Form $form)
    {
        $diff = array_diff(array_keys($form->all()), array_keys($data));

        foreach ($diff as $key) {
            $form->remove($key);
        }

        foreach ($data as $name => $value) {
            if (!is_array($value)) {
                continue;
            }

            self::removeFields($value, $form[$name]);
        }
    }
}
