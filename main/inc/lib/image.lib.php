<?php
/* For licensing terms, see /license.txt */
/**
 * This class provides a layer to manage images
 * @author Julio Montoya <gugli100@gmail.com>
 * @package chamilo.include.image
 * @todo move in a DB configuration setting
 */
define('IMAGE_PROCESSOR', 'gd'); // imagick or gd strings
/**
 * Image class
 * @package chamilo.include.image
 */
class Image
{
    public $image_wrapper = null;

    function __construct($path)
    {
        $path = preg_match(VALID_WEB_PATH, $path) ? (api_is_internal_path(
            $path
        ) ? api_get_path(TO_SYS, $path) : $path) : $path;
        if (IMAGE_PROCESSOR == 'gd') {
            $this->image_wrapper = new GDWrapper($path);
        } else {
            if (class_exists('Imagick')) {
                $this->image_wrapper = new ImagickWrapper($path);
            } else {
                Display::display_warning_message('Class Imagick not found');
                exit;
            }
        }
    }

    public function resize(
        $thumbw,
        $thumbh,
        $border = 0,
        $specific_size = false
    ) {
        $this->image_wrapper->resize($thumbw, $thumbh, $border, $specific_size);
    }

    public function send_image(
        $file = '',
        $compress = -1,
        $convert_file_to = null
    ) {
        return $this->image_wrapper->send_image(
            $file,
            $compress,
            $convert_file_to
        );
    }

    public function get_image_size()
    {
        return $this->image_wrapper->get_image_size();
    }

    public function get_image_info()
    {
        return $this->image_wrapper->get_image_info();
    }

    public function convert2bw()
    {
        $this->image_wrapper->convert2bw();
    }
}

/**
 * Image wrapper class
 *
 * @package chamilo.include.image
 */
abstract class ImageWrapper
{
    var $debug = true;
    var $path;
    var $width;
    var $height;
    var $type;
    var $allowed_extensions = array('jpeg', 'jpg', 'png', 'gif');
    var $image_validated = false;

    public function __construct($path)
    {
        if (empty($path)) {
            return false;
        }
        $this->path = preg_match(VALID_WEB_PATH, $path) ? (api_is_internal_path($path) ? api_get_path(TO_SYS, $path) : $path) : $path;
        $this->set_image_wrapper();  //Creates image obj
    }

    abstract function set_image_wrapper();
    abstract function fill_image_info();
    abstract function get_image_size();
    abstract function resize($thumbw, $thumbh, $border, $specific_size = false);
    abstract function send_image($file = '', $compress = -1, $convert_file_to = null);

    public function get_image_info()
    {
        return array(
            'width' => $this->width,
            'height' => $this->height,
            'type' => $this->type,
        );
    }
}

/**
 * Imagick Chamilo wrapper
 *
 * @author jmontoya
 *
 * @package chamilo.include.image
 */
class ImagickWrapper extends ImageWrapper
{
    var $image;
    var $filter = Imagick::FILTER_LANCZOS;

    public function __construct($path) {
          parent::__construct($path);
    }
    public function set_image_wrapper() {
        if ($this->debug) error_log('Image::set_image_wrapper loaded');
        try {
            if (file_exists($this->path)) {
                $this->image     = new Imagick($this->path);

                if ($this->image) {
                    $this->fill_image_info(); //Fills height, width and type
                }
            } else {
                if ($this->debug) error_log('Image::image does not exist');
            }
        } catch(ImagickException $e) {
            if ($this->debug) error_log($e->getMessage());
        }
    }

    public function fill_image_info() {
        $image_info      = $this->image->identifyImage();

        $this->width     = $image_info['geometry']['width'];
        $this->height    = $image_info['geometry']['height'];
        $this->type      = strtolower($this->image->getImageFormat());

        if (in_array($this->type, $this->allowed_extensions)) {
            $this->image_validated = true;
            if ($this->debug) error_log('image_validated true');
        }
    }

	public function get_image_size() {
		$imagesize = array('width'=>0,'height'=>0);
	    if ($this->image_validated) {
            $imagesize = $this->image->getImageGeometry();
	    }
	    return $imagesize;
	}

	//@todo implement border logic case for Imagick
	public function resize($thumbw, $thumbh, $border, $specific_size = false) {
	    if (!$this->image_validated) return false;

        if ($specific_size) {
            $width = $thumbw;
            $height = $thumbh;
        } else {
            $scale  = ($this->width > 0 && $this->height > 0) ? min($thumbw / $this->width, $thumbh / $this->height) : 0;
            $width  = (int)($this->width * $scale);
            $height = (int)($this->height * $scale);
        }
		$result = $this->image->resizeImage($width, $height, $this->filter, 1);
		$this->width  = $thumbw;
		$this->height = $thumbh;
	}

    public function send_image($file = '', $compress = -1, $convert_file_to = null) {
        if (!$this->image_validated) return false;
        $type = $this->type;
        if (!empty($convert_file_to) && in_array($convert_file_to, $this->allowed_extensions)) {
            $type = $convert_file_to;
        }
		switch ($type) {
		    case 'jpeg':
			case 'jpg':
				if (!$file) header("Content-type: image/jpeg");
				break;
			case 'png':
				if (!$file) header("Content-type: image/png");
				break;
			case 'gif':
				if (!$file) header("Content-type: image/gif");
				break;
		}
		$result = false;
		try {
		    $result = $this->image->writeImage($file);
		} catch(ImagickException $e) {
            if ($this->debug) error_log($e->getMessage());
        }

		if (!$file) {
		    echo $this->image;
		    $this->image->clear();
            $this->image->destroy();
		} else {
		    $this->image->clear();
            $this->image->destroy();
		    return $result;
		}
	}

}

/**
 * php-gd wrapper
 * @package chamilo.include.image
 */
class GDWrapper extends ImageWrapper
{
    var $bg;

    function __construct($path) {
        parent::__construct($path);
    }

    public function set_image_wrapper()
    {
        $handler = null;
        $this->fill_image_info();

        switch ($this->type) {
        	case 0:
        		$handler = false;
        		break;
		    case 1 :
                $handler = @imagecreatefromgif($this->path);
                $this->type = 'gif';
                break;
		    case 2 :
                $handler = @imagecreatefromjpeg($this->path);
                $this->type = 'jpg';
                break;
		    case 3 :
		        $handler = @imagecreatefrompng($this->path);
		        $this->type = 'png';
		        break;
        }
        if ($handler) {
            $this->image_validated = true;
            $this->bg = $handler;
            @imagealphablending($this->bg, false);
            @imagesavealpha($this->bg, true);
        }
    }

    public function get_image_size()
    {
        $return_array = array('width'=>0,'height'=>0);
        if ($this->image_validated) {
	        $return_array = array('width'=>$this->width,'height'=>$this->height);
        }
        return $return_array;
	}

    public function fill_image_info()
    {
    	if (file_exists($this->path)) {
	        $image_info     = getimagesize($this->path);
			$this->width    = $image_info[0];
			$this->height   = $image_info[1];
			$this->type     = $image_info[2];
    	} else {
    		$this->width    = 0;
    		$this->height   = 0;
    		$this->type     = 0;
    	}
    }

    public function resize($thumbw, $thumbh, $border, $specific_size = false)
    {
        if (!$this->image_validated) return false;
		if ($border == 1) {
            if ($specific_size) {
                $width = $thumbw;
                $height = $thumbh;
            } else {
                $scale = min($thumbw / $this->width, $thumbh / $this->height);
                $width = (int)($this->width * $scale);
                $height = (int)($this->height * $scale);
            }
			$deltaw = (int)(($thumbw - $width) / 2);
			$deltah = (int)(($thumbh - $height) / 2);
			$dst_img = @ImageCreateTrueColor($thumbw, $thumbh);
            		@imagealphablending($dst_img, false);
		        @imagesavealpha($dst_img, true);
			if (!empty($this->color)) {
				@imagefill($dst_img, 0, 0, $this->color);
			}
			$this->width = $thumbw;
			$this->height = $thumbh;
		} elseif ($border == 0) {
            if ($specific_size) {
                $width = $thumbw;
                $height = $thumbh;
            } else {
                $scale = ($this->width > 0 && $this->height > 0) ? min($thumbw / $this->width, $thumbh / $this->height) : 0;
                $width  = (int)($this->width * $scale);
                $height = (int)($this->height * $scale);
            }
			$deltaw = 0;
			$deltah = 0;
			$dst_img = @ImageCreateTrueColor($width, $height);
            		@imagealphablending($dst_img, false);
		        @imagesavealpha($dst_img, true);
			$this->width = $width;
			$this->height = $height;
		}
		$src_img = $this->bg;
		@ImageCopyResampled($dst_img, $src_img, $deltaw, $deltah, 0, 0, $width, $height, ImageSX($src_img), ImageSY($src_img));
		$this->bg = $dst_img;
		@imagedestroy($src_img);
	}

	public function send_image($file = '', $compress = -1, $convert_file_to = null) {
	    if (!$this->image_validated) return false;
        $compress = (int)$compress;
        $type = $this->type;
        if (!empty($convert_file_to) && in_array($convert_file_to, $this->allowed_extensions)) {
            $type = $convert_file_to;
        }
		switch ($type) {
		    case 'jpeg':
			case 'jpg':
				if (!$file) header("Content-type: image/jpeg");
				if ($compress == -1) $compress = 100;
				return imagejpeg($this->bg, $file, $compress);
				break;
			case 'png':
				if (!$file) header("Content-type: image/png");
				if ($compress != -1) {
					@imagetruecolortopalette($this->bg, true, $compress);
				}
				return imagepng($this->bg, $file, $compress);
				break;
			case 'gif':
				if (!$file) header("Content-type: image/gif");
				if ($compress != -1) {
					@imagetruecolortopalette($this->bg, true, $compress);
				}
				return imagegif($this->bg, $file, $compress);
				break;
			default: return 0;
		}
		// TODO: Occupied memory is not released, because the following fragment of code is actually dead.
		@imagedestroy($this->bg);
	}

    /**
     * Convert image to black & white
     */
    function convert2bw()
    {
        if (!$this->image_validated) return false;

        $dest_img = imagecreatetruecolor(imagesx($this->bg), imagesy($this->bg));
        /* copy ignore the transparent color
         * so that we can use black (0,0,0) as transparent, which is what
         * the image is filled with when created.
         */
        $transparent = imagecolorallocate($dest_img, 0,0,0);
        imagealphablending($dest_img, false);
        imagesavealpha($dest_img, true);
        imagecolortransparent($dest_img, $transparent);
        imagecopy($dest_img, $this->bg, 0,0, 0, 0,imagesx($this->bg), imagesx($this->bg));
        imagefilter($dest_img, IMG_FILTER_GRAYSCALE);
        $this->bg = $dest_img;

        return true;
    }
}
