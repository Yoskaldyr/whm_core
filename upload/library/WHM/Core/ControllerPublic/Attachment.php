<?php

/**
 * Public controller for attachments
 *
 * @package WHM_Core
 * @author  pepelac <ar@whiteholemedia.com>
 * @version 1000030 $Id$
 * @since   1000030
 */

/**
 * Extends XenForo_ControllerPublic_Attachment to add some extra functionality
 */
class WHM_Core_ControllerPublic_Attachment extends XFCP_WHM_Core_ControllerPublic_Attachment
{
	/**
	 * Extends \XenForo_ControllerPublic_Attachment::actionDoUpload method to pass the
	 * template name as a parameter into a view
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDoUpload()
	{
		$response = parent::actionDoUpload();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$response->params['attachmentEditorTemplateName'] = $this->_getAttachmentModel()->getTemplateNameForAttachmentEditor();
		}

		return $response;
	}
}