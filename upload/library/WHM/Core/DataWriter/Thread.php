<?php

/**
 * Data writer for threads.
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class WHM_Core_DataWriter_Thread extends XFCP_WHM_Core_DataWriter_Thread
{

	protected function _getFields()
	{
		$dwFields = parent::_getFields();
		if ($dwCoreFields = WHM_Core_Application::getInstance()->get(
			WHM_Core_Application::DW_FIELDS,
			'XenForo_DataWriter_Discussion_Thread'
		))
		{
			$dwFields = XenForo_Application::mapMerge($dwFields, $dwCoreFields);
		}

		return $dwFields;
	}
}