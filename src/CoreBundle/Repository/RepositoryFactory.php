<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

class RepositoryFactory
{
    public static function createRepository($class)
    {
        return new $class();
    }
}
