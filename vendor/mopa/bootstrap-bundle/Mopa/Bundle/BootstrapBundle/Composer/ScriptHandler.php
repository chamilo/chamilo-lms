<?php
/**
 * Script for composer, to symlink bootstrap lib into Bundle
 *
 * Maybe nice to convert this to a command and then reuse command in here.
 */
namespace Mopa\Bundle\BootstrapBundle\Composer;

use Composer\Script\Event;
use Mopa\Bridge\Composer\Util\ComposerPathFinder;
use Mopa\Bundle\BootstrapBundle\Command\BootstrapSymlinkLessCommand;
use Mopa\Bundle\BootstrapBundle\Command\BootstrapSymlinkSassCommand;

class ScriptHandler
{

    protected static function getTargetSuffix($end = "")
    {
        return DIRECTORY_SEPARATOR . "Resources" . DIRECTORY_SEPARATOR . "public". DIRECTORY_SEPARATOR . "bootstrap" . $end;
    }
    public static function postInstallSymlinkTwitterBootstrap(Event $event)
    {
        $IO = $event->getIO();
        $composer = $event->getComposer();
        $cmanager = new ComposerPathFinder($composer);
        $options = array(
            'targetSuffix' => self::getTargetSuffix(),
            'sourcePrefix' => '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        );
        list($symlinkTarget, $symlinkName) = $cmanager->getSymlinkFromComposer(
            BootstrapSymlinkLessCommand::$mopaBootstrapBundleName,
            BootstrapSymlinkLessCommand::$twitterBootstrapName,
            $options
        );

        $IO->write("Checking Symlink", FALSE);
        if (false === BootstrapSymlinkLessCommand::checkSymlink($symlinkTarget, $symlinkName, true)) {
            $IO->write("Creating Symlink: " . $symlinkName, FALSE);
            BootstrapSymlinkLessCommand::createSymlink($symlinkTarget, $symlinkName);
        }
        $IO->write(" ... <info>OK</info>");
    }

    public static function postInstallMirrorTwitterBootstrap(Event $event)
    {
        $IO = $event->getIO();
        $composer = $event->getComposer();
        $cmanager = new ComposerPathFinder($composer);
        $options = array(
            'targetSuffix' =>  self::getTargetSuffix(),
            'sourcePrefix' => '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        );
        list($symlinkTarget, $symlinkName) = $cmanager->getSymlinkFromComposer(
            BootstrapSymlinkLessCommand::$mopaBootstrapBundleName,
            BootstrapSymlinkLessCommand::$twitterBootstrapName,
            $options
        );

        $IO->write("Checking Mirror", FALSE);
        if (false === BootstrapSymlinkLessCommand::checkSymlink($symlinkTarget, $symlinkName)) {
            $IO->write("Creating Mirror: " . $symlinkName, FALSE);
            BootstrapSymlinkLessCommand::createMirror($symlinkTarget, $symlinkName);
        }
        $IO->write(" ... <info>OK</info>");
    }

    public static function postInstallSymlinkTwitterBootstrapSass(Event $event)
    {
        $IO = $event->getIO();
        $composer = $event->getComposer();
        $cmanager = new ComposerPathFinder($composer);
        $options = array(
            'targetSuffix' =>  self::getTargetSuffix('-sass'),
            'sourcePrefix' => '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        );
        list($symlinkTarget, $symlinkName) = $cmanager->getSymlinkFromComposer(
            BootstrapSymlinkSassCommand::$mopaBootstrapBundleName,
            BootstrapSymlinkSassCommand::$twitterBootstrapName,
            $options
        );

        $IO->write("Checking Symlink", FALSE);
        if (false === BootstrapSymlinkSassCommand::checkSymlink($symlinkTarget, $symlinkName, true)) {
            $IO->write(" ... Creating Symlink: " . $symlinkName, FALSE);
            BootstrapSymlinkSassCommand::createSymlink($symlinkTarget, $symlinkName);
        }
        $IO->write(" ... <info>OK</info>");
    }

    public static function postInstallMirrorTwitterBootstrapSass(Event $event)
    {
        $IO = $event->getIO();
        $composer = $event->getComposer();
        $cmanager = new ComposerPathFinder($composer);
        $options = array(
            'targetSuffix' =>  self::getTargetSuffix('-sass'),
            'sourcePrefix' => '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        );
        list($symlinkTarget, $symlinkName) = $cmanager->getSymlinkFromComposer(
            BootstrapSymlinkSassCommand::$mopaBootstrapBundleName,
            BootstrapSymlinkSassCommand::$twitterBootstrapName,
            $options
        );

        $IO->write("Checking Mirror", FALSE);
        if (false === BootstrapSymlinkSassCommand::checkSymlink($symlinkTarget, $symlinkName)) {
            $IO->write(" ... Creating Mirror: " . $symlinkName, FALSE);
            BootstrapSymlinkSassCommand::createMirror($symlinkTarget, $symlinkName);
        }
        $IO->write(" ... <info>OK</info>");
    }
}
