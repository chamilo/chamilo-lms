<?php

namespace Chamilo\CoreBundle\Naming;

use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class UserImage implements DirectoryNamerInterface
{
    /**
     * @inheritdoc
    */
    public function directoryName($object, PropertyMapping $mapping)
    {
    }
}
