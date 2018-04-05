<?php
/**
 * MenuLoader.php
 * avanzu-admin
 * Date: 24.02.14.
 */

namespace Chamilo\ThemeBundle\Routing;

use Symfony\Component\Config\Loader\Loader;

class MenuLoader extends Loader
{
    /**
     * Loads a resource.
     *
     * @param mixed  $resource The resource
     * @param string $type     The resource type
     */
    public function load($resource, $type = null)
    {
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return true;
    }
}
