<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin\Administrator;

use Chamilo\CoreBundle\Controller\BaseController;
use Silex\Application;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Chamilo\CoreBundle\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
//use Guzzle\Http\Client;

/**
 * Class RoleController
 * @todo @route and @method function don't work yet
 * @package Chamilo\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class UpgradeController extends BaseController
{
    /**
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        //$version = api_http_request('version.chamilo.org', 80, '/version.php');
        $version = '11';

        $request = $this->getRequest();
        $builder = $this->createFormBuilder();
        $builder->add('upgrade_chamilo', 'submit');
        $form = $builder->getForm();

        if ($request->getMethod() == 'POST') {
            if ($form->isValid()) {
                $this->get('session')->getFlashBag()->add('success', "Upgrade");
                $url = $this->generateUrl('upgrade.controller:upgradeAction');
                return $this->redirect($url);
            }
        }
        $template = $this->get('template');

        $template->assign('form', $form->createView());
        $template->assign('version', $version);
        $response = $template->render_template($this->getTemplatePath().'index.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @Route("/update-chash")
     * @Method({"GET"})
     */
    public function updateChashAction()
    {
         /** @var \Knp\Console\Application $console */
        $console = $this->get('console');

        $console->addCommands(
            array(
                new \Chash\Command\Chash\SelfUpdateCommand(),
            )
        );

        /** @var \Chash\Command\Chash\SelfUpdateCommand $command */
        $command = $console->get('chash:self-update');
        $def = $command->getDefinition();

        $input = new \Symfony\Component\Console\Input\ArrayInput(
            array(
                'name',
                '--temp-folder' => $this->get('sys_temp_path'),
                '--src-destination' => $this->get('sys_root').'vendor/chamilo/chash'
            )
        );

        $output = new BufferedOutput();
        $command->run($input, $output);
        $outputTostring = $output->getBuffer();

        $this->get('session')->getFlashBag()->add('success', "Updated");
        $this->get('session')->getFlashBag()->add('info', $outputTostring);
        $url = $this->generateUrl('upgrade.controller:indexAction');
        return $this->redirect($url);
    }

    /**
    * @Route("{version}/update")
    * @Method({"GET"})
    */
    public function upgradeAction($version)
    {
        /** @var \Knp\Console\Application $console */
        $console = $this->get('console');

        $console->addCommands(
            array(
               // DBAL Commands.
                new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
                new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),

                // Migrations Commands.
                new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
                new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
                new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
                new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
                new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
                new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),

                new \Chash\Command\Chash\SetupCommand(),

                new \Chash\Command\Database\RunSQLCommand(),
                new \Chash\Command\Database\DumpCommand(),
                new \Chash\Command\Database\RestoreCommand(),
                new \Chash\Command\Database\SQLCountCommand(),
                new \Chash\Command\Database\FullBackupCommand(),
                new \Chash\Command\Database\DropDatabaseCommand(),
                new \Chash\Command\Database\ShowConnInfoCommand(),

                new \Chash\Command\Files\CleanDataFilesCommand(),
                new \Chash\Command\Files\CleanTempFolderCommand(),
                new \Chash\Command\Files\CleanConfigFilesCommand(),
                new \Chash\Command\Files\MailConfCommand(),
                new \Chash\Command\Files\SetPermissionsAfterInstallCommand(),
                new \Chash\Command\Files\GenerateTempFileStructureCommand(),

                new \Chash\Command\Installation\InstallCommand(),
                new \Chash\Command\Installation\WipeCommand(),
                new \Chash\Command\Installation\StatusCommand(),
                new \Chash\Command\Installation\UpgradeCommand()

            )
        );

        $helpers = array(
            'configuration' => new \Chash\Helpers\ConfigurationHelper()
        );

        $helperSet = $console->getHelperSet();
        foreach ($helpers as $name => $helper) {
            $helperSet->set($helper, $name);
        }

        $command = $console->get('chamilo:upgrade');

        $version = '1.11.0';

        $def = $command->getDefinition();

        $input = new \Symfony\Component\Console\Input\ArrayInput(
            array(
                'name',
                '--path' => $this->get('sys_root'),
                'version' => $version,
                '--temp-folder' => $this->get('sys_temp_path'),
                '--migration-yml-path' => api_remove_trailing_slash($this->get('sys_temp_path')),
                '--migration-class-path' => $this->get('sys_root').'vendor/chamilo/chash/src/Chash/Migrations',
                '--download-package' => 'true',
                '--silent' => 'true'
            ),
            $def
        );

        $output = new BufferedOutput();
        $result = $command->run($input, $output);
        if ($result) {

        }
        $output = $output->getBuffer();

        $template = $this->get('template');
        $template->assign('output', $output);
        $response = $template->render_template($this->getTemplatePath().'upgrade.tpl');

        return new Response($response, 200, array());
    }

    /*protected function getControllerAlias()
    {
        return 'upgrade.controller';
    }*/

    /**
    * {@inheritdoc}
    */
    public function getTemplatePath()
    {
        return 'admin/administrator/upgrade/';
    }
}
