<?php

/**
 * Controller for handling actions on forums.
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class WHM_Core_ControllerPublic_Forum extends XFCP_WHM_Core_ControllerPublic_Forum
{

	public function actionAddThread()
	{
		$core = WHM_Core_Application::getInstance();
		$core->set(
			WHM_Core_Application::DW_DATA,
			'XenForo_DataWriter_Discussion_Thread',
			$this->_input->filter(
				$core->get(
					WHM_Core_Application::INPUT_FIELDS,
					'XenForo_DataWriter_Discussion_Thread'
				)
			)
		);

		return parent::actionAddThread();
	}
}