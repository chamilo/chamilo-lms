<?php

namespace ChamiloLMS\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use ChamiloLMS\CoreBundle\Migrations\Data\ORM\LoadAdminUserData;
//use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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
                'form' => $form->createView()
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

        $form = $this->createForm('chamilo_installer_setup');
        $form->setData($adminUser);

        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            // pass "load demo fixtures" flag to the next step
            $context->getStorage()->set(
                'loadFixtures',
                $form->has('loadFixtures') && $form->get('loadFixtures')->getData()
            );

            $this->get('fos_user.user_manager')->updateUser($adminUser);

            // update company name and title if specified
            /** @var ConfigManager $configManager */
            /*
            $configManager       = $this->get('oro_config.global');
            $defaultCompanyName  = $configManager->get('oro_ui.application_name');
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
}
