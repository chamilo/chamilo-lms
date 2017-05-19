<?php

/*
 * This file is part of Ghostscript-PHP.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ghostscript;

use Silex\ServiceProviderInterface;
use Silex\Application;

class GhostscriptServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['ghostscript.default.configuration'] = array(
            'gs.binaries' => array('gs'),
            'timeout'     => 60,
        );
        $app['ghostscript.configuration'] = array();
        $app['ghostscript.logger'] = null;

        $app['ghostscript.transcoder'] = $app->share(function(Application $app) {
            $app['ghostscript.configuration'] = array_replace(
                $app['ghostscript.default.configuration'], $app['ghostscript.configuration']
            );

            return Transcoder::create($app['ghostscript.configuration'], $app['ghostscript.logger']);
        });
    }

    public function boot(Application $app)
    {
    }
}
