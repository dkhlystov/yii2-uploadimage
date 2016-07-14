<?php

namespace uploadimage\components;

/**
 * Class for manipulating with image
 */
class ImageFile
{

	/**
	 * @var integer Qualiti of JPG image file to save.
	 */
	public $jpegQuality = 80;

	/**
	 * @var string Name of the image file.
	 */
	private $_filename;

	/**
	 * @var string Format of the image file.
	 */
	private $_format;

	/**
	 * @var integer Image width.
	 */
	private $_width;

	/**
	 * @var integer Image height.
	 */
	private $_height;

	/**
	 * @var resource Image data.
	 */
	private $_image;

	/**
	 * @param string $filename Name of the image file.
	 * @return void
	 */
	public function __construct($filename, $options)
	{
		$this->_filename = $filename;

		$this->setOptions($options);

		$this->load();
	}

	public function __destruct()
	{
		if (is_resource($this->_image))
			imagedestroy($this->_image);
	}

	/**
	 * If one of image demension lager then specified, image will resize with
	 * preserve aspect ratio to fit the specified demensions. 
	 * @param integer $maxWidth Maximum width.
	 * @param integer $maxHeight Maximum height.
	 * @return void
	 */
	public function bounds($maxWidth, $maxHeight)
	{
		if ($maxWidth > $this->_width && $maxHeight > $this->_height)
			return;

		list($width, $height) = self::getBounds($this->_width, $this->_height, $maxWidth, $maxHeight);

		$this->resize($this->_width, $this->_height, $width, $height);
	}

	/**
	 * Rotate image on specified angle.
	 * @param integer $degrees 
	 * @return void
	 */
	public function rotate($angle)
	{
		if (!function_exists('imagerotate'))
			throw new \RuntimeException('Your version of GD does not support image rotation.');

		$image = imagerotate($this->_image, $angle, 0);
		$this->preserveAlpha($image);

		$this->_image = $image;
		$this->_width = imagesx($image);
		$this->_height = imagesy($image);
	}

	/**
	 * Calculate bounds for resize image with preserve aspect ratio.
	 * @param integer $width Image width.
	 * @param integer $height Image height.
	 * @param integer $maxWidth Maximum width.
	 * @param integer $maxHeight Maximum height.
	 * @return array[] First element is calculated width and second is height.
	 */
	public static function getBounds($width, $height, $maxWidth, $maxHeight)
	{
		$a = $width / $height;
		$maxA = $maxWidth / $maxHeight;

		if ($a > $maxA) {
			$width = $maxWidth;
			$height = (integer) ($width / $a);
		} else {
			$height = $maxHeight;
			$width = (integer) ($height * $a);
		}

		return [$width, $height];
	}

	/**
	 * Crop the image.
	 * @param integer $width Crop width.
	 * @param integer $height Crop height.
	 * @param integer|null $x Crop x offset. Center if null.
	 * @param integer|null $y Crop y offset. Center if null.
	 * @return void
	 */
	public function crop($width, $height, $x = null, $y = null)
	{
		$a = $this->_width / $this->_height;
		$thumbA = $width / $height;

		if ($a > $thumbA) {
			$h = $this->_height;
			$w = (integer) ($h * $thumbA);
		} else {
			$w = $this->_width;
			$h = (integer) ($w / $thumbA);
		}

		$maxX = $this->_width - $w;
		$maxY = $this->_height - $h;

		if ($x === null)
			$x = (integer) ($maxX / 2);

		if ($y === null)
			$y = (integer) ($maxY / 2);

		if ($x < 0) $x = 0;
		if ($x > $maxX) $x = $maxX;
		if ($y < 0) $y = 0;
		if ($y > $maxY) $y = $maxY;

		$this->resize($w, $h, $width, $height, $x, $y);

	}

	/**
	 * Save changed image data.
	 * @param string|null $filename Name of the image file. Original filename will be used if null.
	 * @param string|null $format Image format. Must be one of [GIF,JPG,PNG]. Original format will be used if null.
	 * @return void
	 */
	public function save($filename = null, $format = null)
	{
		if ($filename === null)
			$filename = $this->_filename;

		$validFormats = ['GIF', 'JPG', 'PNG'];

		if ($format === null)
			$format = $this->_format;

		$format = strtoupper($format);

		if (!in_array($format, $validFormats))
			throw new \InvalidArgumentException("Invalid format type specified in save function: {$format}");

		switch ($format) {
			case 'GIF':
				imagegif($this->_image, $filename);
				break;
			case 'JPG':
				imagejpeg($this->_image, $filename, $this->jpegQuality);
				break;
			case 'PNG':
				imagepng($this->_image, $filename);
				break;
		}

		$this->_filename = $filename;
	}

	/**
	 * Image resize
	 * @param integer $width Source width
	 * @param integer $height Source height
	 * @param integer $newWidth Destination width
	 * @param integer $newHeight Destination height
	 * @param integer $x Horizontal offset
	 * @param integer $y Vertical offset
	 * @return void
	 */
	private function resize($width, $height, $newWidth, $newHeight, $x = 0, $y = 0)
	{
		if (function_exists('imagecreatetruecolor')) {
			$image = imagecreatetruecolor($newWidth, $newHeight);
		} else {
			$image = imagecreate($newWidth, $newHeight);
		}

		$this->preserveAlpha($image);

		imagecopyresampled($image, $this->_image, 0, 0, $x, $y, $newWidth, $newHeight, $width, $height);

		$this->_image = $image;
		$this->_width = $newWidth;
		$this->_height = $newHeight;
	}

	/**
	 * Preserve the alpha or tranparency for GIF and PNG files.
	 * @param resource $image 
	 * @return void
	 */
	private function preserveAlpha($image)
	{
		if ($this->_format == 'GIF') {
			$color = imagecolorallocate($image, 0, 0, 0);

			imagecolortransparent($image, $color);
			imagetruecolortopalette($image, true, 256);
		}

		if ($this->_format == 'PNG') {
			$color = imagecolorallocatealpha($image, 255, 255, 255, 127);

			imagefill($image, 0, 0, $color);
			imagecolortransparent($image, $color);
			imagealphablending($image, false);
			imagesavealpha($image, true);
		}
	}

	/**
	 * Set user options.
	 * @param array $options 
	 * @return void
	 */
	private function setOptions($options)
	{
		if (isset($options['jpegQuality']))
			$this->jpegQuality = $options['jpegQuality'];
	}

	/**
	 * Loads image file for manipulations.
	 * @return void
	 */
	private function load()
	{
		$this->determineFormat();
		$this->checkFormatSupport();

		switch ($this->_format) {
			case 'GIF':
				$this->_image = imagecreatefromgif($this->_filename);
				break;
			case 'JPG':
				$this->_image = imagecreatefromjpeg($this->_filename);
				break;
			case 'PNG':
				$this->_image = imagecreatefrompng($this->_filename);
				break;
		}

		$this->_width = imagesx($this->_image);
		$this->_height = imagesy($this->_image);
	}

	/**
	 * Image file format determining by mime type.
	 * @return void
	 */
	private function determineFormat()
	{
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimeType = finfo_file($finfo, $this->_filename);
		finfo_close($finfo);

		switch ($mimeType) {
			case 'image/gif':
				$this->_format = 'GIF';
				break;
			case 'image/jpeg':
				$this->_format = 'JPG';
				break;
			case 'image/png':
				$this->_format = 'PNG';
				break;
			default:
				throw new \Exception("Image format not supported: {$mimeType}.");
		}
	}

	/**
	 * Check format support in gd
	 * @return type
	 */
	private function checkFormatSupport()
	{
		$gdInfo = gd_info();

		switch ($this->_format) {
			case 'GIF':
				$support = $gdInfo['GIF Create Support'];
				break;
			case 'JPG':
				$support = isset($gdInfo['JPG Support']) || isset($gdInfo['JPEG Support']) ? true : false;
				break;
			case 'PNG':
				$support = $gdInfo['PNG Support'];
				break;
			default:
				$support = false;
				break;
		}

		if (!$support)
			throw new \Exception("Your GD installation does not support {$this->_format} image types.");
	}

}
