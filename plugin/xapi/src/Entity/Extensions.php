<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\PluginBundle\Entity\XApi;

use Doctrine\ORM\Mapping as ORM;
use Xabbuh\XApi\Model\Extensions as ExtensionsModel;
use Xabbuh\XApi\Model\IRI;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * @ORM\Table(name="xapi_extensions")
 * @ORM\Entity()
 */
class Extensions
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $identifier;
    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    public $extensions;

    /**
     * @param \Xabbuh\XApi\Model\Extensions $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\Extensions
     */
    public static function fromModel(ExtensionsModel $model)
    {
        $extensions = new self();
        $extensions->extensions = array();

        foreach ($model->getExtensions() as $key) {
            $extensions->extensions[$key->getValue()] = $model[$key];
        }

        return $extensions;
    }

    /**
     * @return \Xabbuh\XApi\Model\Extensions
     */
    public function getModel()
    {
        $extensions = new \SplObjectStorage();

        foreach ($this->extensions as $key => $extension) {
            $extensions->attach(IRI::fromString($key), $extension);
        }

        return new ExtensionsModel($extensions);
    }
}
