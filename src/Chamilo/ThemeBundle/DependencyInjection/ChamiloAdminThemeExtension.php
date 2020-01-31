<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ChamiloThemeExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        //$config        = $this->processConfiguration($configuration, $configs);

        //$container->setParameter('avanzu_admin_theme.bower_bin', $config['bower_bin']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle'])) {
            $container->prependExtensionConfig('twig', [
                'form' => [
                    'resources' => [
                        'ChamiloThemeBundle:Layout:form-theme.html.twig',
                    ],
                ],
                'globals' => [
                    'admin_theme' => 'chamilo_admin_theme.theme_manager',
                ],
            ]);
        }

        return;

        $jsAssets = '@ChamiloThemeBundle/Resources/';
        $lteJs = $jsAssets.'public/vendor/adminlte/js/';
        $cssAssets = 'bundles/avanzuadmintheme/';
        $lteCss = $cssAssets.'vendor/adminlte/css/';
        $lteFont = $cssAssets.'vendor/adminlte/fonts/';

        if (isset($bundles['AsseticBundle']) && 0) {
            $container->prependExtensionConfig(
              'assetic',
                  [
                      'bundles' => [
                        'ChamiloThemeBundle',
                        ],
                      'assets' => [
                          'common_js' => [
                              'inputs' => [
                                  $jsAssets.'public/vendor/jquery/dist/jquery.js',
                                  $jsAssets.'public/vendor/jquery-ui/jquery-ui.js',
                                  $jsAssets.'public/vendor/underscore/underscore.js',
                                  $jsAssets.'public/vendor/backbone/backbone.js',
                                  $jsAssets.'public/vendor/marionette/lib/backbone.marionette.js',
                                  $jsAssets.'public/vendor/bootstrap/dist/js/bootstrap.min.js',
                                  $jsAssets.'public/vendor/bootbox/bootbox.js',
                                  $jsAssets.'public/js/dialogs.js',
                                  $jsAssets.'public/js/namespace.js',
                              ],
                          ],
                          'tools_js' => [
                              'inputs' => [
                                  '@common_js',
                                  $jsAssets.'public/vendor/momentjs/moment.js',
                                  $jsAssets.'public/vendor/holderjs/holder.js',
                                  $jsAssets.'public/vendor/spinjs/spin.js',
                              ],
                          ],
                          'admin_lte_js' => [
                              'inputs' => [
                                  $lteJs.'plugins/bootstrap-slider/bootstrap-slider.js',
                                  $lteJs.'plugins/datatables/jquery.dataTables.js',
                                  $lteJs.'plugins/datatables/dataTables.bootstrap.js',
                                  $lteJs.'plugins/slimScroll/jquery.slimscroll.js',
                                  $jsAssets.'public/js/adminLTE.js',
                              ],
                          ],
                          'admin_lte_css' => [
                              'inputs' => [
                                //  $lteCss . 'jQueryUI/jquery-ui-1.10.3.custom.css',
                                 $cssAssets.'vendor/bootstrap/dist/css/bootstrap.min.css',
                                  $lteCss.'bootstrap-slider/slider.css',
                                  $lteCss.'datatables/dataTables.bootstrap.css',
                                  $cssAssets.'vendor/fontawesome/css/font-awesome.min.css',
                                  $cssAssets.'vendor/ionicons/css/ionicons.min.css',
                                  $lteCss.'AdminLTE.css',
                                  //$lteFont . 'fontawesome-webfont.eot',
                                  // $lteFont . 'ionicons.eot',
                              ],
                          ],
                          'admin_lte_forms_js' => [
                              'inputs' => [
                                  $lteJs.'plugins/colorpicker/bootstrap-colorpicker.js',
                                  $lteJs.'plugins/daterangepicker/daterangepicker.js',
                                  $lteJs.'plugins/timepicker/bootstrap-timepicker.js',
                                  $lteJs.'plugins/input-mask/jquery.inputmask.js',
                                  //   $lteJs.'plugins/input-mask/*',
                              ],
                          ],
                          'admin_lte_forms_css' => [
                              'inputs' => [
                                  $lteCss.'colorpicker/bootstrap-colorpicker.css',
                                  $lteCss.'daterangepicker/daterangepicker-bs3.css',
                                  $lteCss.'timepicker/bootstrap-timepicker.css',
                              ],
                          ],
                          'admin_lte_wysiwyg' => [
                              'inputs' => [
                                  $lteJs.'plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.js',
                              ],
                          ],
                          'admin_lte_wysiwyg_css' => [
                              'inputs' => [
                                  $lteCss.'bootstrap-wysihtml5/bootstrap3-wysihtml5.css',
                              ],
                          ],
                          'admin_lte_morris' => [
                              'inputs' => [
                                  $lteJs.'plugins/morris/morris.js',
                              ],
                          ],
                          'admin_lte_morris_css' => [
                              'inputs' => [
                                  $lteCss.'morris/morris.css',
                              ],
                          ],
                          'admin_lte_flot' => [
                              'inputs' => [
                                  $lteJs.'plugins/flot/*',
                              ],
                          ],
                          'admin_lte_calendar' => [
                              'inputs' => [
                                  $jsAssets.'public/vendor/fullcalendar/dist/fullcalendar.min.js',
                              ],
                          ],
                          'admin_lte_calendar_css' => [
                              'inputs' => [
                                  $lteCss.'fullcalendar/fullcalendar.css',
                              ],
                          ],
                          'avatar_img' => [
                              'inputs' => [
                                  '@ChamiloThemeBundle/Resources/public/img/avatar.png',
                              ],
                          ],
                          'admin_lte_all' => [
                              'inputs' => [
                                  '@tools_js',
                                  '@admin_lte_forms_js',
                                  '@admin_lte_wysiwyg',
                                  '@admin_lte_morris',
                                  '@admin_lte_calendar',
                                  '@admin_lte_js',
                                  //  '@admin_lte_flot',
                              ],
                          ],
                          'admin_lte_all_css' => [
                              'inputs' => [
                                  '@admin_lte_calendar_css',
                                  '@admin_lte_morris_css',
                                  '@admin_lte_wysiwyg_css',
                                  '@admin_lte_forms_css',
                                  '@admin_lte_css',
                              ],
                          ],
                      ],
                  ]
            );
        }
    }
}
