<?php
/**
 * Forum model
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 *
 * @method WHM_Core_Model_Node getModelFromCache
 */
class WHM_Core_Model_Forum extends XFCP_WHM_Core_Model_Forum
{
	public function prepareForum(array $forum)
	{
		return $this->getModelFromCache('XenForo_Model_Node')->unserializeNodeFields(parent::prepareForum($forum));
	}
}