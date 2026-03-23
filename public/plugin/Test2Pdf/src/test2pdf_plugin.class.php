<?php
/**
 * Plugin class for the Test2Pdf plugin.
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
            []
        );
    }

    /**
     * @return StaticPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * This method creates the global resources required by this plugin.
     *
     * Important:
     * Do not install course fields here.
     * In Chamilo 2, course plugin tools must only be propagated when the plugin
     * is enabled from the plugins list, not merely when it is installed.
     */
    public function install()
    {
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
            $warning = 'Test2PDF plugin icons could not be copied to main/img/ because of folder permissions. To fix, give the web server user permission to write to main/img/ before enabling this plugin.';
            Display::addFlash(Display::return_message($warning, 'warning'));
        }
    }

    /**
     * This method removes the data created by this plugin.
     *
     * Course fields are already removed by the core enable/disable/uninstall
     * synchronization flow in plugin.ajax.php, but keeping this cleanup here
     * is harmless and makes the plugin uninstall safer.
     */
    public function uninstall()
    {
        $this->uninstall_course_fields_in_all_courses($this->course_settings);
    }

    public function get_name(): string
    {
        return 'Test2Pdf';
    }
}
