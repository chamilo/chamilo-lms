<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Doctrine\Adapter;

interface AdapterInterface
{
    public const ID_SEPARATOR = '~';

    /**
     * Get the identifiers for this model class as a string.
     *
     * @param object $model
     *
     * @return string|null a string representation of the identifiers for this instance
     */
    public function getNormalizedIdentifier($model);

    /**
     * Get the identifiers as a string that is save to use in an url.
     *
     * This is similar to getNormalizedIdentifier but guarantees an id that can
     * be used in an URL.
     *
     * @param object $model
     *
     * @return string|null string representation of the id that is save to use in an url
     */
    public function getUrlSafeIdentifier($model);
}

interface_exists(\Sonata\CoreBundle\Model\Adapter\AdapterInterface::class);
