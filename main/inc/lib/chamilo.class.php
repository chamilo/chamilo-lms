<?php

/**
 *
 * @return ChamiloSession
 */
function session()
{
    return Chamilo::session();
}

/**
 * Description of chamilo
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Chamilo
{

    public static function name()
    {
        //@todo: add version
        return 'chamilo';
    }

    static function is_test_server()
    {
        return api_get_setting('server_type') == 'test';
    }

    static function is_production_server()
    {
        return api_get_setting('server_type') == 'production';
    }

    /**
     *
     * @return ChamiloSession
     */
    static function session()
    {
        return ChamiloSession::instance();
    }

    /**
     *
     * @return CurrentUser
     */
    static function user()
    {
        return ChamiloSession::instance()->user();
    }

    /**
     * Returns a full url from local/absolute path and parameters.
     * Append the root as required for relative urls.
     * 
     * @param string $path
     * @param array $params
     * @return string 
     */
    public static function url($path = '', $params = array(), $html = true)
    {
        return Uri::url($path, $params, $html);
    }

    public static function here($params = array(), $html = true)
    {
        return Uri::here($params, $html);
    }

    /**
     * Application web root
     */
    public static function www()
    {
        return Uri::www();
    }

    /**
     * File system root for Chamilo
     * 
     * @return string
     */
    public static function root()
    {
        return api_get_path(SYS_PATH);
    }

    public static function root_courses()
    {
        return api_get_path(SYS_COURSE_PATH);
    }

    /**
     * Returns a temporary file - one that is automatically deleted at the end
     * of the script.
     * 
     * @param string $ext
     * @return Temp 
     */
    public static function temp_file($ext = '')
    {
        $ext = $ext ? '.' . $ext : '';
        Temp::set_temp_root(api_get_path(SYS_ARCHIVE_PATH) . 'temp');
        $path = Temp::get_temporary_name() . $ext;
        return Temp::create($path);
    }

    /**
     * Returns a temporary directory - one that is automatically deleted at the end
     * of the script.
     * 
     * @param string $ext
     * @return Temp 
     */
    public static function temp_dir()
    {
        $ext = $ext ? '.' . $ext : '';
        Temp::set_temp_root(api_get_path(SYS_ARCHIVE_PATH) . 'temp');
        $path = Temp::get_temporary_name() . $ext;
        return Temp::dir($path);
    }

    /**
     *
     * @return Zip
     */
    public static function temp_zip()
    {
        return Zip::create(self::temp_file('zip'));
    }

    public static function path($path = '')
    {
        $root = self::root();
        if (empty($path)) {
            return $root;
        }
        return $root . $path;
    }

}