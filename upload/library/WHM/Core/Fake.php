<?php

/**
 * Fake Class for IDE autocomplete
 *
 * @package WHM_Core
 * @author Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
abstract class XFCP_WHM_Core_DataWriter_Abstract extends XenForo_DataWriter {}
abstract class XFCP_WHM_Core_ControllerAdmin_NodeAbstract extends XenForo_ControllerAdmin_NodeAbstract {}

class XFCP_WHM_Core_Model_Node extends XenForo_Model_Node {}
class XFCP_WHM_Core_Model_Forum extends XenForo_Model_Forum {}
class XFCP_WHM_Core_Model_Post extends XenForo_Model_Post {}
class XFCP_WHM_Core_Model_Thread extends XenForo_Model_Thread {}
class XFCP_WHM_Core_DataWriter_Node extends XenForo_DataWriter_Node {}
class XFCP_WHM_Core_DataWriter_Thread extends XenForo_DataWriter_Discussion_Thread {}
class XFCP_WHM_Core_DataWriter_Post extends XenForo_DataWriter_DiscussionMessage_Post {}

class XFCP_WHM_Core_ControllerPublic_Forum extends XenForo_ControllerPublic_Forum {}
class XFCP_WHM_Core_ControllerPublic_Thread extends XenForo_ControllerPublic_Thread {}
class XFCP_WHM_Core_ControllerPublic_Post extends XenForo_ControllerPublic_Post {}

class XFCP_WHM_Core_Model_Attachment extends XenForo_Model_Attachment {}
abstract class XFCP_WHM_Core_Image_Abstract extends XenForo_Image_Abstract {}
class XFCP_WHM_Core_ControllerPublic_Attachment extends XenForo_ControllerPublic_Attachment {}
class XFCP_WHM_Core_ViewPublic_Attachment_DoUpload extends XenForo_ViewPublic_Attachment_DoUpload {}