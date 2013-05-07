<?php

/**
 * View for displaying a form to upload more attachments, and listing those that already exist
 *
 * @package WHM_Core
 * @author  pepelac <ar@whiteholemedia.com>
 * @version 1000030 $Id$
 * @since   1000030
 */

class WHM_Core_ViewPublic_Attachment_DoUpload extends XFCP_WHM_Core_ViewPublic_Attachment_DoUpload
{
	public function renderJson()
	{
		$attach = $this->_prepareAttachmentForJson($this->_params['attachment']);
		if (!empty($this->_params['message']))
		{
			$attach['message'] = $this->_params['message'];
		}

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($attach);
	}

	/**
	 * Reduces down an array of attachment data into information we don't mind exposing,
	 * and includes the attachment template for each attachment depending on the attachment
	 * type
	 *
	 * @param array $attachment
	 *
	 * @return array
	 */
	protected function _prepareAttachmentForJson(array $attachment)
	{
		$keys = array('attachment_id', 'attach_date', 'filename', 'thumbnailUrl', 'deleteUrl');

		// just in case...
		$templateName = empty($this->_params['attachmentEditorTemplateName']) ? 'attachment_editor_attachment' : $this->_params['attachmentEditorTemplateName'];

		$template = $this->createTemplateObject($templateName, array('attachment' => $attachment));

		$attachment = XenForo_Application::arrayFilterKeys($attachment, $keys);

		$attachment['templateHtml'] = $template;
		$attachment['templateName'] = $templateName;

		return $attachment;
	}
}