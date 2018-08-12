<?php

/*
 * This file is part of PHP-MP4Box.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MP4Box;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MP4BoxServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['mp4box.default.configuration'] = array(
            'mp4box.binaries' => array('MP4Box'),
            'timeout'         => 60,
        );
        $app['mp4box.configuration'] = array();
        $app['mp4box.logger'] = null;

        $app['mp4box'] = $app->share(function(Application $app) {
            $app['mp4box.configuration'] = array_replace(
                $app['mp4box.default.configuration'], $app['mp4box.configuration']
            );

            return MP4Box::create($app['mp4box.configuration'], $app['mp4box.logger']);
        });
    }

    public function boot(Application $app)
    {
    }
}
