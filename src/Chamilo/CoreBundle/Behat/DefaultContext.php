<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Sylius\Bundle\ResourceBundle\Behat\DefaultContext as BaseDefaultContext;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * Class DefaultContext
 * @package Chamilo\CoreBundle\Behat
 */
abstract class DefaultContext extends BaseDefaultContext
{
    /**
     * Faker.
     *
     * @var Generator
     */
    protected $faker;

    /**
     * Actions.
     *
     * @var array
     */
    protected $actions = array(
        'viewing'  => 'show',
        'creation' => 'create',
        'editing'  => 'update',
        'building' => 'build',
    );

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    /**
     * {@inheritdoc}
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @BeforeScenario
     *
     */
    public function purgeDatabase(BeforeScenarioScope $scope)
    {
        //$output = new ConsoleOutput();

        /*$generator = $this->getContainer()->get('sonata.page.route.page.generator');
        $siteManager = $this->getContainer()->get('sonata.page.manager.site');

        $defaultSite = $siteManager->find(1);

        $generator->update($defaultSite, $output);*/

        /*$entityManager = $this->getService('doctrine.orm.entity_manager');
        $entityManager->getConnection()->executeUpdate("SET foreign_key_checks = 0;");

        $purger = new ORMPurger($entityManager);
        $purger->purge();

        $entityManager->getConnection()->executeUpdate("SET foreign_key_checks = 1;");*/
    }

    /**
     * @BeforeFeature
     */
    public static function setupFeature(BeforeFeatureScope $event)
    {
//
//        $output = new ConsoleOutput();
//
//        $generator = self::getContainer()->get('sonata.page.route.page.generator');
//        $siteManager = self::getContainer()->get('sonata.page.manager.site');
//
//        $defaultSite = $siteManager->find(1);
//
//        $generator->update($defaultSite, $output);

    }

    /**
     * Find one resource by name.
     *
     * @param string $type
     * @param string $name
     *
     * @return object
     */
    protected function findOneByName($type, $name)
    {
        return $this->findOneBy($type, array('name' => trim($name)));
    }

    /**
     * Find one resource by criteria.
     *
     * @param string $type
     * @param array  $criteria
     *
     * @return object
     *
     * @throws \InvalidArgumentException
     */
    protected function findOneBy($type, array $criteria)
    {
        $resource = $this
            ->getRepository($type)
            ->findOneBy($criteria)
        ;

        if (null === $resource) {
            throw new \InvalidArgumentException(
                sprintf('%s for criteria "%s" was not found.', str_replace('_', ' ', ucfirst($type)), serialize($criteria))
            );
        }

        return $resource;
    }

    /**
     * Get repository by resource name.
     *
     * @param string $resource
     *
     * @return RepositoryInterface
     */
    /*protected function getRepository($resource)
    {
        return $this->getService('sylius.repository.'.$resource);
    }*/

    /**
     * Get entity manager.
     *
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine')->getManager();
    }

    /**
     * Returns Container instance.
     *
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * Get service by id.
     *
     * @param string $id
     *
     * @return object
     */
    protected function getService($id)
    {
        return $this->getContainer()->get($id);
    }

    /**
     * Configuration converter.
     *
     * @param string $configurationString
     *
     * @return array
     */
    protected function getConfiguration($configurationString)
    {
        $configuration = array();
        $list = explode(',', $configurationString);

        foreach ($list as $parameter) {
            list($key, $value) = explode(':', $parameter);
            $key = strtolower(trim(str_replace(' ', '_', $key)));

            switch ($key) {
                case 'country':
                    $configuration[$key] = $this->getRepository('country')->findOneBy(array('name' => trim($value)))->getId();
                    break;

                case 'taxons':
                    $configuration[$key] = new ArrayCollection(array($this->getRepository('taxon')->findOneBy(array('name' => trim($value)))->getId()));
                    break;

                case 'variant':
                    $configuration[$key] = $this->getRepository('product')->findOneBy(array('name' => trim($value)))->getMasterVariant()->getId();
                    break;

                default:
                    $configuration[$key] = trim($value);
                    break;
            }
        }

        return $configuration;
    }

    /**
     * Generate page url.
     * This method uses simple convention where page argument is prefixed
     * with "sylius_" and used as route name passed to router generate method.
     *
     * @param object|string $page
     * @param array         $parameters
     *
     * @return string
     */
    protected function generatePageUrl($page, array $parameters = array())
    {
        if (is_object($page)) {
            return $this->generateUrl($page, $parameters);
        }
        $route  = str_replace(' ', '_', trim($page));

        return $this->generateUrl($route, $parameters);
    }

    /**
     * Get current user instance.
     *
     * @return null|UserInterface
     *
     * @throws \Exception
     */
    protected function getUser()
    {
        $token = $this->getSecurityContext()->getToken();

        if (null === $token) {
            throw new \Exception('No token found in security context.');
        }

        return $token->getUser();
    }

    /**
     * Get security context.
     *
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->getContainer()->get('security.context');
    }

    /**
     * Generate url.
     *
     * @param string  $route
     * @param array   $parameters
     * @param Boolean $absolute
     *
     * @return string
     */
    protected function generateUrl($route, array $parameters = array(), $absolute = false)
    {
        return $this->locatePath($this->getService('router')->generate($route, $parameters, $absolute));
    }

    /**
     * Presses button with specified id|name|title|alt|value.
     */
    protected function pressButton($button)
    {
        $this->getSession()->getPage()->pressButton($this->fixStepArgument($button));
    }

    /**
     * Clicks link with specified id|title|alt|text.
     */
    protected function clickLink($link)
    {
        $this->getSession()->getPage()->clickLink($this->fixStepArgument($link));
    }

    /**
     * Fills in form field with specified id|name|label|value.
     */
    protected function fillField($field, $value)
    {
        $this->getSession()->getPage()->fillField($this->fixStepArgument($field), $this->fixStepArgument($value));
    }

    /**
     * Selects option in select field with specified id|name|label|value.
     */
    public function selectOption($select, $option)
    {
        $this->getSession()->getPage()->selectFieldOption($this->fixStepArgument($select), $this->fixStepArgument($option));
    }

    /**
     * Returns fixed step argument (with \\" replaced back to ").
     *
     * @param string $argument
     *
     * @return string
     */
    protected function fixStepArgument($argument)
    {
        return str_replace('\\"', '"', $argument);
    }

    /**
     * @return \Sonata\UserBundle\Entity\UserManager
     */
    public function getUserManager()
    {
        return $this->getContainer()->get('fos_user.user_manager');
    }

    /**
     * @return \Sonata\UserBundle\Entity\GroupManager
     */
    public function getGroupManager()
    {
        return $this->getContainer()->get('fos_user.group_manager');
    }

    /**
     * @return \Chamilo\CoreBundle\Entity\Manager\CourseManager
     */
    public function getCourseManager()
    {
        return $this->getContainer()->get('chamilo_core.manager.course');
    }

    /**
     * @return \Chamilo\CoreBundle\Entity\Manager\SessionManager
     */
    public function getSessionManager()
    {
        return $this->getContainer()->get('chamilo_core.manager.session');
    }

    /**
     * Returns the Doctrine repository manager for a given entity.
     *
     * @param string $entityName The name of the entity.
     *
     * @return EntityRepository
     */
    protected function getRepository($entityName)
    {
        return $this->getEntityManager()->getRepository($entityName);
    }
}
