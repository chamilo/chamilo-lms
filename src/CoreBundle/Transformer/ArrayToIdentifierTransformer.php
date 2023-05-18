<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Object to identifier transformer.
 *
 * @author Julio Montoya
 */
class ArrayToIdentifierTransformer implements DataTransformerInterface
{
    public function transform($value): string
    {
        if (!\is_array($value)) {
            return '';
        }

        return implode(',', $value);
    }

    public function reverseTransform($value): array
    {
        if (empty($value)) {
            return [];
        }

        return explode(',', $value);
    }
}
