<?php

namespace Knp\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;

class Command extends BaseCommand
{
    public function getSilexApplication()
    {
        return $this->getApplication()->getSilexApplication();
    }

    public function getProjectDirectory()
    {
        return $this->getApplication()->getProjectDirectory();
    }
}