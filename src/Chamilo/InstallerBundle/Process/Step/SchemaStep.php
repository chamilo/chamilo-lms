<?php

namespace Chamilo\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

class SchemaStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        set_time_limit(600);

        switch ($this->getRequest()->query->get('action')) {
            case 'cache':
                // suppress warning: ini_set(): A session is active. You cannot change the session
                // module's ini settings at this time
                error_reporting(E_ALL ^ E_WARNING);
                return $this->handleAjaxAction('cache:clear', array('--no-optional-warmers' => true));
            /*case 'clear-config':
                return $this->handleAjaxAction('oro:entity-config:cache:clear', array('--no-warmup' => true));*/
            /*case 'clear-extend':
                return $this->handleAjaxAction('oro:entity-extend:cache:clear', array('--no-warmup' => true));*/
            case 'schema-drop':
                return $this->handleAjaxAction(
                    'doctrine:schema:drop',
                    array('--force' => true, '--full-database' => $context->getStorage()->get('fullDatabase', false))
                );
            case 'schema-update':
                return $this->handleAjaxAction('oro:migration:load', array('--force' => true));
            case 'fixtures':
                return $this->handleAjaxAction(
                    'oro:migration:data:load',
                    array('--no-interaction' => true)
                );
            /*case 'workflows':
                return $this->handleAjaxAction('oro:workflow:definitions:load');
            case 'processes':
                return $this->handleAjaxAction('oro:process:configuration:load');*/
        }

        return $this->render('ChamiloInstallerBundle:Process/Step:schema.html.twig');
    }
}
