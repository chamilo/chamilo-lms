<?php

namespace ChamiloLMS\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use ChamiloLMS\CoreBundle\Migrations\Data\ORM\LoadAdminUserData;
//use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Sylius\Bundle\SettingsBundle\Manager\SettingsManager;
use Application\Sonata\UserBundle\Entity\User;

class SetupStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        $form = $this->createForm('chamilo_installer_setup');

        /** @var ConfigManager $configManager */
        //$configManager = $this->get('oro_config.global');
        //$form->get('company_name')->setData($configManager->get('oro_ui.application_name'));
        //$form->get('company_title')->setData($configManager->get('oro_ui.application_title'));

        return $this->render(
            'ChamiloLMSInstallerBundle:Process/Step:setup.html.twig',
            array(
                'form' => $this->createSetupForm()->createView()
            )
        );
    }

    public function forwardAction(ProcessContextInterface $context)
    {
        $adminUser = $this
            ->getDoctrine()
            ->getRepository('ApplicationSonataUserBundle:User')
            ->findOneBy(array('username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME));

        if (!$adminUser) {
            throw new \RuntimeException("Admin user wasn't loaded in fixtures.");
        }

        $form = $this->createSetupForm();
        $form->get('admin')->setData($adminUser);

        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            // pass "load demo fixtures" flag to the next step
            /*$context->getStorage()->set(
                'loadFixtures',
                $form->has('loadFixtures') && $form->get('loadFixtures')->getData()
            );*/

            $this->get('fos_user.user_manager')->updateUser($adminUser);

            // update company name and title if specified
            /** @var SettingsManager $settingsManager */
            $settingsManager = $this->get('sylius.settings.manager');
            $settings = $settingsManager->loadSettings('platform');

            $parameters = array(
                'institution' => $form->get('portal')->get('institution')->getData(),
                'institution_url' => $form->get('portal')->get('institution_url')->getData(),
                'site_name' => $form->get('portal')->get('site_name')->getData(),
                'administrator_email' => $adminUser->getEmail(),
                'administrator_name' => $adminUser->getName(),
                'administrator_surname' => $adminUser->getLastName(),
                'administrator_phone' => $adminUser->getPhone()
            );
            $settings->setParameters($parameters);

            $settingsManager->saveSettings('platform', $settings);

            /*$defaultCompanyName  = $configManager->get('oro_ui.application_name');
            $defaultCompanyTitle = $configManager->get('oro_ui.application_title');
            $companyName         = $form->get('company_name')->getData();
            $companyTitle        = $form->get('company_title')->getData();
            if (!empty($companyName) && $companyName !== $defaultCompanyName) {
                $configManager->set('oro_ui.application_name', $companyName);
            }
            if (!empty($companyTitle) && $companyTitle !== $defaultCompanyTitle) {
                $configManager->set('oro_ui.application_title', $companyTitle);
            }
            $configManager->flush();
            */
            return $this->complete();
        }

        return $this->render(
            'ChamiloLMSInstallerBundle:Process/Step:setup.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    protected function createSetupForm()
    {
        $data = $this->get('chamilo_installer.yaml_persister')->parse();

        return $this->createForm('chamilo_installer_setup', empty($data) ? null : $data);
    }

}
