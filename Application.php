<?php
class WHM_Core_Application
{
	/**
	 * Constant key for storing
	 * input filters definitions
	 */
	const INPUT_FIELDS = 'inputFields';

	/**
	 * Constant key for storing
	 * datawriter fields definitions
	 */
	const DW_FIELDS = 'dwFields';

	/**
	 * Constant key for storing
	 * datawriter data for bulk set on presave
	 */
	const DW_DATA = 'dwData';

	/**
	 * Constant key for storing
	 * datawriter's extra data
	 */
	const DW_EXTRA = 'whmExtraInputData';

	/**
	 * Instance manager.
	 *
	 * @var WHM_Core_Application
	 */
	private static $_instance;

	/**
	 * WHM_Core application registry store
	 *
	 * @var array
	 */
	private $_data = array();

	/**
	 * Global trigger for enable/disable WHM_Core
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * @var bool|string Variable for saving class names
	 *
	 * */
	public $lastResolved = false;

	/**
	 * Gets the WHM Core instance.
	 *
	 * @return WHM_Core_Application
	 */
	public static final function getInstance()
	{
		if (!self::$_instance)
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Adds dynamic WHM_Core listeners and extenders to base XenForo Listeners
	 * by firing init_listeners event
	 * Called from WHM_Core_Autoloader
	 * */
	public static function initListeners()
	{
		$events = new WHM_Core_Listener();
		XenForo_CodeEvent::fire('init_listeners', array( $events ));
		//remove event (protects doubling init)
		unset($events->listeners['init_listeners']);
		//set merged dynamic load_class_* and normal listeners
		XenForo_CodeEvent::setListeners(
			array_merge_recursive(
				array('init_dependencies' => array(
					array('WHM_Core_Listener', 'initDependencies')
				)),
				$events->getDynamicListeners(),
				$events->listeners
			), false);
		unset($events);
	}

	/**
	 * Instance manager. Loaded on init_dependencies event
	 *
	 * @var WHM_Core_Application
	 */
	public function __construct()
	{
		if (WHM_Core_Listener::$enabled)
		{
			$this->enabled = true;
			XenForo_CodeEvent::fire('init_application', array($this));
		}
	}

	/**
	 * Gets data from core application registry by key and type/class/classname
	 *
	 * @param string              $key    Key for Data
	 * @param string|array|object $type   SubType (if array returned merged result)
	 * @param boolean             $remove Remove key after get
	 *
	 * @return array
	 *
	 * */
	public function get($key, $type, $remove = false)
	{
		if ($this->enabled && $type && $key && !empty($this->_data[$key]))
		{
			//if merged result
			if (is_array($type))
			{
				$merged = array();
				foreach ($type as $typeItem)
				{
					$merged = XenForo_Application::mapMerge($merged, $this->get($key, $typeItem, $remove));
				}
				return $merged;
			}
			else if (is_object($type))
			{
				$merged = array();
				foreach ($this->_data[$key] as $className => $classData)
				{
					if ($type instanceof $className)
					{
						$merged = XenForo_Application::mapMerge($merged, $classData);
						if ($remove)
						{
							$this->_data[$key][$className] = array();
						}
						$this->lastResolved = $className;
					}
				}
				return $merged;
			}
			else if (isset($this->_data[$key][$type]))
			{
				$return = $this->_data[$key][$type];
				if ($remove)
				{
					$this->_data[$key][$type] = array();
				}
				return $return;
			}
		}
		return array();
	}

	/**
	 * Save data to core application registry by key and type/classname
	 *
	 * @param string $key    Key for Data
	 * @param string $type   SubType or Class name
	 * @param array  $data   Data to save
	 *
	 * @return array
	 */

	public function set($key, $type, array $data)
	{
		if ($this->enabled && $type && $key && $data)
		{
			if ($typeName = $this->resolveTypeName($type, $key))
			{
				if (!isset($this->_data[$key][$typeName]))
				{
					$this->_data[$key][$typeName] = array();
				}
				$this->_data[$key][$typeName] = XenForo_Application::mapMerge($this->_data[$key][$typeName], $data);
			}
		}
	}

	/**
	 * Clears data in core application registry by key or type/class/classname
	 *
	 * @param string              $key    Key for Data
	 * @param string|array|object $type   Single type (type/class/class name)
	 *                                    or array of types to clear
	 */

	public function clear($key='', $type='')
	{
		if (!$this->enabled)
		{
			return;
		}
		else if (!$key)
		{
			$this->_data=array();
		}
		else if (!$type)
		{
			$this->_data[$key]=array();
		}
		//if group clear
		else if (is_array($type))
		{
			foreach ($type as $typeItem)
			{
				$this->clear($key, $typeItem);
			}
			return;
		}
		else if (is_object($type))
		{
			foreach ($this->_data[$key] as $className => $classData)
			{
				if ($type instanceof $className)
				{
					$this->_data[$key][$className] = array();
				}
			}
			return;
		}
		else if ($typeName = $this->resolveTypeName($type, $key))
		{
			$this->_data[$key][$typeName] = array();
		}
	}

	/**
	 * Helper for unserialize selected fields in datawriter data
	 *
	 * @param array $data      Data
	 * @param array $dwFields  Datawriter fields definitions
	 *
	 * @return array returns data with unserialized fields
	 */
	public function unserialize($data, $dwFields)
	{
		if ($data && $dwFields && is_array($data) && is_array($dwFields))
		{
			foreach ($dwFields as $fields)
			{
				foreach ($fields as $fieldName => $fieldOptions)
				{
					if (is_array($fieldOptions) && isset($fieldOptions['type']) && $fieldOptions['type'] == XenForo_DataWriter::TYPE_SERIALIZED)
					{
						if (empty($data[$fieldName]))
						{
							$data[$fieldName] = array();
						}
						else if (!is_array($data[$fieldName]))
						{
							$data[$fieldName] = unserialize($data[$fieldName]);
						}
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Type name normalizer. Resolves type name by keys array or core registry
	 * @param  string|array|object  $type Type to resolve
	 * @param  mixed                $keys Key or array of keys to compare. If null registry used
	 *
	 * @return boolean|string       Returns type name as string if success
	 */
	public function resolveTypeName($type, $keys = null)
	{
		$this->lastResolved = false;
		if (!$type)
		{
			return false;
		}
		else if (!is_object($type))
		{
			return is_string($type) ? $type : false;
		}
		else if (!$keys)
		{
			//searching all core registry
			$keys=array_keys($this->_data);
		}
		else if (!is_array($keys))
		{
			$keys = array($keys);
		}
		foreach ($keys as $key)
		{
			if (isset($this->_data[$key]) && is_array($this->_data[$key]))
			{
				foreach ($this->_data[$key] as $className => $classData)
				{
					if ($type instanceof $className)
					{
						$this->lastResolved = $className;
						return $className;
					}
				}
			}
		}
		return false;
	}
}
