<?php
/***********************************************************************
** Title.........:  GD Driver
** Version.......:  1.0
** Author........:  Xiang Wei ZHUO <wei@zhuo.org>
** Filename......:  GD.php
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
// |          Alan Knowles <alan@akbkhome.com>                            |
// +----------------------------------------------------------------------+
//
//    Usage :
//    $img    = new Image_Transform_GD();
//    $angle  = -78;
//    $img->load('magick.png');
//
//    if($img->rotate($angle,array('autoresize'=>true,'color_mask'=>array(255,0,0)))){
//        $img->addText(array('text'=>"Rotation $angle",'x'=>0,'y'=>100,'font'=>'/usr/share/fonts/default/TrueType/cogb____.ttf'));
//        $img->display();
//    } else {
//        echo "Error";
//    }
//
//
// $Id: GD.php 26 2004-03-31 02:35:21Z Wei Zhuo $
//
// Image Transformation interface using the GD library
//

require_once "Transform.php";

Class Image_Transform_Driver_GD extends Image_Transform
{
    /**
     * Holds the image file for manipulation
     */
    var $imageHandle = '';

    /**
     * Holds the original image file
     */
    var $old_image = '';

    /**
     * Check settings
     *
     * @return mixed true or  or a PEAR error object on error
     *
     * @see PEAR::isError()
     */
    function Image_Transform_GD()
    {
        return;
    } // End function Image

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
        $this->image = $image;
        $this->_get_image_details($image);
        $functionName = 'ImageCreateFrom' . $this->type;
		if(function_exists($functionName))
		{
			$this->imageHandle = $functionName($this->image);
		}
    } // End load

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
                                'color' => array(255,0,0),
                                'font' => 'Arial.ttf',
                                'size' => '12',
                                'angle' => 0,
                                'resize_first' => false // Carry out the scaling of the image before annotation?  Not used for GD
                                );
        $params = array_merge($default_params, $params);
        extract($params);

        if( !is_array($color) ){
            if ($color[0]=='#'){
                $this->colorhex2colorarray( $color );
            } else {
                include_once('Image/Transform/Driver/ColorsDefs.php');
                $color = isset($colornames[$color])?$colornames[$color]:false;
            }
        }

        $c = imagecolorresolve ($this->imageHandle, $color[0], $color[1], $color[2]);

        if ('ttf' == substr($font, -3)) {
            ImageTTFText($this->imageHandle, $size, $angle, $x, $y, $c, $font, $text);
        } else {
            ImagePSText($this->imageHandle, $size, $angle, $x, $y, $c, $font, $text);
        }
        return true;
    } // End addText


    /**
     * Rotate image by the given angle
     * Uses a fast rotation algorythm for custom angles
     * or lines copy for multiple of 90 degrees
     *
     * @param int       $angle      Rotation angle
     * @param array     $options    array(  'autoresize'=>true|false,
     *                                      'color_mask'=>array(r,g,b), named color or #rrggbb
     *                                   )
     * @author Pierre-Alain Joye
     * @return mixed none or a PEAR error object on error
     * @see PEAR::isError()
     */
    function rotate($angle, $options=null)
    {
        if(function_exists('imagerotate') && false) {
            $white = imagecolorallocate ($this->imageHandle, 255, 255, 255);
			$this->imageHandle = imagerotate($this->imageHandle, $angle, $white);
            return true;
        }

        if ( $options==null ){
            $autoresize = true;
            $color_mask = array(255,255,0);
        } else {
            extract( $options );
        }

        while ($angle <= -45) {
            $angle  += 360;
        }
        while ($angle > 270) {
            $angle  -= 360;
        }

        $t      = deg2rad($angle);

        if( !is_array($color_mask) ){
            if ($color[0]=='#'){
                $this->colorhex2colorarray( $color_mask );
            } else {
                include_once('Image/Transform/Driver/ColorDefs.php');
                $color = isset($colornames[$color_mask])?$colornames[$color_mask]:false;
            }
        }

        // Do not round it, too much lost of quality
        $cosT   = cos($t);
        $sinT   = sin($t);

        $img    =& $this->imageHandle;

        $width  = $max_x  = $this->img_x;
        $height = $max_y  = $this->img_y;
        $min_y  = 0;
        $min_x  = 0;

        $x1     = round($max_x/2,0);
        $y1     = round($max_y/2,0);

        if ( $autoresize ){
            $t      = abs($t);
            $a      = round($angle,0);
            switch((int)($angle)){
                case 0:
                        $width2     = $width;
                        $height2    = $height;
                    break;
                case 90:
                        $width2     = $height;
                        $height2    = $width;
                    break;
                case 180:
                        $width2     = $width;
                        $height2    = $height;
                    break;
                case 270:
                        $width2     = $height;
                        $height2    = $width;
                    break;
                default:
                    $width2     = (int)(abs(sin($t) * $height + cos($t) * $width));
                    $height2    = (int)(abs(cos($t) * $height+sin($t) * $width));
            }

            $width2     -= $width2%2;
            $height2    -= $height2%2;

            $d_width    = abs($width - $width2);
            $d_height   = abs($height - $height2);
            $x_offset   = $d_width/2;
            $y_offset   = $d_height/2;
            $min_x2     = -abs($x_offset);
            $min_y2     = -abs($y_offset);
            $max_x2     = $width2;
            $max_y2     = $height2;
        }

        if(function_exists('ImageCreateTrueColor')){
            $img2 =ImageCreateTrueColor($width2,$height2);
        } else {
            $img2 =ImageCreate($width2,$height2);
        }


        if ( !is_resource($img2) ){
            return false;/*PEAR::raiseError('Cannot create buffer for the rotataion.',
                                null, PEAR_ERROR_TRIGGER, E_USER_NOTICE);*/
        }

        $this->img_x = $width2;
        $this->img_y = $height2;


        imagepalettecopy($img2,$img);

        $mask   = imagecolorresolve($img2,$color_mask[0],$color_mask[1],$color_mask[2]);

        // use simple lines copy for axes angles
        switch((int)($angle)){
            case 0:
                imagefill ($img2, 0, 0,$mask);
                for ($y=0; $y < $max_y; $y++) {
                    for ($x = $min_x; $x < $max_x; $x++){
                        $c  = @imagecolorat ( $img, $x, $y);
                        imagesetpixel($img2,$x+$x_offset,$y+$y_offset,$c);
                    }
                }
                break;
            case 90:
                imagefill ($img2, 0, 0,$mask);
                for ($x = $min_x; $x < $max_x; $x++){
                    for ($y=$min_y; $y < $max_y; $y++) {
                        $c  = imagecolorat ( $img, $x, $y);
                        imagesetpixel($img2,$max_y-$y-1,$x,$c);
                    }
                }
                break;
            case 180:
                imagefill ($img2, 0, 0,$mask);
                for ($y=0; $y < $max_y; $y++) {
                    for ($x = $min_x; $x < $max_x; $x++){
                        $c  = @imagecolorat ( $img, $x, $y);
                        imagesetpixel($img2, $max_x2-$x-1, $max_y2-$y-1, $c);
                    }
                }
                break;
            case 270:
                imagefill ($img2, 0, 0,$mask);
                for ($y=0; $y < $max_y; $y++) {
                    for ($x = $max_x; $x >= $min_x; $x--){
                        $c  = @imagecolorat ( $img, $x, $y);
                        imagesetpixel($img2,$y,$max_x-$x-1,$c);
                    }
                }
                break;
            // simple reverse rotation algo
            default:
                $i=0;
                for ($y = $min_y2; $y < $max_y2; $y++){

                    // Algebra :)
                    $x2 = round((($min_x2-$x1) * $cosT) + (($y-$y1) * $sinT + $x1),0);
                    $y2 = round((($y-$y1) * $cosT - ($min_x2-$x1) * $sinT + $y1),0);

                    for ($x = $min_x2; $x < $max_x2; $x++){

                        // Check if we are out of original bounces, if we are
                        // use the default color mask
                        if ( $x2>=0 && $x2<$max_x && $y2>=0 && $y2<$max_y ){
                            $c  = imagecolorat ( $img, $x2, $y2);
                        } else {
                            $c  = $mask;
                        }
                        imagesetpixel($img2,$x+$x_offset,$y+$y_offset,$c);

                        // round verboten!
                        $x2  += $cosT;
                        $y2  -= $sinT;
                    }
                }
                break;
        }
        $this->old_image    = $this->imageHandle;
        $this->imageHandle  =  $img2;
        return true;
    }


   /**
    * Resize Action
    *
    * For GD 2.01+ the new copyresampled function is used
    * It uses a bicubic interpolation algorithm to get far
    * better result.
    *
    * @param $new_x int  new width
    * @param $new_y int  new height
    *
    * @return true on success or pear error
    * @see PEAR::isError()
    */
    function _resize($new_x, $new_y) {
        if ($this->resized === true) {
            return false; /*PEAR::raiseError('You have already resized the image without saving it.  Your previous resizing will be overwritten', null, PEAR_ERROR_TRIGGER, E_USER_NOTICE);*/
        }
        if(function_exists('ImageCreateTrueColor')){
            $new_img = @ImageCreateTrueColor($new_x,$new_y);
        } else {
            $new_img = @ImageCreate($new_x,$new_y);
        }
        if(function_exists('ImageCopyResampled')){
            @ImageCopyResampled($new_img, $this->imageHandle, 0, 0, 0, 0, $new_x, $new_y, $this->img_x, $this->img_y);
        } else {
            @ImageCopyResized($new_img, $this->imageHandle, 0, 0, 0, 0, $new_x, $new_y, $this->img_x, $this->img_y);
        }
        $this->old_image = $this->imageHandle;
        $this->imageHandle = $new_img;
        $this->resized = true;

        $this->new_x = $new_x;
        $this->new_y = $new_y;
        return true;
    }

    /**
     * Crop the image
     *
     * @param int $crop_x left column of the image
     * @param int $crop_y top row of the image
     * @param int $crop_width new cropped image width
     * @param int $crop_height new cropped image height
     */
    function crop($new_x, $new_y, $new_width, $new_height)
    {
        if(function_exists('ImageCreateTrueColor')){
            $new_img =ImageCreateTrueColor($new_width,$new_height);
        } else {
            $new_img =ImageCreate($new_width,$new_height);
        }
        if(function_exists('ImageCopyResampled')){
            ImageCopyResampled($new_img, $this->imageHandle, 0, 0, $new_x, $new_y,$new_width,$new_height,$new_width,$new_height);
        } else {
            ImageCopyResized($new_img, $this->imageHandle, 0, 0, $new_x, $new_y, $new_width,$new_height,$new_width,$new_height);
        }
        $this->old_image = $this->imageHandle;
        $this->imageHandle = $new_img;
        $this->resized = true;

        $this->new_x = $new_x;
        $this->new_y = $new_y;
        return true;
    }

    /**
     * Flip the image horizontally or vertically
     *
     * @param boolean $horizontal true if horizontal flip, vertical otherwise
     */
    function flip($horizontal)
    {
        if(!$horizontal) {
            $this->rotate(180);
        }

        $width = imagesx($this->imageHandle);
        $height = imagesy($this->imageHandle);

        for ($j = 0; $j < $height; $j++) {
                $left = 0;
                $right = $width-1;


                while ($left < $right) {
                    //echo " j:".$j." l:".$left." r:".$right."\n<br>";
                    $t = imagecolorat($this->imageHandle, $left, $j);
                    imagesetpixel($this->imageHandle, $left, $j, imagecolorat($this->imageHandle, $right, $j));
                    imagesetpixel($this->imageHandle, $right, $j, $t);
                    $left++; $right--;
                }

        }

        return true;
    }


    /**
     * Adjust the image gamma
     *
     * @param float $outputgamma
     *
     * @return none
     */
    function gamma($outputgamma=1.0) {
        ImageGammaCorrect($this->imageHandle, 1.0, $outputgamma);
    }

    /**
     * Save the image file
     *
     * @param $filename string  the name of the file to write to
     * @param $quality  int     output DPI, default is 85
     * @param $types    string  define the output format, default
     *                          is the current used format
     *
     * @return none
     */
    function save($filename, $type = '', $quality = 85)
    {
		$type           = $type==''? $this->type : $type;
		$functionName   = 'image' . $type;

		if(function_exists($functionName))
		{
			$this->old_image = $this->imageHandle;
			if($type=='jpeg')
				$functionName($this->imageHandle, $filename, $quality);
			else
				$functionName($this->imageHandle, $filename);
			$this->imageHandle = $this->old_image;
			$this->resized = false;
		}
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
        if ($type != '') {
            $this->type = $type;
        }
        $functionName = 'Image' . $this->type;
		if(function_exists($functionName))
		{
			header('Content-type: image/' . strtolower($this->type));
			$functionName($this->imageHandle, '', $quality);
			$this->imageHandle = $this->old_image;
			$this->resized = false;
			ImageDestroy($this->old_image);
			$this->free();
		}
    }

    /**
     * Destroy image handle
     *
     * @return none
     */
    function free()
    {
        if ($this->imageHandle){
            ImageDestroy($this->imageHandle);
        }
    }

} // End class ImageGD
?>
