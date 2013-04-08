<?php
/**
 * Data writer for Forums.
 *
 * @package XenForo_Node
 */
abstract class WHM_Core_DataWriter_Abstract extends XFCP_WHM_Core_DataWriter_Abstract
{
	public function preSave()
	{
		// retrieving from core with remove (only single get)
		if ($coreFields = WHM_Core_Application::getInstance()->get(WHM_Core_Application::DW_DATA, $this))
		{
			$extraFields = array();
			//manual fieldname search not buggy $this->getFieldNames()
			$fieldNames = array();
			foreach ($this->_fields AS $fields)
			{
				foreach ($fields AS $fieldName => $fieldInfo)
				{
					$fieldNames[] = $fieldName;
				}
			}
			$fieldNames = array_unique($fieldNames);

			foreach ($coreFields as $name => $field)
			{
				if (!in_array($name, $fieldNames))
				{
					$extraFields[$name] = $field;
					unset($coreFields[$name]);
				}
			}
			if ($extraFields)
			{
				// saving unparsed data in datawriter extra data
				$this->setExtraData(WHM_Core_Application::DW_EXTRA, $extraFields);
			}
			$this->bulkSet($coreFields);
		}

		parent::preSave();
	}

	protected function _validateSerializedArray(&$data)
	{
		if (!$data)
		{
			$data = null;
		}
		else
		{
			$dataNew = $data;

			if (!is_array($dataNew))
			{
				$dataNew = unserialize($dataNew);
				if (!is_array($dataNew))
				{
					$dataNew = null;
				}
			}
			$data = ($dataNew) ? serialize($dataNew) : null;
		}
		return true;
	}

	public function getUnserialize($key, $onlyArray = false)
	{
		$data = $this->get($key);
		if (is_string($data))
		{
			$data = @unserialize($data);
		}
		return $onlyArray
			? (is_array($data) ? $data : array())
			: $data;
	}

	/**
	 * !!! Fixed Version !!! Allow null as data
	 * Gets data related to this object regardless of where it is defined (new or old).
	 *
	 * @param string $field Field name
	 * @param string $tableName Table name, if empty loops through tables until first match
	 *
	 * @return mixed Returns null if the specified field could not be found.
	 */
	public function get($field, $tableName = '')
	{
		$tables = $this->_getTableList($tableName);

		foreach ($tables AS $tableName)
		{
			if (
				isset($this->_newData[$tableName])
				&& is_array($this->_newData[$tableName])
				&& array_key_exists($field, $this->_newData[$tableName])
			)
			{
				return $this->_newData[$tableName][$field];
			}
			else if (
				isset($this->_existingData[$tableName])
				&& is_array($this->_existingData[$tableName])
				&& array_key_exists($field, $this->_existingData[$tableName])
			)
			{
				return $this->_existingData[$tableName][$field];
			}
		}

		return null;
	}

}