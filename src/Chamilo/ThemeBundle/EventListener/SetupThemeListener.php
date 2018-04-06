<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\EventListener;

use Chamilo\ThemeBundle\Theme\ThemeManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class SetupThemeListener.
 *
 * @package Chamilo\ThemeBundle\EventListener
 */
class SetupThemeListener
{
    /**
     * @var ThemeManager
     */
    protected $manager;

    protected $cssBase;

    protected $lteAdmin;

    public function __construct($manager, $cssBase = null, $lteAdmin = null)
    {
        $this->cssBase = $cssBase ?: 'bundles/avanzuadmintheme/';
        $this->lteAdmin = $lteAdmin ?: 'vendor/AdminLTE/css/';
        $this->manager = $manager;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $css = rtrim($this->cssBase, '/').'/'.trim($this->lteAdmin, '/');
        $mng = $this->manager;
        $mng->registerStyle('jquery-ui', $css.'/jQueryUI/jquery-ui-1.10.3.custom.css');
        $mng->registerStyle('bootstrap', $css.'/bootstrap.css', ['jquery-ui']);
        $mng->registerStyle('bootstrap-slider', $css.'/bootstrap-slider/slider.css', ['bootstrap']);
        $mng->registerStyle('datatables', $css.'/datatables/dataTables.bootstrap.css', ['bootstrap']);
        $mng->registerStyle('fontawesome', $css.'/font-awesome.css');
        $mng->registerStyle('ionicons', $css.'/ionicons.css');
        $mng->registerStyle('admin-lte', $css.'/AdminLTE.css', ['bootstrap-slider', 'fontawesome', 'ionicons', 'datatables']);
        $mng->registerStyle('bs-colorpicker', $css.'/colorpicker/bootstrap-colorpicker.css', ['admin-lte']);
        $mng->registerStyle('daterangepicker', $css.'/daterangepicker/daterangepicker-bs3.css', ['admin-lte']);
        $mng->registerStyle('timepicker', $css.'/timepicker/bootstrap-timepicker.css', ['admin-lte']);
        $mng->registerStyle('wysiwyg', $css.'/bootstrap-wysihtml5/bootstrap3-wysihtml5.css', ['admin-lte']);
    }
}
