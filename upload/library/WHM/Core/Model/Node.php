<?php

/**
 * Node model
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class WHM_Core_Model_Node extends XFCP_WHM_Core_Model_Node
{
	protected static $_fieldsCache = array();

	public function unserializeNodeFields(array $node)
	{
		$core = WHM_Core_Application::getInstance();
		$cacheKey = 'Node';
		$nodeClasses = array('XenForo_DataWriter_Node');

		if (isset($node['node_type_id']) && ($nodeType = $this->getNodeTypeById($node['node_type_id'])))
		{
			$cacheKey = $nodeType['node_type_id'];
			$nodeClasses[] = $nodeType['datawriter_class'];
		}
		if (empty(self::$_fieldsCache[$cacheKey]))
		{
			self::$_fieldsCache[$cacheKey] = $core->get(WHM_Core_Application::DW_FIELDS, $nodeClasses);
		}
		return $core->unserialize($node, self::$_fieldsCache[$cacheKey]);
	}

	public function getNodeById($nodeId, array $fetchOptions = array())
	{
		$node = parent::getNodeById($nodeId, $fetchOptions);
		return is_array($node) ? $this->unserializeNodeFields($node) : $node;
	}

	public function getNodeByName($nodeName, $nodeTypeId, array $fetchOptions = array())
	{
		$node = parent::getNodeByName($nodeName, $nodeTypeId, $fetchOptions);
		return is_array($node) ? $this->unserializeNodeFields($node) : $node;
	}

	public function prepareNodeForAdmin(array $node)
	{
		return $this->unserializeNodeFields(parent::prepareNodeForAdmin($node));
	}

	public function prepareNodesWithHandlers(array $nodes, array $nodeHandlers)
	{
		$nodes = parent::prepareNodesWithHandlers($nodes, $nodeHandlers);
		foreach ($nodes AS &$node)
		{
			$node = $this->unserializeNodeFields($node);
		}
		return $nodes;
	}
}