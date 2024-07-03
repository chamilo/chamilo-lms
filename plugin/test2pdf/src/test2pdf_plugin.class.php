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

        $list = [
            '/64/test2pdf.png',
            '/64/test2pdf_na.png',
            '/32/test2pdf.png',
            '/32/test2pdf_na.png',
            '/22/test2pdf.png',
        ];

        $res = true;
        foreach ($list as $file) {
            $source = __DIR__.'/../resources/img/'.$file;
            $destination = __DIR__.'/../../../main/img/icons/'.$file;
            $res = @copy($source, $destination);
            if (!$res) {
                break;
            }
        }

        if (!$res) {
            $warning = 'Test2PDF plugin icons could not be copied to main/img/ because of folder permissions. 
            To fix, give web server user permissions to write to main/img/ before enabling this plugin.';
            Display::addFlash(Display::return_message($warning, 'warning'));
        }
    }

    /**
     * By default new icon is invisible.
     *
     * @return bool
     */
    public function isIconVisibleByDefault()
    {
        return false;
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
