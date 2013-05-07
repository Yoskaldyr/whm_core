<?php

/**
 * Extended abstract image processor
 *
 * @package WHM_Core
 * @author  pepelac <ar@whiteholemedia.com>
 * @version 1000030 $Id$
 * @since   1000030
 */

/**
 * Class WHM_Core_Image_Abstract extends base XenForo_Image_Abstract class to add ability
 * to generate square thumbnails
 */
abstract class WHM_Core_Image_Abstract extends XFCP_WHM_Core_Image_Abstract
{
	/**
	 * Thumbnails the current image.
	 *
	 * @param integer $length The maximum lenght of the thumb side.
	 *
	 * @return boolean True if thumbnailing was necessary
	 */
	public function thumbnailSquare($length)
	{
		$this->_makeSquareImage();
		return $this->thumbnail($length);
	}

	/**
	 * Crops image to make it square
	 */
	protected function _makeSquareImage()
	{
		$cropPoint = array();
		switch ($this->getOrientation())
		{
			case self::ORIENTATION_LANDSCAPE :
				$shortSide = $this->getHeight();
				$centerX = round($this->getWidth() / 2);
				$cropX = $centerX - round($shortSide / 2);
				$cropPoint['x'] = ($cropX > 0) ? $cropX : 0;
				$cropPoint['y'] = 0;
				unset($centerX, $cropX);
				break;

			case self::ORIENTATION_PORTRAIT :
				$shortSide = $this->getWidth();
				$centerY = round($this->getHeight() / 2);
				$cropY = $centerY - round($shortSide / 2);
				$cropPoint['x'] = 0;
				$cropPoint['y'] = ($cropY > 0) ? $cropY : 0;
				unset($centerY, $cropY);
				break;

			default :
				$shortSide = $this->getWidth();
				$cropPoint['x'] = $cropPoint['y'] = 0;
				break;
		}

		$this->crop($cropPoint['x'], $cropPoint['y'], $shortSide, $shortSide);
	}
}