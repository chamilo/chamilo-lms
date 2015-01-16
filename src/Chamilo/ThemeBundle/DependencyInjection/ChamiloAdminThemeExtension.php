<?php

namespace Chamilo\ThemeBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ChamiloThemeExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        //$config        = $this->processConfiguration($configuration, $configs);

        //$container->setParameter('avanzu_admin_theme.bower_bin', $config['bower_bin']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle'])) {
            $container->prependExtensionConfig('twig', array(
                'form' => array(
                    'resources' => array(
                        'ChamiloThemeBundle:Layout:form-theme.html.twig'
                    )
                ),
                'globals' => array(
                    'admin_theme' => 'chamilo_admin_theme.theme_manager'
                )
            ));
        }

        return;

        $jsAssets  = '@ChamiloThemeBundle/Resources/';
        $lteJs     = $jsAssets . 'public/vendor/adminlte/js/';
        $cssAssets = 'bundles/avanzuadmintheme/';
        $lteCss    = $cssAssets . 'vendor/adminlte/css/';
        $lteFont   = $cssAssets . 'vendor/adminlte/fonts/';

        if (isset($bundles['AsseticBundle']) && 0) {
            $container->prependExtensionConfig(
                      'assetic',
                          array(
                              'bundles' => array(
                                'ChamiloThemeBundle'
                                ),
                              'assets' => array(
                                  'common_js'              => array(
                                      'inputs' => array(
                                          $jsAssets . 'public/vendor/jquery/dist/jquery.js',
                                          $jsAssets . 'public/vendor/jquery-ui/jquery-ui.js',
                                          $jsAssets . 'public/vendor/underscore/underscore.js',
                                          $jsAssets . 'public/vendor/backbone/backbone.js',
                                          $jsAssets . 'public/vendor/marionette/lib/backbone.marionette.js',
                                          $jsAssets . 'public/vendor/bootstrap/dist/js/bootstrap.min.js',
                                          $jsAssets . 'public/vendor/bootbox/bootbox.js',
                                          $jsAssets . 'public/js/dialogs.js',
                                          $jsAssets . 'public/js/namespace.js',
                                      ),
                                  ),
                                  'tools_js'               => array(
                                      'inputs' => array(
                                          '@common_js',
                                          $jsAssets . 'public/vendor/momentjs/moment.js',
                                          $jsAssets . 'public/vendor/holderjs/holder.js',
                                          $jsAssets . 'public/vendor/spinjs/spin.js',
                                      ),
                                  ),
                                  'admin_lte_js'           => array(
                                      'inputs' => array(
                                          $lteJs . 'plugins/bootstrap-slider/bootstrap-slider.js',
                                          $lteJs . 'plugins/datatables/jquery.dataTables.js',
                                          $lteJs . 'plugins/datatables/dataTables.bootstrap.js',
                                          $lteJs . 'plugins/slimScroll/jquery.slimscroll.js',
                                          $jsAssets . 'public/js/adminLTE.js',
                                      )
                                  ),
                                  'admin_lte_css'          => array(
                                      'inputs' => array(

                                        //  $lteCss . 'jQueryUI/jquery-ui-1.10.3.custom.css',
                                         $cssAssets . 'vendor/bootstrap/dist/css/bootstrap.min.css',
                                          $lteCss . 'bootstrap-slider/slider.css',
                                          $lteCss . 'datatables/dataTables.bootstrap.css',
                                          $cssAssets . 'vendor/fontawesome/css/font-awesome.min.css',
                                          $cssAssets . 'vendor/ionicons/css/ionicons.min.css',
                                          $lteCss . 'AdminLTE.css',
                                          //$lteFont . 'fontawesome-webfont.eot',
                                          // $lteFont . 'ionicons.eot',
                                      )
                                  ),
                                  'admin_lte_forms_js'     => array(
                                      'inputs' => array(
                                          $lteJs . 'plugins/colorpicker/bootstrap-colorpicker.js',
                                          $lteJs . 'plugins/daterangepicker/daterangepicker.js',
                                          $lteJs . 'plugins/timepicker/bootstrap-timepicker.js',
                                          $lteJs . 'plugins/input-mask/jquery.inputmask.js',
                                          //   $lteJs.'plugins/input-mask/*',
                                      )
                                  ),
                                  'admin_lte_forms_css'    => array(
                                      'inputs' => array(
                                          $lteCss . 'colorpicker/bootstrap-colorpicker.css',
                                          $lteCss . 'daterangepicker/daterangepicker-bs3.css',
                                          $lteCss . 'timepicker/bootstrap-timepicker.css',
                                      )
                                  ),
                                  'admin_lte_wysiwyg'      => array(
                                      'inputs' => array(
                                          $lteJs . 'plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.js',
                                      )
                                  ),
                                  'admin_lte_wysiwyg_css'  => array(
                                      'inputs' => array(
                                          $lteCss . 'bootstrap-wysihtml5/bootstrap3-wysihtml5.css',
                                      )
                                  ),
                                  'admin_lte_morris'       => array(
                                      'inputs' => array(
                                          $lteJs . 'plugins/morris/morris.js',
                                      )
                                  ),
                                  'admin_lte_morris_css'   => array(
                                      'inputs' => array(
                                          $lteCss . 'morris/morris.css',
                                      )
                                  ),
                                  'admin_lte_flot'         => array(
                                      'inputs' => array(
                                          $lteJs . 'plugins/flot/*',
                                      )
                                  ),
                                  'admin_lte_calendar'     => array(
                                      'inputs' => array(
                                          $jsAssets . 'public/vendor/fullcalendar/dist/fullcalendar.min.js',
                                      )
                                  ),
                                  'admin_lte_calendar_css' => array(
                                      'inputs' => array(
                                          $lteCss . 'fullcalendar/fullcalendar.css',
                                      )
                                  ),
                                  'avatar_img'             => array(
                                      'inputs' => array(
                                          '@ChamiloThemeBundle/Resources/public/img/avatar.png'
                                      )
                                  ),
                                  'admin_lte_all'          => array(
                                      'inputs' => array(
                                          '@tools_js',
                                          '@admin_lte_forms_js',
                                          '@admin_lte_wysiwyg',
                                          '@admin_lte_morris',
                                          '@admin_lte_calendar',
                                          '@admin_lte_js',
                                          //  '@admin_lte_flot',
                                      )
                                  ),
                                  'admin_lte_all_css'      => array(
                                      'inputs' => array(
                                          '@admin_lte_calendar_css',
                                          '@admin_lte_morris_css',
                                          '@admin_lte_wysiwyg_css',
                                          '@admin_lte_forms_css',
                                          '@admin_lte_css'
                                      )
                                  ),
                              )
                          )
            );

        }
    }
}
