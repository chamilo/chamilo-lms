<?php

/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PHPExiftoolServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['exiftool.logger'] = $app->share(function() {
            $logger = new Logger('Exiftool Logger');
            $logger->pushHandler(new NullHandler());

            return $logger;
        });

        $app['exiftool.processor'] = $app->share(function(Application $app) {
            return new Exiftool($app['exiftool.logger']);
        });

        $app['exiftool.reader'] = $app->share(function(Application $app) {
            return new Reader($app['exiftool.processor'], new RDFParser());
        });

        $app['exiftool.writer'] = $app->share(function(Application $app) {
            return new Writer($app['exiftool.processor']);
        });

        $app['exiftool.preview-extractor'] = $app->share(function(Application $app) {
            return new PreviewExtractor($app['exiftool.processor']);
        });
    }

    public function boot(Application $app)
    {

    }
}
