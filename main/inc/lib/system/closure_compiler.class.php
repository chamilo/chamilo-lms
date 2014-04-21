<?php

/**
 * Proxy for the closure compiler. Allows to minify javascript files.
 * This makes use of CURL and calls the closure service.
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 * @see http://closure-compiler.appspot.com/home
 * @see https://developers.google.com/closure/compiler/
 *
 */
class ClosureCompiler
{

    const PARAM_OUTPUT_FORMAT = 'output_format';
    const PARAM_OUTPUT_INFO = 'output_info';
    const PARAM_COMPILATION_LEVEL = 'compilation_level';
    const PARAM_JS_CODE = 'js_code';
    const LEVEL_SIMPLE = 'SIMPLE_OPTIMIZATIONS';
    const LEVEL_WHITESPACE = 'WHITESPACE_ONLY';
    const LEVEL_ADVANCED = 'ADVANCED_OPTIMIZATIONS';
    const OUTPUT_FORMAT_TEXT = 'text';
    const OUTPUT_INFO_CODE = 'compiled_code';

    /**
     * Url of the service
     * 
     * @return string 
     */
    public static function url()
    {
        return 'closure-compiler.appspot.com/compile';
    }

    /**
     * Post data the closure compiler service. By default it returns minimized code
     * with the simple option.
     * 
     * @param string $code Javascript code to minimify
     * @param array $params parameters to pass to the service
     * @return string minimified code
     */
    public static function post($code, $params = array())
    {
        if (!isset($params[self::PARAM_OUTPUT_FORMAT]))
        {
            $params[self::PARAM_OUTPUT_FORMAT] = self::OUTPUT_FORMAT_TEXT;
        }
        if (!isset($params[self::PARAM_OUTPUT_INFO]))
        {
            $params[self::PARAM_OUTPUT_INFO] = self::OUTPUT_INFO_CODE;
        }
        if (!isset($params[self::PARAM_COMPILATION_LEVEL]))
        {
            $params[self::PARAM_JS_CODE] = $code;
        }

        $params[self::PARAM_COMPILATION_LEVEL] = self::LEVEL_SIMPLE;

        $fields = array();
        foreach ($params as $key => $value)
        {
            $fields[] = $key . '=' . urlencode($value);
        }
        $fields = implode('&', $fields);

        $headers = array("Content-type: application/x-www-form-urlencoded");

        $url = self::url();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        //curl_setopt($ch, CURLOPT_HEADER, false);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        $content = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        return $content;
    }

}