<?php
/***********************************************************************
** Title.........:  NetPBM Driver
** Version.......:  1.0
** Author........:  Xiang Wei ZHUO <wei@zhuo.org>
** Filename......:  NetPBM.php
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
// $Id: NetPBM.php 27 2004-04-01 08:31:57Z Wei Zhuo $
//
// Image Transformation interface using command line NetPBM

require_once "Transform.php";

Class Image_Transform_Driver_NetPBM extends Image_Transform
{

    /**
     * associative array commands to be executed
     * @var array
     */
    var $command = array();

    /**
     * Class Constructor
     */
    function Image_Transform_Driver_NetPBM()
    {
        $this->uid = md5($_SERVER['REMOTE_ADDR']);

        return true;
    } // End function Image_NetPBM

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
        //echo $image;
        $this->image = $image;
        $this->_get_image_details($image);
    } // End load

    /**
     * Resizes the image
     *
     * @return none
     * @see PEAR::isError()
     */
    function _resize($new_x, $new_y)
    {
        // there's no technical reason why resize can't be called multiple
        // times...it's just silly to do so

        $this->command[] = IMAGE_TRANSFORM_LIB_PATH .
                           "pnmscale -width $new_x -height $new_y";

        $this->_set_new_x($new_x);
        $this->_set_new_y($new_y);
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
        $this->command[] = IMAGE_TRANSFORM_LIB_PATH .
                            "pnmcut -left $crop_x -top $crop_y -width $crop_width -height $crop_height";
    }

    /**
     * Rotates the image
     *
     * @param int $angle The angle to rotate the image through
     */
    function rotate($angle)
    {
        $angle = -1*floatval($angle);

        if($angle > 90)
        {
            $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "pnmrotate -noantialias 90";
            $this->rotate(-1*($angle-90));
        }
        else if ($angle < -90)
        {
            $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "pnmrotate -noantialias -90";
            $this->rotate(-1*($angle+90));
        }
        else
            $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "pnmrotate -noantialias $angle";
    } // End rotate

    /**
     * Flip the image horizontally or vertically
     *
     * @param boolean $horizontal true if horizontal flip, vertical otherwise
     */
    function flip($horizontal)
    {
        if($horizontal)
            $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "pnmflip -lr";
        else
            $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "pnmflip -tb";
    }

    /**
     * Adjust the image gamma
     *
     * @param float $outputgamma
     *
     * @return none
     */
    function gamma($outputgamma = 1.0) {
        $this->command[13] = IMAGE_TRANSFORM_LIB_PATH . "pnmgamma $outputgamma";
    }

    /**
     * adds text to an image
     *
     * @param   array   options     Array contains options
     *             array(
     *                  'text'          // The string to draw
     *                  'x'             // Horizontal position
     *                  'y'             // Vertical Position
     *                  'Color'         // Font color
     *                  'font'          // Font to be used
     *                  'size'          // Size of the fonts in pixel
     *                  'resize_first'  // Tell if the image has to be resized
     *                                  // before drawing the text
     *                   )
     *
     * @return none
     */
    function addText($params)
    {
        $default_params = array('text' => 'This is Text',
                                'x' => 10,
                                'y' => 20,
                                'color' => 'red',
                                'font' => 'Arial.ttf',
                                'size' => '12',
                                'angle' => 0,
                                'resize_first' => false);
        // we ignore 'resize_first' since the more logical approach would be
        // for the user to just call $this->_resize() _first_ ;)
        extract(array_merge($default_params, $params));
        $this->command[] = "ppmlabel -angle $angle -colour $color -size "
                           ."$size -x $x -y ".$y+$size." -text \"$text\"";
    } // End addText

    function _postProcess($type, $quality, $save_type)
    {
        $type = is_null($type) || $type==''? $this->type : $type;
        $save_type = is_null($save_type) || $save_type==''? $this->type : $save_type;
        //echo "TYPE:". $this->type;
        array_unshift($this->command, IMAGE_TRANSFORM_LIB_PATH
                      . $type.'topnm '. $this->image);
        $arg = '';
        switch(strtolower($save_type)){
            case 'gif':
                $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "ppmquant 256";
                $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "ppmto$save_type";
                break;
            case 'jpg':
            case 'jpeg':
                $arg = "--quality=$quality";
                $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "ppmto$save_type $arg";
                break;
            default:
                $this->command[] = IMAGE_TRANSFORM_LIB_PATH . "pnmto$save_type $arg";
                break;
        } // switch
        return implode('|', $this->command);
    }

    /**
     * Save the image file
     *
     * @param $filename string the name of the file to write to
     * @param string $type (jpeg,png...);
     * @param int $quality 75
     * @return none
     */
    function save($filename, $type=null, $quality = 85)
    {
        $cmd = $this->_postProcess('', $quality, $type) . ">$filename";

		//if we have windows server
        if(isset($_ENV['OS']) && eregi('window',$_ENV['OS']))
			$cmd = ereg_replace('/','\\',$cmd);
        //echo $cmd."##";
        $output = system($cmd);
		error_log('NETPBM: '.$cmd);
		//error_log($output);
        $this->command = array();
    } // End save


    /**
     * Display image without saving and lose changes
     *
     * @param string $type (jpeg,png...);
     * @param int $quality 75
     * @return none
     */
    function display($type = null, $quality = 75)
    {
        header('Content-type: image/' . $type);
        $cmd = $this->_postProcess($type, $quality);

        passthru($cmd);
        $this->command = array();
    }

    /**
     * Destroy image handle
     *
     * @return none
     */
    function free()
    {
        // there is no image handle here
        return true;
    }


} // End class NetPBM
?>
