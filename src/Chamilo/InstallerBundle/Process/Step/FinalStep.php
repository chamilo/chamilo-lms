<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

/**
 * Class FinalStep
 * @package Chamilo\InstallerBundle\Process\Step
 */
class FinalStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        $this->complete();

        return $this->render('ChamiloInstallerBundle:Process/Step:final.html.twig');
    }
}
