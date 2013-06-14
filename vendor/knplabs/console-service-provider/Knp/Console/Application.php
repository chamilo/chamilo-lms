<?php

namespace Knp\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Silex\Application as SilexApplication;

class Application extends BaseApplication
{
    private $silexApplication;

    private $projectDirectory;

    public function __construct(SilexApplication $application, $projectDirectory, $name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->silexApplication = $application;
        $this->projectDirectory = $projectDirectory;

        $application->boot();
    }

    public function getSilexApplication()
    {
        return $this->silexApplication;
    }

    public function getProjectDirectory()
    {
        return $this->projectDirectory;
    }
}
