<?php

/**
 * Javascript utility functions.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Javascript
{

    /**
     * Minimify Javascript.
     * 
     * If called with a path 
     * 
     *  input:   /dir/dir/file.js 
     *  returns: /dir/dir/file.min.js
     * 
     * Otherwise returns the actual code.
     * 
     * @param string $arg either a path or actual code
     * @return string either a path to minified content (ends with min.js) or actual code
     */
    public static function minify($arg)
    {
        if (is_readable($arg))
        {
            $path = $arg;
            $code = file_get_contents($path);
            $code = ClosureCompiler::post($code);

            $min_path = $path;
            $min_path = str_replace('.js', '', $min_path);
            $min_path .= '.min.js';
            file_put_contents($min_path, $code);
            return $min_path;
        }
        else
        {
            return ClosureCompiler::post($code);
        }
    }

    /**
     * Returns lang object that contains the translation.
     * 
     *   Javascript::get_lang('string1', 'string2', 'string3');
     * 
     * returns
     * 
     * if(typeof(lang) == "undefined")
     * {
     *      var lang = {};
     * }
     * lang.string1 = "...";
     * lang.string2 = "...";
     * lang.string3 = "...
     * 
     * @param type $_
     * @return type 
     */
    public static function get_lang($_)
    {
        $result = array();
        $result[] = 'if(typeof(lang) == "undefined")';
        $result[] = '{';
        $result[] = '   var lang = {};';
        $result[] = '}';

        $keys = func_get_args();
        foreach ($keys as $key)
        {
            $value = get_lang($key);
            $result[] = 'lang.' . $key . ' = "' . $value . '";';
        }
        return implode("\n", $result);
    }

    public static function tag($src)
    {
        return '<script type="text/javascript" src="' . $src . '"></script>';
    }

    public static function tag_code($code)
    {
        $new_line = "\n";
        return '<script type="text/javascript">' . $new_line . $code . $new_line . '</script>';
    }

}