<?php
/**
 * User: Yoskaldyr
 */

class WHM_Core_Listener extends XenForo_CodeEvent
{

	public $listeners = array();
	public static $enabled = false;

	protected static $_extend = array();
	protected static $_counters = array();
	protected $_validTypes = array(
		 'proxy_class', 'bb_code', 'controller', 'datawriter', 'importer',
		 'mail', 'model', 'route_prefix', 'search_data', 'view'
	);

	/**
	 * Private constructor, use statically.
	 */
	public function __construct()
	{
		$this->listeners = $this->getXenForoListeners();
	}

	public function getXenForoListeners()
	{
		return ($return = parent::$_listeners) ? $return : array();
	}

	public function getDynamicListeners()
	{
		if (self::$_extend)
		{
			$listeners = array();
			foreach ($this->_validTypes as $type)
			{
				if (!empty(self::$_extend[$type]))
				{
					$listenerMethod = str_replace(' ', '', ucwords(str_replace('_', ' ', $type)));
					$listeners['load_class_'.$type] = array(
						array('WHM_Core_Listener', 'loadClass'. $listenerMethod)
					);
					if ($type == 'proxy_class')
					{
						foreach (self::$_extend[$type] as $className => $extend)
						{
							WHM_Core_Autoloader::getProxy()->addClass($className);
						}
					}
				}
			}
			return $listeners;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Prepends a listener for the specified event. This method takes an arbitrary
	 * callback, so can be used with more advanced things like object-based
	 * callbacks (and simple function-only callbacks).
	 *
	 * @param string   $event    Event to listen to
	 * @param callback $callback Function/method to call.
	 */
	public function prependListener($event, $callback)
	{
		if (self::$enabled)
		{
			if (!isset($this->listeners[$event]))
			{
				$this->listeners[$event][] = $callback;
			}
			else
			{
				array_unshift($this->listeners[$event], $callback);
			}
		}
	}

	/**
	 * Appends a listener for the specified event. This method takes an arbitrary
	 * callback, so can be used with more advanced things like object-based
	 * callbacks (and simple function-only callbacks).
	 *
	 * @param string   $event    Event to listen to
	 * @param callback $callback Function/method to call.
	 */
	public function appendListener($event, $callback)
	{
		if (self::$enabled)
		{
			$this->listeners[$event][] = $callback;
		}
	}

	/**
	 * Prepends or appends a listeners array to current events. This method takes an arbitrary
	 * callback, so can be used with more advanced things like object-based
	 * callbacks (and simple function-only callbacks).
	 *
	 * @param array   $listeners    Event to listen to
	 * @param boolean $prepend
	 */
	public function addListeners(array $listeners, $prepend = false)
	{
		if (self::$enabled)
		{
			if ($this->listeners)
			{
				$this->listeners = $listeners;
			}
			else
			{
				$this->listeners = ($prepend) ? array_merge_recursive($listeners, $this->listeners) : array_merge_recursive($this->listeners, $listeners);
			}
		}
	}

	public function addExtenders($extenders, $prepend = false)
	{
		self::addExtendersStatic($extenders, $prepend);
	}

	public static function addExtendersStatic($extenders, $prepend = false)
	{
		if (self::$enabled)
		{
			if (!self::$_extend)
			{
				self::$_extend = $extenders;
			}
			else
			{
				self::$_extend = ($prepend) ? array_merge_recursive($extenders, self::$_extend) : array_merge_recursive(self::$_extend, $extenders);
			}
		}
	}

	/** Core event listener - must be first in queue */
	public static function initListeners(WHM_Core_Listener $events)
	{
		self::$enabled = true;
		//Core listeners
		$events->addExtenders(
			array(
			     'proxy_class' => array(
				     'XenForo_DataWriter' => array(
					     array('WHM_Core_DataWriter_Abstract', 'abstract')
				     )
			     )
			)
		);
	}

	public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		//first launch if core active
		WHM_Core_Application::getInstance();
	}

	public static function loadClassBbCode($class, array &$extend)
	{
		self::_mergeExtend('bb_code', $class, $extend);
	}

	public static function loadClassController($class, array &$extend)
	{
		self::_mergeExtend('controller', $class, $extend);
	}

	public static function loadClassDatawriter($class, array &$extend)
	{
		self::_mergeExtend('datawriter', $class, $extend);
	}

	public static function loadClassImporter($class, array &$extend)
	{
		self::_mergeExtend('importer', $class, $extend);
	}

	public static function loadClassMail($class, array &$extend)
	{
		self::_mergeExtend('mail', $class, $extend);
	}

	public static function loadClassModel($class, array &$extend)
	{
		self::_mergeExtend('model', $class, $extend);
	}

	public static function loadClassRoutePrefix($class, array &$extend)
	{
		self::_mergeExtend('route_prefix', $class, $extend);
	}

	public static function loadClassSearchData($class, array &$extend)
	{
		self::_mergeExtend('search_data', $class, $extend);
	}

	public static function loadClassView($class, array &$extend)
	{
		self::_mergeExtend('view', $class, $extend);
	}

	public static function loadClassProxyClass($class, array &$extend)
	{
		self::_mergeExtend('proxy_class', $class, $extend);
	}

	protected static function _mergeExtend($type, $class, array &$extend)
	{
		if (self::$enabled && isset(self::$_extend[$type][$class]))
		{
			if (!is_array(self::$_extend[$type][$class]))
			{
				self::$_extend[$type][$class] = array(self::$_extend[$type][$class]);
			}
			foreach(self::$_extend[$type][$class] as $dynamic)
			{
				if (is_array($dynamic))
				{
					$extend[] = $dynamic;
				}
				else if (empty(self::$_counters[$dynamic]))
				{
					$extend[] = $dynamic;
					self::$_counters[$dynamic] = 1;
				}
				else
				{
					$extend[] = $dynamic. '__' . self::$_counters[$dynamic];
					self::$_counters[$dynamic]++;
				}
			}
		}
	}
}