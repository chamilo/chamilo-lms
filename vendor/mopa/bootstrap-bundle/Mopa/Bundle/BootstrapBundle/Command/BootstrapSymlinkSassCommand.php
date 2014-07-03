<?php

namespace Mopa\Bundle\BootstrapBundle\Command;

/**
 * Command to check and create bootstrap symlink into MopaBootstrapBundle
 */
class BootstrapSymlinkSassCommand extends BaseBootstrapSymlinkCommand
{
    public static $twitterBootstrapName = "jlong/sass-twitter-bootstrap";
    public static $targetSuffix = '-sass';
    public static $pathName = 'TwitterBootstrapSass';

    protected function getTwitterBootstrapName()
    {
        return self::$twitterBootstrapName;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('mopa:bootstrap:symlink:sass')
            ->setHelp(<<<EOT
The <info>mopa:bootstrap:symlink:sass</info> command helps you checking and symlinking/mirroring the jlong/sass-twitter-bootstrap library.

By default, the command uses composer to retrieve the paths of MopaBootstrapBundle and jlong/sass-twitter-bootstrap in your vendors.

If you want to control the paths yourself specify the paths manually:

php app/console mopa:bootstrap:symlink:sass <comment>--manual</comment> <pathToTwitterBootstrapSass> <pathToMopaBootstrapBundle>

Defaults if installed by composer would be :

pathToTwitterBootstrapSass: ../../../../../../../vendor/jlong/sass-twitter-bootstrap
pathToMopaBootstrapBundle:  vendor/mopa/bootstrap-bundle/Mopa/Bundle/BootstrapBundle/Resources/bootstrap

EOT
            );
    }
}
