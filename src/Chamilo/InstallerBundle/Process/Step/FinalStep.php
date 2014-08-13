<?php

namespace Chamilo\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

class FinalStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        $this->complete();

        return $this->render('ChamiloInstallerBundle:Process/Step:final.html.twig');
    }
}
