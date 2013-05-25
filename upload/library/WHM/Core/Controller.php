<?php
/**
 * Class WHM_Core_Controller
 * WHM Controller class with load_controller_helper event
 *
 * @package WHM_Core
 * @author Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000031 $Id$
 * @since 1000031
 */

abstract class WHM_Core_Controller extends XFCP_WHM_Core_Controller
{
	public function getHelper($class)
	{
		if (strpos($class, '_') === false)
		{
			$class = 'XenForo_ControllerHelper_' . $class;
		}

		$createClass = XenForo_Application::resolveDynamicClass($class, 'controller_helper');

		if (!$createClass)
		{
			throw new XenForo_Exception("Invalid controller helper '$class' specified");
		}

		return parent::getHelper($createClass);
	}
}