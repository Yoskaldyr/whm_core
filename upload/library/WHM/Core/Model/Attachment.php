<?php

/**
 * Extended model for manipulation with attachments
 *
 * @package WHM_Core
 * @author  pepelac <ar@whiteholemedia.com>
 * @version 1000030 $Id$
 * @since   1000030
 */

class WHM_Core_Model_Attachment extends XFCP_WHM_Core_Model_Attachment
{
	/**
	 * @var string Contains content type for last attachment being uploaded. If empty, standard
	 * thumbnail generation process will be used
	 */
	protected $_lastAttachmentType = '';

	/**
	 * Gets the attachment handler object for a specified content type.
	 * Sets the content type as the last attachment type.
	 *
	 * @param string $contentType
	 *
	 * @return XenForo_AttachmentHandler_Abstract|null
	 */
	public function getAttachmentHandler($contentType)
	{
		$object = parent::getAttachmentHandler($contentType);

		$this->_lastAttachmentType = ($object) ? $contentType : '';

		return $object;
	}

	/**
	 * Gets the attachment handler for the last uploaded attachment
	 *
	 * @return null|XenForo_AttachmentHandler_Abstract
	 */
	public function getLastAttachmentHandler()
	{
		return $this->getAttachmentHandler($this->_lastAttachmentType);
	}

	/**
	 * Returns name of the template, which will be used for JSON response in case of
	 * ajax file upload
	 */
	public function getTemplateNameForAttachmentEditor()
	{
		$attachmentHandler = $this->getLastAttachmentHandler();
		$templateName      = 'attachment_editor_attachment'; // default template name

		if ($attachmentHandler && is_callable(array($attachmentHandler, 'getTemplateNameForAttachmentEditor')))
		{
			$templateName = $attachmentHandler->getTemplateNameForAttachmentEditor();
		}

		return $templateName;
	}

	/**
	 * Overrides method of the parent class (@see \XenForo_Model_Attachment::insertUploadedAttachmentData)
	 * and enables square thumbnails generation. Inserts uploaded attachment data.
	 *
	 * @param XenForo_Upload $file Uploaded attachment info. Assumed to be valid
	 * @param integer $userId User ID uploading
	 * @param array $extra Extra params to set
	 * @throws Exception
	 * @return int|void
	 */
	public function insertUploadedAttachmentData(XenForo_Upload $file, $userId, array $extra = array())
	{
		$attachmentHandler = $this->getLastAttachmentHandler();
		$dimensions        = array();

		if ($attachmentHandler && is_callable(array($attachmentHandler, 'generateThumbnail')))
		{
			$tempThumbFile = $attachmentHandler->generateThumbnail($file, $dimensions);
		}
		else
		{
			$tempThumbFile = $this->_generateStandardThumbnail($file, $dimensions);
		}

		try
		{
			$dataDw = XenForo_DataWriter::create('XenForo_DataWriter_AttachmentData');
			$dataDw->bulkSet($extra);
			$dataDw->set('user_id', $userId);
			$dataDw->set('filename', $file->getFileName());
			$dataDw->bulkSet($dimensions);
			$dataDw->setExtraData(XenForo_DataWriter_AttachmentData::DATA_TEMP_FILE, $file->getTempFile());
			if ($tempThumbFile)
			{
				$dataDw->setExtraData(XenForo_DataWriter_AttachmentData::DATA_TEMP_THUMB_FILE, $tempThumbFile);
			}
			$dataDw->save();
		}
		catch (Exception $e)
		{
			if ($tempThumbFile)
			{
				@unlink($tempThumbFile);
			}

			throw $e;
		}

		if ($tempThumbFile)
		{
			@unlink($tempThumbFile);
		}

		// TODO: add support for "on rollback" behavior

		return $dataDw->get('data_id');
	}

	/**
	 * This method is called if the attachment handler for the current content type has no thumbnail
	 * generation functionality. Will try to create thumbanil with the standard settings
	 *
	 * @param XenForo_Upload $file
	 * @param array $dimensions Wiil contain dimensions of the generated thumbnail or empty, if generation will fail
	 * @return string Path to the thumbnail file
	 */
	protected function _generateStandardThumbnail(XenForo_Upload $file, array &$dimensions = array())
	{
		if ($file->isImage()
			&& XenForo_Image_Abstract::canResize($file->getImageInfoField('width'), $file->getImageInfoField('height'))
		)
		{
			$dimensions = array(
				'width' => $file->getImageInfoField('width'),
				'height' => $file->getImageInfoField('height'),
			);

			$tempThumbFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
			if ($tempThumbFile)
			{
				$image = XenForo_Image_Abstract::createFromFile($file->getTempFile(), $file->getImageInfoField('type'));
				if ($image)
				{
					if ($image->thumbnail(XenForo_Application::get('options')->attachmentThumbnailDimensions))
					{
						$image->output($file->getImageInfoField('type'), $tempThumbFile);
					}
					else
					{
						copy($file->getTempFile(), $tempThumbFile); // no resize necessary, use the original
					}

					$dimensions['thumbnail_width'] = $image->getWidth();
					$dimensions['thumbnail_height'] = $image->getHeight();

					unset($image);
				}
			}
		}
		else
		{
			$tempThumbFile = '';
			$dimensions = array();
		}

		return $tempThumbFile;
	}
}