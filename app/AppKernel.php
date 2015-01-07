<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Class AppKernel
 */
class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = array(
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle($this),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),

            // Sylius
            new Sylius\Bundle\SettingsBundle\SyliusSettingsBundle(),
            new Sylius\Bundle\ResourceBundle\SyliusResourceBundle(),
            new Sylius\Bundle\FlowBundle\SyliusFlowBundle(),

            // Symfony standard edition
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),

            // Doctrine
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),

            // KNP HELPER BUNDLES
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),

            // User
            new FOS\UserBundle\FOSUserBundle(),
            new Sonata\UserBundle\SonataUserBundle('FOSUserBundle'),
            new Chamilo\UserBundle\ChamiloUserBundle(),

            // Page
            new Sonata\PageBundle\SonataPageBundle(),
            new Application\Sonata\PageBundle\ApplicationSonataPageBundle(),

            // NEWS
            new Sonata\NewsBundle\SonataNewsBundle(),
            new Application\Sonata\NewsBundle\ApplicationSonataNewsBundle(),

            // MEDIA
            new Sonata\MediaBundle\SonataMediaBundle(),
            new Application\Sonata\MediaBundle\ApplicationSonataMediaBundle(),
            // new Liip\ImagineBundle\LiipImagineBundle(),
            //new Presta\CMSMediaBundle\PrestaCMSMediaBundle(),

            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
            new CoopTilleuls\Bundle\CKEditorSonataMediaBundle\CoopTilleulsCKEditorSonataMediaBundle(),

            new Sonata\AdminBundle\SonataAdminBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),

            // Disable this if you don't want the audit on entities
            new SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle(),

            // API
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),

            // E-COMMERCE
            /*new Sonata\BasketBundle\SonataBasketBundle(),
            new Application\Sonata\BasketBundle\ApplicationSonataBasketBundle(),
            new Sonata\CustomerBundle\SonataCustomerBundle(),
            new Application\Sonata\CustomerBundle\ApplicationSonataCustomerBundle(),
            new Sonata\DeliveryBundle\SonataDeliveryBundle(),
            new Application\Sonata\DeliveryBundle\ApplicationSonataDeliveryBundle(),
            new Sonata\InvoiceBundle\SonataInvoiceBundle(),
            new Application\Sonata\InvoiceBundle\ApplicationSonataInvoiceBundle(),
            new Sonata\OrderBundle\SonataOrderBundle(),
            new Application\Sonata\OrderBundle\ApplicationSonataOrderBundle(),
            new Sonata\PaymentBundle\SonataPaymentBundle(),
            new Application\Sonata\PaymentBundle\ApplicationSonataPaymentBundle(),
            new Sonata\ProductBundle\SonataProductBundle(),
            new Application\Sonata\ProductBundle\ApplicationSonataProductBundle(),
            new Sonata\PriceBundle\SonataPriceBundle(),

            */
            new FOS\CommentBundle\FOSCommentBundle(),
            new Sonata\CommentBundle\SonataCommentBundle(),
            new Application\Sonata\CommentBundle\ApplicationSonataCommentBundle(),

            // SONATA CORE & HELPER BUNDLES
            new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\IntlBundle\SonataIntlBundle(),
            new Sonata\FormatterBundle\SonataFormatterBundle(),
            new Sonata\CacheBundle\SonataCacheBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Sonata\SeoBundle\SonataSeoBundle(),
            new Sonata\ClassificationBundle\SonataClassificationBundle(),
            new Sonata\NotificationBundle\SonataNotificationBundle(),
            new Application\Sonata\ClassificationBundle\ApplicationSonataClassificationBundle(),
            new Application\Sonata\NotificationBundle\ApplicationSonataNotificationBundle(),
            new Application\Sonata\SeoBundle\ApplicationSonataSeoBundle(),
            new Sonata\DatagridBundle\SonataDatagridBundle(),

            // Search Integration
            //new FOS\ElasticaBundle\FOSElasticaBundle(),

            // CMF Integration
            new Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle(),

            // DEMO and QA - Can be deleted
            //new Sonata\Bundle\DemoBundle\SonataDemoBundle(),
            //new Sonata\Bundle\QABundle\SonataQABundle(),

            // Disable this if you don't want the timeline in the admin
            new Spy\TimelineBundle\SpyTimelineBundle(),
            new Sonata\TimelineBundle\SonataTimelineBundle(),
            new Application\Sonata\TimelineBundle\ApplicationSonataTimelineBundle(), // easy extends integration

            new Mopa\Bundle\BootstrapBundle\MopaBootstrapBundle(),
            new Application\Sonata\AdminBundle\ApplicationSonataAdminBundle(),
            new FOS\AdvancedEncoderBundle\FOSAdvancedEncoderBundle(),

            //new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
            new FOS\MessageBundle\FOSMessageBundle(),

            // Chamilo
            new Chamilo\InstallerBundle\ChamiloInstallerBundle(),
            new Chamilo\CoreBundle\ChamiloCoreBundle(),
            new Chamilo\CourseBundle\ChamiloCourseBundle(),
            new Chamilo\MessageBundle\ChamiloMessageBundle(),
            new Chamilo\SettingsBundle\ChamiloSettingsBundle(),
            new Chamilo\AdminThemeBundle\ChamiloAdminThemeBundle(),
            //new Chamilo\ThemeBundle\ChamiloThemeBundle(),

            // Chamilo course tool
            new Chamilo\NotebookBundle\ChamiloNotebookBundle(),

            new APY\DataGridBundle\APYDataGridBundle(),
            new JMS\TranslationBundle\JMSTranslationBundle(),
            new Liip\ThemeBundle\LiipThemeBundle(),

            //new FOS\RestBundle\FOSRestBundle(),
            //new JMS\SerializerBundle\JMSSerializerBundle($this),
            new Sp\BowerBundle\SpBowerBundle(),
            new Oro\Bundle\MigrationBundle\OroMigrationBundle(),
            new Thrace\DataGridBundle\ThraceDataGridBundle(),

            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),

            //new Vich\UploaderBundle\VichUploaderBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            //$bundles[] = new Jjanvier\Bundle\CrowdinBundle\JjanvierCrowdinBundle(),
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Bazinga\Bundle\FakerBundle\BazingaFakerBundle();
            $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
            $bundles[] = new Elao\WebProfilerExtraBundle\WebProfilerExtraBundle();
            $bundles[] = new Jns\Bundle\XhprofBundle\JnsXhprofBundle();
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    /*public function getCacheDir()
    {
        return dirname(dirname(__DIR__)).'/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return dirname(dirname(__DIR__)).'/log/';
    }*/

    /*public function getLogDir()
    {
        return $this->rootDir.'/../logs/'.$this->environment.'/logs/';
    }

    public function getCacheDir()
    {
        return $this->rootDir.'/../data/temp/'.$this->environment.'/cache/';
    }*/

    // Custom paths

    /**
     * Returns the real root path
     * @return string
     */
    public function getRealRootDir()
    {
        return realpath($this->rootDir.'/../').'/';
    }

    /**
     * Returns the data path
     * @return string
     */
    public function getDataDir()
    {
        return $this->getRealRootDir().'data/';
    }
}
