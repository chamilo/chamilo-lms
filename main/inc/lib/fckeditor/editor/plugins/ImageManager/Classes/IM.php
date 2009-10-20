<?php

/***********************************************************************
** Title.........:  ImageMagick Driver
** Version.......:  1.0
** Author........:  Xiang Wei ZHUO <wei@zhuo.org>
** Filename......:  IM.php
** Last changed..:  30 Aug 2003
** Notes.........:  Orginal is from PEAR
**/

// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Peter Bowyer <peter@mapledesign.co.uk>                      |
// +----------------------------------------------------------------------+
//
// $Id: IM.php,v 1.2 2006/12/19 21:14:36 thierrybo Exp $
//
// Image Transformation interface using command line ImageMagick
//

require_once "Transform.php";

Class Image_Transform_Driver_IM extends Image_Transform
{
    /**
     * associative array commands to be executed
     * @var array
     */
    var $command = array();

    /**
     *
     *
     */
    function Image_Transform_Driver_IM()
    {
        return true;
    } // End Image_IM

    /**
     * Load image
     *
     * @param string filename
     *
     * @return mixed none or a PEAR error object on error
     * @see PEAR::isError()
     */
    function load($image)
    {

        $this->uid = md5($_SERVER['REMOTE_ADDR']);
        /*if (!file_exists($image)) {
            return PEAR::raiseError('The image file ' . $image . ' does\'t exist', true);
        }*/
        $this->image = $image;
        $this->_get_image_details($image);
    } // End load

    /**
     * Resize Action
     *
     * @param int   new_x   new width
     * @param int   new_y   new height
     *
     * @return none
     * @see PEAR::isError()
     */
    function _resize($new_x, $new_y)
    {
		// prevent illegal data
		if (!is_numeric($new_x)) die("new X must be numeric");
		if (!is_numeric($new_y)) die("new Y must be numeric");

        /*if (isset($this->command['resize'])) {
            return PEAR::raiseError("You cannot scale or resize an image more than once without calling save or display", true);
        }*/
        $this->command['resize'] = "-geometry {$new_x}x{$new_y}!";

        $this->new_x = $new_x;
        $this->new_y = $new_y;
    } // End resize

    /**
     * Crop the image
     *
     * @param int $crop_x left column of the image
     * @param int $crop_y top row of the image
     * @param int $crop_width new cropped image width
     * @param int $crop_height new cropped image height
     */
    function crop($crop_x, $crop_y, $crop_width, $crop_height)
    {
		if (!is_numeric($crop_x)) die("crop_x must be numeric");
		if (!is_numeric($crop_y)) die("crop_y must be numeric");
		if (!is_numeric($crop_width)) die("crop_width must be numeric");
		if (!is_numeric($crop_height)) die("crop_height must be numeric");
        $this->command['crop'] = "-crop {$crop_width}x{$crop_height}+{$crop_x}+{$crop_y}";
    }

    /**
     * Flip the image horizontally or vertically
     *
     * @param boolean $horizontal true if horizontal flip, vertical otherwise
     */
    function flip($horizontal)
    {
        if($horizontal)
            $this->command['flop'] = "-flop";
        else
            $this->command['flip'] = "-flip";
    }
    /**
     * rotate
     *
     * @param   int     angle   rotation angle
     * @param   array   options no option allowed
     *
     */
    function rotate($angle, $options=null)
    {
        if (!is_numeric($angle)) die("angle must be numeric");
        if ('-' == $angle{0}) {
            $angle = 360 - substr($angle, 1);
        }
         $this->command['rotate'] = "-rotate $angle";
    } // End rotate

    /**
     * addText
     *
     * @param   array   options     Array contains options
     *                              array(
     *                                  'text'  The string to draw
     *                                  'x'     Horizontal position
     *                                  'y'     Vertical Position
     *                                  'Color' Font color
     *                                  'font'  Font to be used
     *                                  'size'  Size of the fonts in pixel
     *                                  'resize_first'  Tell if the image has to be resized
     *                                                  before drawing the text
     *                              )
     *
     * @return none
     * @see PEAR::isError()
     */
    function addText($params)
    {
        $default_params = array(
                                'text' => 'This is Text',
                                'x' => 10,
                                'y' => 20,
                                'color' => 'red',
                                'font' => 'Arial.ttf',
                                'resize_first' => false // Carry out the scaling of the image before annotation?
                                );
         $params = array_merge($default_params, $params);
         extract($params);
         if (true === $resize_first) {
             // Set the key so that this will be the last item in the array
            $key = 'ztext';
         } else {
            $key = 'text';
         }
		 // test input params
         if (!is_numeric($x)) die("x must be numeric");
         if (!is_numeric($y)) die("y must be numeric");
         // escape others
         $font = escapeshellarg($font);
         $color = escapeshellarg($color);
         $text = escapeshellarg($text);
         $this->command[$key] = "-font $font -fill $color -draw 'text $x,$y \"$text\"'";
         // Producing error: gs: not found gs: not found convert: Postscript delegate failed [No such file or directory].
    } // End addText

    /**
     * Adjust the image gamma
     *
     * @param float $outputgamma
     *
     * @return none
     */
    function gamma($outputgamma=1.0) {
        if (!is_numeric($outputgamma)) die("outputgamma must be numeric");
        $this->command['gamma'] = "-gamma $outputgamma";
    }

    /**
     * Save the image file
     *
     * @param $filename string  the name of the file to write to
     * @param $quality  quality image dpi, default=75
     * @param $type     string  (JPG,PNG...)
     *
     * @return none
     */
    function save($filename, $type='', $quality = 85)
    {
        if (!is_numeric($quality)) die("quality must be numeric");
        $type == '' ? $this->type : $type;
        $cmd = '' . IMAGE_TRANSFORM_LIB_PATH . 'convert ';
		$cmd .= implode(' ', $this->command) . " -quality $quality ";
		$cmd .= '"'.($this->image) . '" "' . ($filename) . '" 2>&1';

        //$cmd = str_replace('/', '\\', $cmd);
		//echo($cmd.'<br>');
        exec($cmd,$retval);
    } // End save

    /**
     * Display image without saving and lose changes
     *
     * @param string type (JPG,PNG...);
     * @param int quality 75
     *
     * @return none
     */
    function display($type = '', $quality = 75)
    {
        if (!is_numeric($quality)) die("quality must be numeric");
        if ($type == '') {
            header('Content-type: image/' . $this->type);
            passthru(IMAGE_TRANSFORM_LIB_PATH . 'convert ' . implode(' ', $this->command) . " -quality $quality "  . escapeshellarg($this->image) . ' ' . strtoupper($this->type) . ":-");
        } else {
            header('Content-type: image/' . $type);
            passthru(IMAGE_TRANSFORM_LIB_PATH . 'convert ' . implode(' ', $this->command) . " -quality $quality "  . escapeshellarg($this->image) . ' ' . strtoupper($type) . ":-");
        }
    }


    /**
     * Destroy image handle
     *
     * @return none
     */
    function free()
    {
        return true;
    }

} // End class ImageIM
?>
