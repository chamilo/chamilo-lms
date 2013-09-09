<?php

/*
 * This file is part of Filesystem Service Provider.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Neutron\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['filesystem'] = $app->share(function(Application $app) {
            return new Filesystem();
        });
    }

    public function boot(Application $app)
    {
    }
}
