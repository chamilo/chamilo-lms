<?php
/**
 * Plugin class for the Test2Pdf plugin.
 *
 * @package chamilo.plugin.test2pdf
 *
 * @author Jose Angel Ruiz <desarrollo@nosolored.com>
 */
class Test2pdfPlugin extends Plugin
{
    public $isCoursePlugin = true;

    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Jose Angel Ruiz - NoSoloRed (original author)',
            [
                'enable_plugin' => 'boolean',
            ]
        );
    }

    /**
     * @return StaticPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * This method creates the tables required to this plugin.
     */
    public function install()
    {
        //Installing course settings
        $this->install_course_fields_in_all_courses();

        $srcfile1 = __DIR__.'/../resources/img/64/test2pdf.png';
        $srcfile2 = __DIR__.'/../resources/img/64/test2pdf_na.png';
        $srcfile3 = __DIR__.'/../resources/img/22/test2pdf.png';
        $dstfile1 = __DIR__.'/../../../main/img/icons/64/test2pdf.png';
        $dstfile2 = __DIR__.'/../../../main/img/icons/64/test2pdf_na.png';
        $dstfile3 = __DIR__.'/../../../main/img/test2pdf.png';
        $res1 = @copy($srcfile1, $dstfile1);
        $res2 = @copy($srcfile2, $dstfile2);
        $res3 = @copy($srcfile3, $dstfile3);
        if (!$res1 || !$res2 || !$res3) {
            $warning = 'Test2PDF plugin icons could not be copied to main/img/ because of folder permissions. To fix, give web server user permissions to write to main/img/ before enabling this plugin.';
            Display::addFlash($warning);
            error_log($warning);
        }
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        // Deleting course settings.
        $this->uninstall_course_fields_in_all_courses($this->course_settings);
        $this->manageTab(false);
    }
}
