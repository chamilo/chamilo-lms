<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus\Media;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
interface MediaInterface
{
    const TYPE_AUDIO = 'Audio';
    const TYPE_IMAGE = 'Image';
    const TYPE_VIDEO = 'Video';
    const TYPE_FLASH = 'Flash';
    const TYPE_DOCUMENT = 'Document';

    /**
     * Return the hash of the `nude` file ; the file is first empty of all its
     * metadatas, then the hash is computed.
     *
     * @see hash_algos()
     *
     * @param type $algo    A valid hash algorithm, see hash_algos function for valid hash names
     *
     * @return string
     */
    public function getHash($algo);

    /**
     * Return the type
     *
     * @return string
     */
    public function getType();

    public function getFile();

    public function getMetadatas();

    public function getLongitude();

    public function getLongitudeRef();

    public function getLatitude();

    public function getLatitudeRef();
}
