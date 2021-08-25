<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Mapping;

use Xabbuh\XApi\Model\Extensions as ExtensionsModel;
use Xabbuh\XApi\Model\IRI;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Extensions
{
    public $identifier;
    public $extensions;

    public static function fromModel(ExtensionsModel $model)
    {
        $extensions = new self();
        $extensions->extensions = array();

        foreach ($model->getExtensions() as $key) {
            $extensions->extensions[$key->getValue()] = $model[$key];
        }

        return $extensions;
    }

    public function getModel()
    {
        $extensions = new \SplObjectStorage();

        foreach ($this->extensions as $key => $extension) {
            $extensions->attach(IRI::fromString($key), $extension);
        }

        return new ExtensionsModel($extensions);
    }
}
