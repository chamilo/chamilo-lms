<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Images;

use DigitalOcean\Credentials;
use DigitalOcean\Images\ImagesActions;
use DigitalOcean\AbstractDigitalOcean;
use HttpAdapter\HttpAdapterInterface;

/**
 * Images class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class Images extends AbstractDigitalOcean
{
    /**
     * Images API name.
     *
     * @var string
     */
    const IMAGES = 'images';


    /**
     * Constructor.
     *
     * @param Credentials          $credentials The credentials to use.
     * @param HttpAdapterInterface $adapter     The HttpAdapter to use.
     */
    public function __construct(Credentials $credentials, HttpAdapterInterface $adapter)
    {
        parent::__construct($credentials, $adapter);

        $this->apiUrl = sprintf("%s/%s", $this->apiUrl, self::IMAGES);
    }

    /**
     * Returns all the available images that can be accessed by your client ID.
     * You will have access to all public images by default, and any snapshots or backups
     * that you have created in your own account.
     *
     * @return StdClass
     */
    public function getAll()
    {
        return $this->processQuery($this->buildQuery());
    }

    /**
     * Returns all your images.
     *
     * @return StdClass
     */
    public function getMyImages()
    {
        return $this->processQuery($this->buildQuery(
            null, null, array('filter' => ImagesActions::ACTION_FILTER_MY_IMAGES))
        );
    }

    /**
     * Returns all global images.
     *
     * @return StdClass
     */
    public function getGlobal()
    {
        return $this->processQuery($this->buildQuery(
            null, null, array('filter' => ImagesActions::ACTION_FILTER_GLOBAL))
        );
    }

    /**
     * Displays the attributes of an image.
     *
     * @param integer $imageId The id of the image.
     *
     * @return StdClass
     */
    public function show($imageId)
    {
        return $this->processQuery($this->buildQuery($imageId));
    }

    /**
     * Destroys an image.
     * There is no way to restore a deleted image so be careful and ensure your data is properly backed up.
     *
     * @param integer $imageId The id of the image.
     *
     * @return StdClass
     */
    public function destroy($imageId)
    {
        return $this->processQuery($this->buildQuery($imageId, ImagesActions::ACTION_DESTROY_IMAGE));
    }

    /**
     * Transferts a specific image to a specified region.
     * The region_id key is required.
     *
     * @param integer $imageId    The id of the image.
     * @param array   $parameters An array of parameters.
     *
     * @return StdClass
     *
     * @throws \InvalidArgumentException
     */
    public function transfert($imageId, array $parameters)
    {
        if (!array_key_exists('region_id', $parameters) || !is_int($parameters['region_id'])) {
            throw new \InvalidArgumentException('You need to provide an integer "region_id".');
        }

        return $this->processQuery($this->buildQuery($imageId, ImagesActions::ACTION_TRANSFERT, $parameters));
    }
}
