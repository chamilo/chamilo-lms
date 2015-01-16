<?php
/**
 * SetupThemeListener.php
 * publisher
 * Date: 01.05.14
 */

namespace Chamilo\ThemeBundle\EventListener;


use Chamilo\ThemeBundle\Theme\ThemeManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class SetupThemeListener {

    /**
     * @var ThemeManager
     */
    protected $manager;

    protected $cssBase;

    protected $lteAdmin;

    function __construct($manager, $cssBase = null, $lteAdmin = null)
    {
        $this->cssBase  = $cssBase?:'bundles/avanzuadmintheme/';
        $this->lteAdmin = $lteAdmin?:'vendor/AdminLTE/css/';
        $this->manager  = $manager;
    }


    public function onKernelController(FilterControllerEvent $event) {

        $css = rtrim($this->cssBase, '/').'/'.trim($this->lteAdmin, '/');
        $mng = $this->manager;

        $mng->registerStyle('jquery-ui', $css.'/jQueryUI/jquery-ui-1.10.3.custom.css');
        $mng->registerStyle('bootstrap', $css.'/bootstrap.css', array('jquery-ui'));
        $mng->registerStyle('bootstrap-slider', $css.'/bootstrap-slider/slider.css', array('bootstrap'));
        $mng->registerStyle('datatables', $css.'/datatables/dataTables.bootstrap.css', array('bootstrap'));
        $mng->registerStyle('fontawesome', $css.'/font-awesome.css');
        $mng->registerStyle('ionicons', $css.'/ionicons.css');
        $mng->registerStyle('admin-lte', $css.'/AdminLTE.css', array('bootstrap-slider', 'fontawesome', 'ionicons','datatables'));
        $mng->registerStyle('bs-colorpicker', $css.'/colorpicker/bootstrap-colorpicker.css', array('admin-lte'));
        $mng->registerStyle('daterangepicker', $css.'/daterangepicker/daterangepicker-bs3.css', array('admin-lte'));
        $mng->registerStyle('timepicker', $css.'/timepicker/bootstrap-timepicker.css', array('admin-lte'));
        $mng->registerStyle('wysiwyg', $css.'/bootstrap-wysihtml5/bootstrap3-wysihtml5.css', array('admin-lte'));

    }
}
