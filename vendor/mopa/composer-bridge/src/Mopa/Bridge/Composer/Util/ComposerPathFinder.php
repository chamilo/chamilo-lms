<?php
namespace Mopa\Bridge\Composer\Util;

use Composer\Composer;
use Composer\Package\PackageInterface;

/**
 * ComposerPathFinder get Pathes from one to another package
 */
class ComposerPathFinder
{
    protected $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }
    public function getSymlinkFromComposer($targetPackageName, $sourcePackageName, array $options)
    {
        if (null === $targetPackage = $this->findPackage($targetPackageName)) {
            throw new \Exception("Could not find targetPackage: " . $targetPackageName . ": " . " with composer");
        }
        if (null === $sourcePackage = $this->findPackage($sourcePackageName)) {
            throw new \Exception("Could not find sourcePackage: " . $sourcePackageName . " with composer");
        }
        return $this->generateSymlink($targetPackage, $sourcePackage, $options);
    }

    protected function isPackageInstalled(PackageInterface $package)
    {
        $repo = $this->composer->getRepositoryManager()->getLocalRepository(); 
        $installer = $this->composer->getInstallationManager()
                          ->getInstaller($package->getType());

        return $installer->isInstalled($repo, $package);
    }
    /**
     * return PackageInterface
     */
    public function findPackage($packageName)
    {
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->findPackages($packageName, null);
        foreach ($packages as $package) {
            if ($this->isPackageInstalled($package)) {
                return $package;
            }
        }
    }
    protected function generateSymlink($targetPackage, $sourcePackage, $options)
    {
        $options = array_merge($this->getDefaultOptions(), $options);
        $sourcePackagePath = $this->composer->getInstallationManager()->getInstallPath($sourcePackage);
        $targetPackagePath = $this->composer->getInstallationManager()->getInstallPath($targetPackage);

        $symlinkTarget = realpath($sourcePackagePath);
        $symlinkName = realpath($targetPackagePath);
        // add source prefix
        // win doesnt support relative filenames
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $symlinkTarget = $options['sourcePrefix'] .
                    $this->generateRelativePath($symlinkName, $symlinkTarget);
        }
        // add target suffix
        $symlinkName = $symlinkName . $options['targetSuffix'];

        return array($symlinkTarget, $symlinkName);
    }
    /**
     * borrowed from http://www.php.net/manual/de/function.realpath.php#105876
     */
    public function generateRelativePath($from, $to, $ps = DIRECTORY_SEPARATOR)
    {
        $arFrom = explode($ps, rtrim($from, $ps));
        $arTo = explode($ps, rtrim($to, $ps));
        while (count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0])) {
            array_shift($arFrom);
            array_shift($arTo);
        }

        return str_pad("", count($arFrom) * 3, '..'.$ps).implode($ps, $arTo);
    }
    protected function getDefaultOptions()
    {
        return array(
            'targetSuffix' => "",
            'sourcePrefix' => "",
            'sourceSuffix' => ""
        );
    }
}
