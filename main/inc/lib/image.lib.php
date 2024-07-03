<?php
/* For licensing terms, see /license.txt */

/**
 * Image class
 * This class provides a layer to manage images.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 *
 * @todo move in a DB configuration setting
 */
class Image
{
    public $image_wrapper = null;

    /**
     * Image constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        if (IMAGE_PROCESSOR == 'gd') {
            $this->image_wrapper = new GDWrapper($path);
        } else {
            if (class_exists('Imagick')) {
                $this->image_wrapper = new ImagickWrapper($path);
            } else {
                echo Display::return_message(
                    'Class Imagick not found',
                    'warning'
                );
                exit;
            }
        }
    }

    public function resize($max_size_for_picture)
    {
        $image_size = $this->get_image_size($this->image_wrapper->path);
        $width = $image_size['width'];
        $height = $image_size['height'];
        if ($width >= $height) {
            if ($width >= $max_size_for_picture) {
                // scale height
                $new_height = round($height * ($max_size_for_picture / $width));
                $this->image_wrapper->resize(
                    $max_size_for_picture,
                    $new_height,
                    0
                );
            }
        } else { // height > $width
            if ($height >= $max_size_for_picture) {
                // scale width
                $new_width = round($width * ($max_size_for_picture / $height));
                $this->image_wrapper->resize(
                    $new_width,
                    $max_size_for_picture,
                    0
                );
            }
        }
    }

    /**
     * @param string $cropParameters
     *
     * @return bool
     */
    public function crop($cropParameters)
    {
        $image_size = $this->get_image_size($this->image_wrapper->path);
        $src_width = $image_size['width'];
        $src_height = $image_size['height'];
        $cropParameters = explode(',', $cropParameters);

        if (isset($cropParameters[0]) && isset($cropParameters[1])) {
            $x = intval($cropParameters[0]);
            $y = intval($cropParameters[1]);
            $width = intval($cropParameters[2]);
            $height = intval($cropParameters[3]);

            $image = $this->image_wrapper->crop(
                $x,
                $y,
                $width,
                $height,
                $src_width,
                $src_height
            );

            return $image;
        }

        return false;
    }

    /**
     * @param string $file
     * @param int    $compress
     * @param null   $convert_file_to
     *
     * @return bool
     */
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

    /**
     * @return array
     */
    public function get_image_size()
    {
        return $this->image_wrapper->get_image_size();
    }

    /**
     * @return array
     */
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
 * Image wrapper class.
 */
abstract class ImageWrapper
{
    public $debug = true;
    public $path;
    public $width;
    public $height;
    public $type;
    public $allowed_extensions = ['jpeg', 'jpg', 'png', 'gif'];
    public $image_validated = false;

    public function __construct($path)
    {
        if (empty($path)) {
            return false;
        }
        $this->path = $path;
        $this->set_image_wrapper(); //Creates image obj
    }

    abstract public function set_image_wrapper();

    abstract public function fill_image_info();

    abstract public function get_image_size();

    abstract public function resize($thumbw, $thumbh, $border, $specific_size = false);

    abstract public function crop($x, $y, $width, $height, $src_width, $src_height);

    abstract public function send_image($file = '', $compress = -1, $convert_file_to = null);

    /**
     * @return array
     */
    public function get_image_info()
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'type' => $this->type,
        ];
    }
}

/**
 * Imagick Chamilo wrapper.
 *
 * @author jmontoya
 */
class ImagickWrapper extends ImageWrapper
{
    public $image;
    public $filter = Imagick::FILTER_LANCZOS;

    /**
     * ImagickWrapper constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
        parent::__construct($path);
    }

    public function set_image_wrapper()
    {
        if ($this->debug) {
            error_log('Image::set_image_wrapper loaded');
        }
        try {
            if (file_exists($this->path)) {
                $this->image = new Imagick($this->path);

                if ($this->image) {
                    $this->fill_image_info(); //Fills height, width and type
                }
            } else {
                if ($this->debug) {
                    error_log('Image::image does not exist');
                }
            }
        } catch (ImagickException $e) {
            if ($this->debug) {
                error_log($e->getMessage());
            }
        }
    }

    public function fill_image_info()
    {
        $image_info = $this->image->identifyImage();
        $this->width = $image_info['geometry']['width'];
        $this->height = $image_info['geometry']['height'];
        $this->type = strtolower($this->image->getImageFormat());

        if (in_array($this->type, $this->allowed_extensions)) {
            $this->image_validated = true;
            if ($this->debug) {
                error_log('image_validated true');
            }
        }
    }

    public function get_image_size()
    {
        $imagesize = ['width' => 0, 'height' => 0];
        if ($this->image_validated) {
            $imagesize = $this->image->getImageGeometry();
        }

        return $imagesize;
    }

    //@todo implement border logic case for Imagick
    public function resize($thumbw, $thumbh, $border, $specific_size = false)
    {
        if (!$this->image_validated) {
            return false;
        }

        if ($specific_size) {
            $width = $thumbw;
            $height = $thumbh;
        } else {
            $scale = ($this->width > 0 && $this->height > 0) ? min($thumbw / $this->width, $thumbh / $this->height) : 0;
            $width = (int) ($this->width * $scale);
            $height = (int) ($this->height * $scale);
        }
        $result = $this->image->resizeImage($width, $height, $this->filter, 1);
        $this->width = $thumbw;
        $this->height = $thumbh;
    }

    /**
     * @author José Loguercio <jose.loguercio@beeznest.com>
     *
     * @param int $x          coordinate of the cropped region top left corner
     * @param int $y          coordinate of the cropped region top left corner
     * @param int $width      the width of the crop
     * @param int $height     the height of the crop
     * @param int $src_width  the source width of the original image
     * @param int $src_height the source height of the original image
     *
     * @return bool
     */
    public function crop($x, $y, $width, $height, $src_width, $src_height)
    {
        if (!$this->image_validated) {
            return false;
        }
        $this->image->cropimage($width, $height, $x, $y);
        $this->width = $width;
        $this->height = $height;

        return true;
    }

    public function send_image(
        $file = '',
        $compress = -1,
        $convert_file_to = null
    ) {
        if (!$this->image_validated) {
            return false;
        }
        $type = $this->type;
        if (!empty($convert_file_to) && in_array($convert_file_to, $this->allowed_extensions)) {
            $type = $convert_file_to;
        }
        switch ($type) {
            case 'jpeg':
            case 'jpg':
                if (!$file) {
                    header("Content-type: image/jpeg");
                }
                break;
            case 'png':
                if (!$file) {
                    header("Content-type: image/png");
                }
                break;
            case 'gif':
                if (!$file) {
                    header("Content-type: image/gif");
                }
                break;
        }
        $result = false;
        try {
            $result = $this->image->writeImage($file);
        } catch (ImagickException $e) {
            if ($this->debug) {
                error_log($e->getMessage());
            }
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
 * php-gd wrapper.
 */
class GDWrapper extends ImageWrapper
{
    public $bg;

    /**
     * GDWrapper constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
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
            case 1:
                $handler = @imagecreatefromgif($this->path);
                $this->type = 'gif';
                break;
            case 2:
                $handler = @imagecreatefromjpeg($this->path);
                $this->type = 'jpg';
                break;
            case 3:
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

    /**
     * @return array
     */
    public function get_image_size()
    {
        $return_array = ['width' => 0, 'height' => 0];
        if ($this->image_validated) {
            $return_array = ['width' => $this->width, 'height' => $this->height];
        }

        return $return_array;
    }

    public function fill_image_info()
    {
        if (file_exists($this->path)) {
            $image_info = getimagesize($this->path);
            $this->width = $image_info[0];
            $this->height = $image_info[1];
            $this->type = $image_info[2];
        } else {
            $this->width = 0;
            $this->height = 0;
            $this->type = 0;
        }
    }

    public function resize($thumbw, $thumbh, $border, $specific_size = false)
    {
        if (!$this->image_validated) {
            return false;
        }
        if (1 == $border) {
            if ($specific_size) {
                $width = $thumbw;
                $height = $thumbh;
            } else {
                $scale = min($thumbw / $this->width, $thumbh / $this->height);
                $width = (int) ($this->width * $scale);
                $height = (int) ($this->height * $scale);
            }
            $deltaw = (int) (($thumbw - $width) / 2);
            $deltah = (int) (($thumbh - $height) / 2);
            $dst_img = @imagecreatetruecolor($thumbw, $thumbh);
            @imagealphablending($dst_img, false);
            @imagesavealpha($dst_img, true);

            if (!empty($this->color)) {
                @imagefill($dst_img, 0, 0, $this->color);
            }
            $this->width = $thumbw;
            $this->height = $thumbh;
        } elseif (0 == $border) {
            if ($specific_size) {
                $width = $thumbw;
                $height = $thumbh;
            } else {
                $scale = ($this->width > 0 && $this->height > 0) ? min($thumbw / $this->width, $thumbh / $this->height) : 0;
                $width = (int) ($this->width * $scale);
                $height = (int) ($this->height * $scale);
            }
            $deltaw = 0;
            $deltah = 0;
            $dst_img = @imagecreatetruecolor($width, $height);
            @imagealphablending($dst_img, false);
            @imagesavealpha($dst_img, true);
            $this->width = $width;
            $this->height = $height;
        }
        $src_img = $this->bg;
        @imagecopyresampled(
            $dst_img,
            $src_img,
            $deltaw,
            $deltah,
            0,
            0,
            $width,
            $height,
            imagesx($src_img),
            imagesy($src_img)
        );
        $this->bg = $dst_img;
        @imagedestroy($src_img);
    }

    /**
     * @author José Loguercio <jose.loguercio@beeznest.com>
     *
     * @param int $x          coordinate of the cropped region top left corner
     * @param int $y          coordinate of the cropped region top left corner
     * @param int $width      the width of the crop
     * @param int $height     the height of the crop
     * @param int $src_width  the source width of the original image
     * @param int $src_height the source height of the original image
     */
    public function crop($x, $y, $width, $height, $src_width, $src_height)
    {
        if (!$this->image_validated) {
            return false;
        }
        $this->width = $width;
        $this->height = $height;
        $src = null;
        $dest = @imagecreatetruecolor($width, $height);
        $type = $this->type;
        switch ($type) {
            case 'jpeg':
            case 'jpg':
                $src = @imagecreatefromjpeg($this->path);
                @imagecopy($dest, $src, 0, 0, $x, $y, $src_width, $src_height);
                @imagejpeg($dest, $this->path);
                break;
            case 'png':
                $src = @imagecreatefrompng($this->path);
                @imagealphablending($dest, false);
                @imagesavealpha($dest, true);
                @imagecopy($dest, $src, 0, 0, $x, $y, $src_width, $src_height);
                @imagepng($dest, $this->path);
                break;
            case 'gif':
                $src = @imagecreatefromgif($this->path);
                @imagecopy($dest, $src, 0, 0, $x, $y, $src_width, $src_height);
                @imagegif($dest, $this->path);
                break;
            default:
                return 0;
        }
        @imagedestroy($dest);
        @imagedestroy($src);
    }

    /**
     * @param string $file
     * @param int    $compress
     * @param null   $convert_file_to
     *
     * @return bool|int
     */
    public function send_image($file = '', $compress = -1, $convert_file_to = null)
    {
        if (!$this->image_validated) {
            return false;
        }
        $compress = (int) $compress;
        $type = $this->type;
        if (!empty($convert_file_to) && in_array($convert_file_to, $this->allowed_extensions)) {
            $type = $convert_file_to;
        }
        switch ($type) {
            case 'jpeg':
            case 'jpg':
                if (!$file) {
                    header("Content-type: image/jpeg");
                }
                if (-1 == $compress) {
                    $compress = 100;
                }

                return imagejpeg($this->bg, $file, $compress);
                break;
            case 'png':
                if (!$file) {
                    header("Content-type: image/png");
                }
                if (-1 != $compress) {
                    @imagetruecolortopalette($this->bg, true, $compress);
                }

                return imagepng($this->bg, $file, $compress);
                break;
            case 'gif':
                if (!$file) {
                    header("Content-type: image/gif");
                }
                if (-1 != $compress) {
                    @imagetruecolortopalette($this->bg, true, $compress);
                }

                return imagegif($this->bg, $file);
                break;
            default:
                return 0;
        }
        // TODO: Occupied memory is not released, because the following fragment of code is actually dead.
        @imagedestroy($this->bg);
    }

    /**
     * Convert image to black & white.
     */
    public function convert2bw()
    {
        if (!$this->image_validated) {
            return false;
        }

        $dest_img = imagecreatetruecolor(imagesx($this->bg), imagesy($this->bg));
        /* copy ignore the transparent color
         * so that we can use black (0,0,0) as transparent, which is what
         * the image is filled with when created.
         */
        $transparent = imagecolorallocate($dest_img, 0, 0, 0);
        imagealphablending($dest_img, false);
        imagesavealpha($dest_img, true);
        imagecolortransparent($dest_img, $transparent);
        imagecopy(
            $dest_img,
            $this->bg,
            0,
            0,
            0,
            0,
            imagesx($this->bg),
            imagesx($this->bg)
        );
        imagefilter($dest_img, IMG_FILTER_GRAYSCALE);
        $this->bg = $dest_img;

        return true;
    }
}
