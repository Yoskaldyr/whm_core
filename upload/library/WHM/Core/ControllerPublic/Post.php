<?php

/**
 * Controller for handling actions on posts.
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class WHM_Core_ControllerPublic_Post extends XFCP_WHM_Core_ControllerPublic_Post
{

	public function actionSave()
	{
		$core = WHM_Core_Application::getInstance();
		$core->set(
			WHM_Core_Application::DW_DATA,
			'XenForo_DataWriter_DiscussionMessage_Post',
			$this->_input->filter(
				$core->get(
					WHM_Core_Application::INPUT_FIELDS,
					'XenForo_DataWriter_DiscussionMessage_Post'
				)
			)
		);

		return parent::actionSave();
	}
}