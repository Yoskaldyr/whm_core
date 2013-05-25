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
	 * Listeners cache array
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
	 * Dynamic extenders array
	 * @var array
	 */
	protected static $_extend = array();

	/**
	 * XFCP_* classes loader counter
	 * @var array
	 */
	protected static $_counters = array();

	/**
	 * List of handled load_class_* event types
	 * @var array
	 */
	protected $_validTypes = array(
		 'proxy_class', 'bb_code', 'controller', 'controller_helper', 'datawriter', 'importer',
		 'mail', 'model', 'route_prefix', 'search_data', 'view'
	);

	/**
	 * Constructor. Sets original listeners cache from parent class
	 */
	public function __construct()
	{
		$this->listeners = $this->getXenForoListeners();
	}

	/**
	 * Returns array of original listeners from parent (XenForo_CodeEvent)
	 * @return array
	 */
	public function getXenForoListeners()
	{
		return ($return = parent::$_listeners) ? $return : array();
	}

	/**
	 * Gets event listeners array
	 * Transforms array of extenders array to array of listener's callbacks
	 * and prepares autoloader for proxy handling only specified classes
	 *
	 * @return array
	 * */
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
		self::addExtendersStatic($extenders, $prepend);
	}

	/**
	 * Static version of addExtenders method
	 * @see WHM_Core_Listener::addExtenders
	 *
	 * @param array $extenders Array of class list
	 * @param bool  $prepend   Add to start of extenders's list if true
	 *
	 */
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
			     'proxy_class' => array(
				     'XenForo_DataWriter' => array(
					     array('WHM_Core_DataWriter_Abstract', 'abstract')
				     ),
				     'XenForo_ControllerAdmin_NodeAbstract' => array(
					     array('WHM_Core_ControllerAdmin_NodeAbstract', 'abstract')
				     ),
				     'XenForo_Image_Abstract' => array(
					     array('WHM_Core_Image_Abstract', 'abstract')
				     ),
				     'XenForo_Controller' => array(
					     array('WHM_Core_Controller', 'abstract')
				     )
			     ),
			     'datawriter' => array(
				     'XenForo_DataWriter_Discussion_Thread' => array(
					     'WHM_Core_DataWriter_Thread'
				     ),
				     'XenForo_DataWriter_DiscussionMessage_Post' => array(
					     'WHM_Core_DataWriter_Post'
				     ),
				     'XenForo_DataWriter_Node' => array(
					     'WHM_Core_DataWriter_Node'
				     ),
				     'XenForo_DataWriter_Forum' => array(
					     'WHM_Core_DataWriter_Node'
				     ),
				     'XenForo_DataWriter_Page' => array(
					     'WHM_Core_DataWriter_Node'
				     ),
				     'XenForo_DataWriter_Category' => array(
					     'WHM_Core_DataWriter_Node'
				     ),
				     'XenForo_DataWriter_LinkForum' => array(
					     'WHM_Core_DataWriter_Node'
				     )
			     ),
			     'model' => array(
				     'XenForo_Model_Attachment' => array(
					     'WHM_Core_Model_Attachment'
				     ),
				     'XenForo_Model_Thread' => array(
					     'WHM_Core_Model_Thread'
				     ),
				     'XenForo_Model_Post' => array(
					     'WHM_Core_Model_Post'
				     ),
				     'XenForo_Model_Node' => array(
					     'WHM_Core_Model_Node'
				     ),
				     'XenForo_Model_Forum' => array(
					     'WHM_Core_Model_Forum'
				     )
			     ),
			     'controller' => array(
				     'XenForo_ControllerPublic_Forum' => array(
					     'WHM_Core_ControllerPublic_Forum'
				     ),
				     'XenForo_ControllerPublic_Thread' => array(
					     'WHM_Core_ControllerPublic_Thread'
				     ),
				     'XenForo_ControllerPublic_Post' => array(
					     'WHM_Core_ControllerPublic_Post'
				     ),
				     'XenForo_ControllerPublic_Attachment' => array(
					     'WHM_Core_ControllerPublic_Attachment'
				     ),
			     ),
			     'view' => array(
				     'XenForo_ViewPublic_Attachment_DoUpload' => array(
					     'WHM_Core_ViewPublic_Attachment_DoUpload'
				     )
			     )
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
	 * Event listener for load_class_bb_code event
	 * Called when instantiating a BB code formatter.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassBbCode($class, array &$extend)
	{
		self::_mergeExtend('bb_code', $class, $extend);
	}

	/**
	 * Event listener for load_class_controller event
	 * Called when instantiating a controller.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassController($class, array &$extend)
	{
		self::_mergeExtend('controller', $class, $extend);
	}

	/**
	 * Event listener for load_class_controller_helper event
	 * Called when instantiating a controller helper.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassControllerHelper($class, array &$extend)
	{
		self::_mergeExtend('controller_helper', $class, $extend);
	}

	/**
	 * Event listener for load_class_datawriter event
	 * Called when instantiating a data writer.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassDatawriter($class, array &$extend)
	{
		self::_mergeExtend('datawriter', $class, $extend);
	}

	/**
	 * Event listener for load_class_importer event
	 * Called when instantiating an importer.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassImporter($class, array &$extend)
	{
		self::_mergeExtend('importer', $class, $extend);
	}

	/**
	 * Event listener for load_class_mail event
	 * Called when instantiating a mail object.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassMail($class, array &$extend)
	{
		self::_mergeExtend('mail', $class, $extend);
	}

	/**
	 * Event listener for load_class_model event
	 * Called when instantiating a model.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassModel($class, array &$extend)
	{
		self::_mergeExtend('model', $class, $extend);
	}

	/**
	 * Event listener for load_class_route_prefix event
	 * Called when instantiating a specific route prefix class.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassRoutePrefix($class, array &$extend)
	{
		self::_mergeExtend('route_prefix', $class, $extend);
	}

	/**
	 * Event listener for load_class_search_data event
	 * Called when instantiating a search data handler.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassSearchData($class, array &$extend)
	{
		self::_mergeExtend('search_data', $class, $extend);
	}

	/**
	 * Event listener for load_class_view event
	 * Called when instantiating a view.
	 * This event can be used to extend the class that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassView($class, array &$extend)
	{
		self::_mergeExtend('view', $class, $extend);
	}

	/**
	 * Event listener for load_class_proxy_class event
	 * Called when autoloading a class with proxy autoload.
	 * This event can be used to extend the any base XenForo class
	 * that will be instantiated dynamically.
	 *
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
	public static function loadClassProxyClass($class, array &$extend)
	{
		self::_mergeExtend('proxy_class', $class, $extend);
	}

	/**
	 * Base callback called by event listeners in load_class_* events
	 * Extends class with list of classes preset in init_listeners event
	 * Counts XFCP proxy class declarations
	 * and handles multi extend classes (traits emulation)
	 *
	 * @param string $type   Type of load class event (load_class_* postfix)
	 * @param string $class  The name of the class to be created
	 * @param array  $extend A modifiable list of classes that wish to extend the class
	 */
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