<?php
/**
 * Data writer for all node types.
 * Dynamic proxy class
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 *
 * @method array getUnserialize($key, $onlyArray = false)
 * @method boolean _validateSerializedArray(&$data)
 * @method WHM_Core_Model_Node _getNodeModel()
 */
class WHM_Core_DataWriter_Node extends XFCP_WHM_Core_DataWriter_Node
{
	protected $_currentNodeType = false;

	protected function _getFields()
	{
		$dwFields = parent::_getFields();
		$classes = array('XenForo_DataWriter_Node');
		if ($nodeType = $this->getNodeType())
		{
			$classes[] = $nodeType['datawriter_class'];
		}
		if ($dwCoreFields = WHM_Core_Application::getInstance()->get(
			WHM_Core_Application::DW_FIELDS,
			$classes
		))
		{
			$dwFields = XenForo_Application::mapMerge($dwFields, $dwCoreFields);
		}

		return $dwFields;
	}

	/**
	 * Post Save for handling reset child nodes data
	 */
	protected function _postSave()
	{
		parent::_postSave();

		if ($nodeType = $this->getNodeType())
		{
			$typeName = $nodeType['datawriter_class'];
			if (
				($extra = $this->getExtraData(WHM_Core_Application::DW_EXTRA))
				&& !empty($extra['whm_reset'])
				&& is_array($extra['whm_reset'])
				&& ($dwData = WHM_Core_Application::getInstance()->get(WHM_Core_Application::DW_DATA, $typeName, true))
			)
			{
				$resetFields = $extra['whm_reset'];
				unset($extra['whm_reset']);
				$resetData = array();
				$nodeTypes = $this->_getNodeModel()->getAllNodeTypes();

				foreach ($resetFields as $name => $field)
				{
					if (isset($dwData[$name]) && $field)
					{
						//applying to all node types or only selected
						$applyTypes = (($field == 1) || ($field == 'all')) ? array_keys($nodeTypes) : explode(',', $field);
						foreach ($applyTypes as $nodeTypeId)
						{
							if (isset($nodeTypes[$nodeTypeId]))
							{
								$resetData[$nodeTypeId][$name] = $dwData[$name];
							}
						}
					}
				}
				if ($resetData && ($childNodes = $this->_getNodeModel()->getChildNodes($this->getMergedData())))
				{
					foreach ($childNodes as $node)
					{
						$nodeTypeId = $node['node_type_id'];
						if (
							isset(
								$nodeTypes[$nodeTypeId]['datawriter_class'],
								$resetData[$nodeTypeId]
							)
						)
						{
							/* @var $writer XenForo_DataWriter_Node */
							$writer = XenForo_DataWriter::create($nodeTypes[$nodeTypeId]['datawriter_class']);

							// prevent any child updates from occuring - we're handling it here
							$writer->setOption(XenForo_DataWriter_Node::OPTION_POST_WRITE_UPDATE_CHILD_NODES, false);
							if ($extra)
							{
								//setting additional settings (can be changed later)
								//$writer->setExtraData(WHM_Core_Application::DW_EXTRA, $extra);
							}
							// we already have the data, don't go and query it again
							$writer->setExistingData($node, true);
							$writer->bulkSet($resetData[$nodeTypeId]);
							$writer->save();
						}
					}
				}
			}
		}
	}

	/**
	 * Gets the current node type
	 * @return array|boolean
	 */
	public function getNodeType()
	{
		if ($this->_currentNodeType)
		{
			return $this->_currentNodeType;
		}
		if ($nodeTypes = $this->_getNodeModel()->getAllNodeTypes())
		{
			foreach ($nodeTypes as $nodeTypeId => $nodeType)
			{
				if ($this instanceof $nodeType['datawriter_class'])
				{
					return $this->_currentNodeType = $nodeType;
				}
			}
		}
		//base node type
		return false;
	}
}