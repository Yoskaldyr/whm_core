<?php

/**
 * Fires code events and executes event listener callbacks
 * based on XenForo_CodeEvent
 *
 * @package WHM_Core
 * @author  Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since   1000011
 */
class WHM_Core_Listener extends XenForo_CodeEvent
{
	/**
	 * Instance manager.
	 *
	 * @var WHM_Core_Listener
	 */
	private static $_instance;

	/**
	 * Listeners cache array - public mirror of original listeners
	 * @var array
	 */
	public $listeners = array();

	/**
	 * Global disabler/enabler of listeners core
	 *
	 * @var bool
	 */
	public static $enabled = false;

	/**
	 * Dynamic extenders input array
	 * @var array
	 */
	protected static $_extendInput = array();

	/**
	 * Normal extenders array
	 * @var array
	 */
	protected static $_extend = array();

	/**
	 * XFCP_* classes loader counter
	 * @var array
	 */
	protected static $_counters = array();

	/**
	 * Gets the WHM Core Listener instance.
	 *
	 * @return WHM_Core_Listener
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
	 * Constructor. Sets original listeners cache from parent class
	 */
	public function __construct()
	{
		if (!is_array(XenForo_CodeEvent::$_listeners))
		{
			XenForo_CodeEvent::$_listeners = array();
		}
		$this->listeners =& XenForo_CodeEvent::$_listeners;
	}

	/**
	 * Returns array of original listeners from parent (XenForo_CodeEvent)
	 * @return array
	 */
	public function getXenForoListeners()
	{
		return ($return = XenForo_CodeEvent::$_listeners) ? $return : array();
	}

	/**
	 * Gets event listeners array
	 * Transforms array of extenders array to array of listener's callbacks
	 * and prepares autoloader for proxy handling only specified classes
	 *
	 * @return array
	 * */
	public function prepareDynamicListeners()
	{
		if (self::$_extendInput)
		{
			$listeners = array();
			$keys = array(
				'all', 'proxy_class', 'bb_code', 'controller', 'datawriter',
				'importer', 'mail', 'model', 'route_prefix', 'search_data', 'view'
			);

			//not grouped extenders - merge with 'all'
			$all = array_diff_key(self::$_extendInput, array_flip($keys));
			self::$_extendInput['all'] = isset(self::$_extendInput['all']) ? array_merge_recursive(self::$_extendInput['all'], $all) : $all;

			foreach ($keys as $type)
			{
				if (!empty(self::$_extendInput[$type]))
				{
					if ($type == 'proxy_class')
					{
						$event = 'load_class_proxy_class';
						$method = 'loadProxyClass';
						$newType = 'proxy';
					}
					else
					{
						$event  = 'load_class';
						$method = 'loadClass';
						$newType = 'all';
					}

					foreach (self::$_extendInput[$type] as $className => $extend)
					{
						if (!isset($listeners[$event][$className]) && !isset(self::$_extend[$newType][$className]))
						{
							$listeners[$event][$className] = array(
								array('WHM_Core_Listener', $method)
							);
						}
					}
					self::$_extend[$newType] = isset(self::$_extend[$newType]) ? array_merge_recursive(self::$_extend[$newType], self::$_extendInput[$type]) : self::$_extendInput[$type];
				}
			}
			self::$_extendInput = array();

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
	 * @param string $hint If specified (value other than an _), will only be run when the specified hint is provided
	 */
	public function prependListener($event, $callback, $hint = '_')
	{
		if (self::$enabled)
		{
			if (!isset($this->listeners[$event][$hint]))
			{
				$this->listeners[$event][$hint][] = $callback;
			}
			else
			{
				array_unshift($this->listeners[$event][$hint], $callback);
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
	 * @param string $hint If specified (value other than an _), will only be run when the specified hint is provided
	 */
	public function appendListener($event, $callback, $hint = '_')
	{
		if (self::$enabled)
		{
			$this->listeners[$event][$hint][] = $callback;
		}
	}

	/**
	 * Prepends or appends a listeners array to current events. This method takes an arbitrary
	 * callback, so can be used with more advanced things like object-based
	 * callbacks (and simple function-only callbacks).
	 *
	 * @param array   $listeners    Event to listen to
	 * @param boolean $prepend      Add to start of listener's list if true
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

	/**
	 * Add class lists for later class extender.
	 * Usually called from init_listeners event
	 * Deprecated !!!!
	 *
	 * Example how extend
	 * XenForo_DataWriter_Page
	 *      with Some_Addon_DataWriter_Node, Some_Addon_DataWriter_Page and
	 * XenForo_ViewPublic_Page_View
	 *      with Some_Addon_ViewPublic_Page_View:
	 *
	 * $this->addExtenders(
	 *      'datawriter' => array(
	 *           'XenForo_DataWriter_Page' => array(
	 *                'Some_Addon_DataWriter_Node',
	 *                'Some_Addon_DataWriter_Page'
	 *            ),
	 *            'XenForo_DataWriter_Forum' => array(
	 *                'Some_Addon_DataWriter_Node',
	 *                'Some_Addon_DataWriter_Forum'
	 *            )
	 *      ),
	 *      'view' => array(
	 *            'XenForo_ViewPublic_Page_View' => array(
	 *              'Some_Addon_ViewPublic_Page_View'
	 *          )
	 *      )
	 * );
	 *
	 * @param array $extenders Array of class list
	 * @param bool  $prepend Add to start of extenders's list if true
	 *
	 */
	public function addExtenders($extenders, $prepend = false)
	{
		if (self::$enabled)
		{
			if (self::$enabled)
			{
				if (!self::$_extendInput)
				{
					self::$_extendInput = $extenders;
				}
				else
				{
					self::$_extendInput = ($prepend) ? array_merge_recursive($extenders, self::$_extendInput) : array_merge_recursive(self::$_extendInput, $extenders);
				}
			}
		}
	}

	/**
	 * Extender method for single normal class with event autocreate
	 * Usage only after init_listeners event
	 *
	 * @param string $class   Class name to extend
	 * @param string $extend  Extend class name
	 * @param bool   $prepend Add to start of extenders's list if true
	 *
	 */
	public function extendClass($class, $extend, $prepend = false)
	{
		if (self::$enabled && $class && $extend)
		{
			if (!isset(self::$_extend['all'][$class]))
			{
				$this->prependListener('load_class', array('WHM_Core_Listener', 'loadClass'), $class);
			}
			else if ($prepend)
			{
				array_unshift(self::$_extend['all'][$class], $extend);

				return;
			}

			self::$_extend['all'][$class][] = $extend;
		}
	}

	/**
	 * Extender method for proxy classes
	 *
	 * @param string $class   Class name to extend
	 * @param string $extend  Extend class name
	 * @param bool   $prepend Add to start of extenders's list if true
	 *
	 */
	public function extendProxyClass($class, $extend, $prepend = false)
	{
		if (self::$enabled && $class && $extend)
		{
			if (!isset(self::$_extend['proxy'][$class]))
			{
				$this->prependListener('load_class_proxy_class', array('WHM_Core_Listener', 'loadProxyClass'), $class);
			}
			else if ($prepend)
			{
				array_unshift(self::$_extend['proxy'][$class], $extend);

				return;
			}
			self::$_extend['proxy'][$class][] = $extend;
		}
	}

	/**
	 * Core event listener - must be first in the queue in XenForo Admin CP
	 *
	 * !!!WARNING!!! XenForo not fully loaded at this time.
	 * Only for setup event listeners by WHM_Core_Listener methods
	 *
	 * @param WHM_Core_Listener $events - Core listener class instance
	 */
	public static function initListeners(WHM_Core_Listener $events)
	{
		self::$enabled = true;
		//Core listeners
		$events->addExtenders(
			array(
			     'proxy_class'                               => array(
				     'XenForo_DataWriter'                   => array(
					     array('WHM_Core_DataWriter_Abstract', 'abstract')
				     ),
				     'XenForo_ControllerAdmin_NodeAbstract' => array(
					     array('WHM_Core_ControllerAdmin_NodeAbstract', 'abstract')
				     )
			     ),
			     //datawriters
			     'XenForo_DataWriter_Discussion_Thread'      => 'WHM_Core_DataWriter_Thread',
			     'XenForo_DataWriter_DiscussionMessage_Post' => 'WHM_Core_DataWriter_Post',
			     'XenForo_DataWriter_Node'                   => 'WHM_Core_DataWriter_Node',
			     'XenForo_DataWriter_Forum'                  => 'WHM_Core_DataWriter_Node',
			     'XenForo_DataWriter_Page'                   => 'WHM_Core_DataWriter_Node',
			     'XenForo_DataWriter_Category'               => 'WHM_Core_DataWriter_Node',
			     'XenForo_DataWriter_LinkForum'              => 'WHM_Core_DataWriter_Node',
			     //models
			     'XenForo_Model_Thread'                      => 'WHM_Core_Model_Thread',
			     'XenForo_Model_Post'                        => 'WHM_Core_Model_Post',
			     'XenForo_Model_Node'                        => 'WHM_Core_Model_Node',
			     'XenForo_Model_Forum'                       => 'WHM_Core_Model_Forum',
			     //controllers
			     'XenForo_ControllerPublic_Forum'            => 'WHM_Core_ControllerPublic_Forum',
			     'XenForo_ControllerPublic_Thread'           => 'WHM_Core_ControllerPublic_Thread',
			     'XenForo_ControllerPublic_Post'             => 'WHM_Core_ControllerPublic_Post'
			)
		);
	}

	/**
	 * Init Dependencies event listener.
	 * Called when the dependency manager loads its default data.
	 * This event is fired on virtually every page and is the first thing you can plug into.
	 * And this callback is fired first of all callbacks with options class loaded
	 *
	 * Instantiating WHM_Core
	 * and firing init_application event (inside WHM_Core_Application::getInstance())
	 *
	 * @param XenForo_Dependencies_Abstract $dependencies
	 * @param array                         $data
	 *
	 * */
	public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		//first launch if core active
		WHM_Core_Application::getInstance();
	}

	/**
	 * Base callback called by event listeners in load_class event
	 * Extends class with list of classes preset in init_listeners event
	 * Counts XFCP proxy class declarations
	 * and handles multi extend classes (traits emulation)
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClass($class, array &$extend)
	{
		if (self::$enabled && isset(self::$_extend['all'][$class]))
		{
			if (!is_array(self::$_extend['all'][$class]))
			{
				self::$_extend['all'][$class] = array(self::$_extend['all'][$class]);
			}
			foreach(self::$_extend['all'][$class] as $dynamic)
			{
				if (empty(self::$_counters[$dynamic]))
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

	/**
	 * Base callback called by event listeners in load_class_proxy_class event
	 * Extends class with list of classes preset in init_listeners event
	 * Counts XFCP proxy class declarations
	 * and handles multi extend classes (traits emulation)
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadProxyClass($class, array &$extend)
	{
		if (self::$enabled && isset(self::$_extend['proxy'][$class]))
		{
			if (!is_array(self::$_extend['proxy'][$class]))
			{
				self::$_extend['proxy'][$class] = array(self::$_extend['proxy'][$class]);
			}
			$extend = array_merge($extend, self::$_extend['proxy'][$class]);
		}
	}
}