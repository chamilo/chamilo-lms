<?php

class image {

    var $bg;
    var $logo;
    var $logox;
    var $logoy;
    var $bgx;
    var $bgy;
    var $fontfile='./verdana';
    

    var $color;

    function image($bgfile='') {
            
            image::addbackground($bgfile);
            
    }

    function createimagefromtype($file,$handler) {
            $size = @getimagesize(api_url_to_local_path($file));
            $type=$size[2];
   
            switch ($type) {
                    case 2 : $imhandler = @imagecreatefromjpeg($file);
                    break;
                    case 3 : $imhandler = @imagecreatefrompng($file);
                    break;
                    case 1 : $imhandler = @imagecreatefromgif($file);
                    break;
            }

            $xtmpstr=$handler.'x';
            $ytmpstr=$handler.'y';

 
                $this->$xtmpstr=$size[0];
                $this->$ytmpstr=$size[1];

                return $imhandler;
        }

        function resize ( $thumbw , $thumbh , $border) {

                $size [0]=$this->bgx;
                $size [1]=$this->bgy;

                if ($border==1) {
                        $scale = min($thumbw/$size[0], $thumbh/$size[1]);
                        $width = (int)($size[0]*$scale);
                        $height = (int)($size[1]*$scale);
                        $deltaw = (int)(($thumbw - $width)/2);
                        $deltah = (int)(($thumbh - $height)/2);
                                $dst_img = @ImageCreateTrueColor($thumbw, $thumbh);
                                if ( !empty($this->color))
                                        @imagefill ( $dst_img, 0, 0, $this->color );
                                $this->bgx=$thumbw;
                                $this->bgy=$thumbh;
                }
                elseif ($border==0) {
                        $scale = ($size[0] > 0 && $size[1] >0)?min($thumbw/$size[0], $thumbh/$size[1]):0;
                        $width = (int)($size[0]*$scale);
                        $height = (int)($size[1]*$scale);
                        
                        $deltaw = 0;
                        $deltah = 0;
                                $dst_img = @ImageCreateTrueColor($width, $height);
                                $this->bgx=$width;
                                $this->bgy=$height;
                }

                $src_img = $this->bg;

                @ImageCopyResampled($dst_img, $src_img, $deltaw, $deltah, 0, 0, $width, $height, ImageSX($src_img),ImageSY($src_img));


                $this->bg=$dst_img;
                @imagedestroy($src_img);
        }

        function addbackground($bgfile) {
        
        		
                if ( !empty($bgfile) && file_exists($bgfile) ) { $this->bg = image::createimagefromtype($bgfile,'bg');
            @imagealphablending( $this->bg ,TRUE );
            }
    }

    function addlogo($file) {
            $this->logo = image::createimagefromtype($file,'logo');
            @imagealphablending( $this->logo ,TRUE );
            $size = @getimagesize(api_url_to_local_path($file));
            $this->logox=$size[0];
            $this->logoy=$size[1];

    }

    function mergelogo($x,$y,$alpha=100) {
             if ($x<0) $x=$this->bgx-$this->logox+$x;
             if ($y<0) $y=$this->bgy-$this->logoy+$y;
            return @imagecopymerge ( $this->bg, $this->logo, $x, $y, 0, 0, $this->logox, $this->logoy, $alpha );
    }

    function makecolor($red, $green, $blue) {
            $this->color = @imagecolorallocate( $this->bg, $red, $green, $blue );
    }

    function setfont($fontfile) {
            $this->fontfile=$fontfile;
    }

    function addtext ($text,$x=0,$y=0,$size=12,$angle=0) {
    	putenv('GDFONTPATH=' . realpath('.'));
    	$this->fontfile='verdana';
            $text= preg_replace('`(?<!\r)\n`',"\r\n",$text);
           
            $box=@imagettfbbox ( $size, $angle, $this->fontfile, $text );
            if ($x<0) {
                $x=$this->bgx - max($box[2],$box[4]) + $x;
            } else {
                $x=max(-$box[0],-$box[6]) + $x;
            }
            if ($y<0) {
                $y=$this->bgy - max($box[1],$box[3]) + $y;
            } else {
                $y=max(-$box[7],-$box[5]) + $y;
            }
            
            @imagettftext($this->bg, $size, $angle, $x, $y, $this->color, $this->fontfile , $text );
          
    }

    function send_image($type,$file='',$compress=-1) {

            switch ($type) {
                    case 'JPG' :
                            if (!$file) header("Content-type: image/jpeg");
                            if ($compress==-1) $compress=100;
                            return imagejpeg($this->bg,$file,$compress);
                    break;
                    case 'PNG' :
                            if (!$file) header("Content-type: image/png");
                            if ($compress!=-1)
                                    @imagetruecolortopalette ( $this->bg, true, $compress);
                            return imagepng($this->bg,$file,$compress);
                    break;
                    case 'GIF' :
                            if (!$file) header("Content-type: image/gif");
                            if ($compress!=-1)
                                    @imagetruecolortopalette ( $this->bg, true, $compress);
                            return imagegif($this->bg,$file,$compress);
                    break;
                    default: return 0;
            }
            
            // TODO: Occupied memory is not released, because the following fragment of code is actually dead.
            @imagedestroy($this->bg);
            @imagedestroy($this->logo);

    }

}

?>