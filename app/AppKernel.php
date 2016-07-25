<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Class AppKernel
 */
class AppKernel extends Kernel
{
    protected $rootDir;

    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            //new Doctrine\Bundle\PHPCRBundle\DoctrinePHPCRBundle(),
            new Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle(),

            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle($this),

            // KNP HELPER BUNDLES
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
            // Data grid
            //new APY\DataGridBundle\APYDataGridBundle(),
            new Chamilo\FaqBundle\ChamiloFaqBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new A2lix\TranslationFormBundle\A2lixTranslationFormBundle(),

            // Sonata
            new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\IntlBundle\SonataIntlBundle(),
            new Sonata\FormatterBundle\SonataFormatterBundle(),
            new Sonata\CacheBundle\SonataCacheBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Sonata\SeoBundle\SonataSeoBundle(),
            new Sonata\ClassificationBundle\SonataClassificationBundle(),
            new Sonata\NotificationBundle\SonataNotificationBundle(),
            new Sonata\DatagridBundle\SonataDatagridBundle(),
            new Sonata\MediaBundle\SonataMediaBundle(),
            new Sonata\PageBundle\SonataPageBundle(),
            new Theodo\Evolution\Bundle\SessionBundle\TheodoEvolutionSessionBundle(),

            new Spy\TimelineBundle\SpyTimelineBundle(),
            new Sonata\TimelineBundle\SonataTimelineBundle(),

            //new SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle(),

            new Sonata\AdminBundle\SonataAdminBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),


            // CMF Integration
            new Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle(),
            //new Symfony\Cmf\Bundle\SearchBundle\CmfSearchBundle(),
            //new Symfony\Cmf\Bundle\BlogBundle\CmfBlogBundle(),
            //new Sonata\DoctrinePHPCRAdminBundle\SonataDoctrinePHPCRAdminBundle(),

            // Oauth
            //new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),

            //new Gregwar\CaptchaBundle\GregwarCaptchaBundle(),
            new Mopa\Bundle\BootstrapBundle\MopaBootstrapBundle(),
            //new Liip\ThemeBundle\LiipThemeBundle(),
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
            new FM\ElfinderBundle\FMElfinderBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new Knp\DoctrineBehaviors\Bundle\DoctrineBehaviorsBundle(),

            // User
            new FOS\UserBundle\FOSUserBundle(),
            new Sonata\UserBundle\SonataUserBundle('FOSUserBundle'),
            new Chamilo\UserBundle\ChamiloUserBundle(),
            /*
            // Sylius
            /*new Sylius\Bundle\SettingsBundle\SyliusSettingsBundle(),
            //new Sylius\Bundle\AttributeBundle\SyliusAttributeBundle(),
            new Sylius\Bundle\ResourceBundle\SyliusResourceBundle(),
            new Sylius\Bundle\FlowBundle\SyliusFlowBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle(),*/

            // Chamilo
            //new Chamilo\InstallerBundle\ChamiloInstallerBundle(),
            new Chamilo\CoreBundle\ChamiloCoreBundle(),
            new Chamilo\CourseBundle\ChamiloCourseBundle(),
            // new Chamilo\SettingsBundle\ChamiloSettingsBundle(),
            new Chamilo\ThemeBundle\ChamiloThemeBundle(),
            //new Chamilo\NotificationBundle\ChamiloNotificationBundle(),
            new Chamilo\AdminBundle\ChamiloAdminBundle(),
            new Chamilo\TimelineBundle\ChamiloTimelineBundle(),
            new \Chamilo\ContactBundle\ChamiloContactBundle(),

            // Based in Sonata
            new Chamilo\ClassificationBundle\ChamiloClassificationBundle(),
            new Chamilo\MediaBundle\ChamiloMediaBundle(),
            new Chamilo\PageBundle\ChamiloPageBundle(),
            /*
            // Chamilo course tool
            //new Chamilo\NotebookBundle\ChamiloNotebookBundle(),

            // Data
            //new Oneup\FlysystemBundle\OneupFlysystemBundle(), */

            // Extra
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),

            /*
            //new JMS\TranslationBundle\JMSTranslationBundle(),
            //new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            //new JMS\AopBundle\JMSAopBundle(),
            /*new Bazinga\Bundle\FakerBundle\BazingaFakerBundle(),
            //new Chamilo\CmsBundle\ChamiloCmsBundle(),
             */
            new Lunetics\LocaleBundle\LuneticsLocaleBundle()
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            //$bundles[] = new Jjanvier\Bundle\CrowdinBundle\JjanvierCrowdinBundle();
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            //$bundles[] = new Sp\BowerBundle\SpBowerBundle();
        }

        return $bundles;
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }


    /**
     * @return string
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = str_replace('\\', '/', dirname($r->getFileName()));
        }

        return $this->rootDir;
    }

    /**
     * Returns the real root path
     * @return string
     */
    public function getRealRootDir()
    {
        return realpath($this->getRootDir().'/../').'/';
    }

    /**
     * Returns the data path
     * @return string
     */
    public function getDataDir()
    {
        return $this->getRealRootDir().'data/';
    }

    /**
     * @return string
     */
    public function getAppDir()
    {
        return $this->getRealRootDir().'app/';
    }

    /**
     * @return string
     */
    public function getConfigDir()
    {
        return $this->getRealRootDir().'app/config/';
    }

    /**
     * @return string
     */
    public function getConfigurationFile()
    {
        return $this->getRealRootDir().'app/config/configuration.php';
    }

    /**
     * @param array $configuration
     */
    public function setApi(array $configuration)
    {
        new ChamiloApi($configuration);
    }
}

