<?php

/**
 * Autoloader
 *
 * @category   Phpdocx
 * @package    loader
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    LGPL
 * @version    2.0
 * @link       http://www.phpdocx.com
 * @since      File available since Release 2.0
 */

/**
 * Autoloader
 *
 * @category   Phpdocx
 * @package    loader
 * @copyright  Copyright (c) 2009-2011 Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    http://www.phpdocx.com/wp-content/themes/lightword/pro_license.php
 * @version    2.0
 * @link       http://www.phpdocx.com
 * @since      Class available since Release 2.0
 */
class AutoLoader
{

    /**
     * Autoload dompdf
     *
     * @access private
     * @param string $className Class to load
     */
    private static function autoloadDompdf($className)
    {
        $pathDompdf = dirname(__FILE__) . '/../pdf/dompdf_config.inc.php';
        if (file_exists($pathDompdf)) {
            require_once $pathDompdf;
        }
    }

    /**
     * Autoload log4php
     *
     * @access private
     * @param string $className Class to load
     */
    private static function autoloadLog4php($className)
    {
        $pathLogphp = dirname(__FILE__) . '/../lib/log4php/'
                . $className . '.php';
        if (file_exists($pathLogphp)) {
            require_once $pathLogphp;
        }
    }

    /**
     * Autoload phpdocx
     *
     * @access private
     * @param string $className Class to load
     */
    private static function autoloadPhpdocx($className)
    {
        $pathPhpdocx = dirname(__FILE__) . '/' . $className . '.inc';
        if (file_exists($pathPhpdocx)) {
            require_once $pathPhpdocx;
        }
    }

    /**
     * Main tags of relationships XML
     *
     * @access public
     * @static
     */
    public static function load()
    {
        spl_autoload_register(array('AutoLoader', 'autoloadPhpdocx'));
        spl_autoload_register(array('AutoLoader', 'autoloadLog4php'));
    }

    public static function loadPDF()
    {
        spl_autoload_register(array('AutoLoader', 'autoloadDompdf'));
    }

}
