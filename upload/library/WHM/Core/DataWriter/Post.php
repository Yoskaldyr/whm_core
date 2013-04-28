<?php

/**
 * Data writer for posts.
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class WHM_Core_DataWriter_Post extends XFCP_WHM_Core_DataWriter_Post
{

	protected function _getFields()
	{
		$dwFields = parent::_getFields();
		if ($dwCoreFields = WHM_Core_Application::getInstance()->get(
			WHM_Core_Application::DW_FIELDS,
			'XenForo_DataWriter_DiscussionMessage_Post'
		))
		{
			$dwFields = XenForo_Application::mapMerge($dwFields, $dwCoreFields);
		}

		return $dwFields;
	}
}