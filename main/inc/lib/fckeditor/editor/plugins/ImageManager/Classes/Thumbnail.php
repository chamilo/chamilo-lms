<?php
/**
 * Create thumbnails.
 * @author Wei Zhuo
 * @version $Id: Thumbnail.php,v 1.2 2006/12/16 21:38:13 thierrybo Exp $
 * @package ImageManager
 */


require_once('Transform.php');

/**
 * Thumbnail creation
 * @author Wei Zhuo
 * @version $Id: Thumbnail.php,v 1.2 2006/12/16 21:38:13 thierrybo Exp $
 * @package ImageManager
 * @subpackage Images
 */
class Thumbnail
{
	/**
	 * Graphics driver, GD, NetPBM or ImageMagick.
	 */
	var $driver;

	/**
	 * Thumbnail default width.
	 */
	var $width = 96;

	/**
	 * Thumbnail default height.
	 */
	var $height = 96;

	/**
	 * Thumbnail default JPEG quality.
	 */
	var $quality = 85;

	/**
	 * Thumbnail is proportional
	 */
	var $proportional = true;

	/**
	 * Default image type is JPEG.
	 */
	var $type = 'jpeg';

	/**
	 * Create a new Thumbnail instance.
	 * @param int $width thumbnail width
	 * @param int $height thumbnail height
	 */
	function Thumbnail($width=96, $height=96)
	{
		$this->driver = Image_Transform::factory(IMAGE_CLASS);
		$this->width = $width;
		$this->height = $height;
	}

	/**
	 * Create a thumbnail.
	 * @param string $file the image for the thumbnail
	 * @param string $thumbnail if not null, the thumbnail will be saved
	 * as this parameter value.
	 * @return boolean true if thumbnail is created, false otherwise
	 */
	function createThumbnail($file, $thumbnail=null)
	{
		if(!is_file($file))
			Return false;

		//error_log('Creating Thumbs: '.$file);

		$this->driver->load($file);

		if($this->proportional)
		{
			$width = $this->driver->img_x;
			$height = $this->driver->img_y;

			if ($width > $height)
				$this->height = intval($this->width/$width*$height);
			else if ($height > $width)
				$this->width = intval($this->height/$height*$width);
		}

		$this->driver->resize($this->width, $this->height);

		if(is_null($thumbnail))
			$this->save($file);
		else
			$this->save($thumbnail);


		$this->free();

		if(is_file($thumbnail))
			Return true;
		else
			Return false;
	}

	/**
	 * Save the thumbnail file.
	 * @param string $file file name to be saved as.
	 */
	function save($file)
	{
		$this->driver->save($file);
	}

	/**
	 * Free up the graphic driver resources.
	 */
	function free()
	{
		$this->driver->free();
	}
}


?>
