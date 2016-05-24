<?php

/*
 * This file is part of PHP-SwfTools.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwfTools;

use SwfTools\Binary\DriverContainer;
use Silex\Application;
use Silex\ServiceProviderInterface;
use SwfTools\Processor\FlashFile;
use SwfTools\Processor\PDFFile;

class SwfToolsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['swftools.default.configuration'] = array(
            'pdf2swf.binaries'    => 'pdf2swf',
            'swfrender.binaries'  => 'swfrender',
            'swfextract.binaries' => 'swfextract',
            'timeout'             => 60,
        );
        $app['swftools.configuration'] = array();
        $app['swftools.logger'] = null;

        $app['swftools.driver-container'] = $app->share(function(Application $app) {
            $app['swftools.configuration'] = array_replace(
                $app['swftools.default.configuration'], $app['swftools.configuration']
            );

            return DriverContainer::create($app['swftools.configuration'], $app['swftools.logger']);
        });

        $app['swftools.pdf-file'] = $app->share(function(Application $app) {
            return new PDFFile($app['swftools.driver-container']);
        });

        $app['swftools.flash-file'] = $app->share(function(Application $app) {
            return new FlashFile($app['swftools.driver-container']);
        });
    }

    public function boot(Application $app)
    {
    }
}
