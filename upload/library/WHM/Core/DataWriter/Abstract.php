<?php

/**
 * WHM Base DataWriter class
 *
 * @package WHM_Core
 * @author Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
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

	/**
	 * Serialized array validator
	 * Unserialize checks with force data convert to serialized array
	 * or null if check failed
	 *
	 * @param mixed $data Validated data
	 * @return bool Always returns true
	 */
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

	/**
	 * Gets data related to this object regardless of where it is defined (new or old).
	 *
	 * @param string $field     Field name
	 * @param bool   $onlyArray Returns only array if true (if data is not array returns empty array)
	 * @param string $tableName Table name, if empty loops through tables until first match
	 *
	 * @return mixed Returns null if the specified field could not be found.
	 */
	public function getUnserialize($field, $onlyArray = false, $tableName = '')
	{
		$data = $this->get($field, $tableName);
		if (is_string($data))
		{
			$data = @unserialize($data);
		}
		return $onlyArray
			? (is_array($data) ? $data : array())
			: $data;
	}

}