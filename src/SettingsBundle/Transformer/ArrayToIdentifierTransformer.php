<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\SettingsBundle\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Object to identifier transformer.
 *
 * @author Julio Montoya
 */
class ArrayToIdentifierTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!is_array($value)) {
            return '';
        }

        $value = implode(',', $value);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (empty($value)) {
            return [];
        }

        $value = explode(',', $value);

        return $value;
    }
}
