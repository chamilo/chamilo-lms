<?php
/**
 * Exception for Image_Text
 *
 * PHP version 5
 *
 * @category Image
 * @package  Image_Text
 * @author   Daniel O'Connor <daniel.oconnor@gmail.com>
 * @license  http://www.php.net/license/3_01.txt PHP License
 * @link     http://pear.php.net/package/Image_Text
 */
$lib_path = api_get_path(LIBRARY_PATH);
require_once $lib_path.'/pear/Exception.php';
/**
 * Exception for Image_Text
 *
 * @category Image
 * @package  Image_Text
 * @author   Daniel O'Connor <daniel.oconnor@gmail.com>
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.php.net/license/3_01.txt PHP License
 * @link     http://pear.php.net/package/Image_Text
 */
class Image_Text_Exception extends PEAR_Exception
{
}
