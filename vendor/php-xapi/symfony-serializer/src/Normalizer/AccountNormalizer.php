<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer\Symfony\Normalizer;

use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\IRL;

/**
 * Normalizes and denormalizes xAPI statement accounts.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class AccountNormalizer extends Normalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$object instanceof Account) {
            return null;
        }

        return array(
            'name' => $object->getName(),
            'homePage' => $object->getHomePage()->getValue(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Account;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $name = '';
        $homePage = '';

        if (isset($data['name'])) {
            $name = $data['name'];
        }

        if (isset($data['homePage'])) {
            $homePage = $data['homePage'];
        }

        return new Account($name, IRL::fromString($homePage));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return 'Xabbuh\XApi\Model\Account' === $type;
    }
}
