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
        copy($srcfile1, $dstfile1);
        copy($srcfile2, $dstfile2);
        copy($srcfile3, $dstfile3);
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
