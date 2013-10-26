<?php

/**
 * Class WHM_Core_Autoloader
 * WHM Autoloader class
 *
 * @package WHM_Core
 * @author Yoskaldyr <yoskaldyr@gmail.com>
 * @version 1000011 $Id$
 * @since 1000011
 */
class WHM_Core_Autoloader extends XenForo_Autoloader
{
	protected $_eval = null;

	/**
	 * WHM_Core_Listener cache.
	 *
	 * @var WHM_Core_Listener
	 */
	protected $_events = null;
	/**
	 * Path to directory containing the application's library.
	 *
	 * @var string
	 */
	protected $_rootDir = '.';

	/**
	 * Path to directory containing the application's library.
	 *
	 * @var string
	 */
	protected $_addonDir = null;

	/**
	 * Array of class prefix to addon prefix bindings.
	 *
	 * @var array
	 */
	protected $_addonMap = array();

	/**
	 * Path to directory for storing the proxy classes.
	 *
	 * @var string
	 */
	protected $_classDir = null;

	/**
	 * On autoload this class init_listeners event will be fired.
	 *
	 * @var string
	 */
	protected $_initListenersClass = 'XenForo_Options';

	/**
	 * On autoload this class init_listeners event will be fired.
	 *
	 * @var bool
	 */
	protected $_initListenersFired = false;

	/**
	 * Public setter for _initListenersClass
	 *
	 * @param string $class
	 * @return $this
	 */
	public function setInitClass($class = '')
	{
		$this->_initListenersClass = (string) $class;
		return $this;
	}

	protected function _fireInitListeners()
	{
		if (!$this->_initListenersFired)
		{
			$events = WHM_Core_Listener::getInstance();
			XenForo_CodeEvent::fire('init_listeners', array($events));
			unset($events->listeners['init_listeners']);

			$events->listeners = array_merge_recursive(
				array('init_dependencies' => array(
					array('WHM_Core_Listener', 'initDependencies')
				)),
				$events->prepareDynamicListeners(),
				$events->listeners
			);
			$this->_events = $events;
			$this->_initListenersFired = true;
		}
	}

	/**
	 * Public setter for _eval
	 *
	 * @param boolean $eval Fail-safe proxy loader with php 'eval'.
	 * @return $this
	 */
	public function setEval($eval = true)
	{
		$this->_eval = (bool) $eval;
		return $this;
	}

	/**
	 * Public setter for _addonDir
	 *
	 * @param string $dir   New addon directory.
	 *                      If empty addon's autoloader will be disabled.
	 * @return $this
	 */
	public function setAddonDir($dir = '')
	{
		$this->_addonDir = ($dir && ($dir = trim((string)$dir)) && @is_readable($dir) && @is_dir($dir)) ? $dir : null;
		return $this;
	}

	/**
	 * Public setter for _addonMap
	 * @param array $map Array of class prefix to addon prefix bindings to add
	 * @return $this
	 * */
	public function addAddonMap($map)
	{
		if ($map && is_array($map))
		{
			$this->_addonMap = $map + $this->_addonMap;
		}
		return $this;
	}

	/**
	 * Manually reset the new autoloader instance. Use this to inject a modified version.
	 *
	 * @param XenForo_Autoloader|null
	 * @return WHM_Core_Autoloader
	 */
	public static function getProxy()
	{
		$instance = XenForo_Autoloader::getInstance();
		if (!($instance instanceof WHM_Core_Autoloader))
		{
			$newInstance = new self();
			$newInstance->setupAutoloader($instance->getRootDir());
			XenForo_Autoloader::setInstance($newInstance);
			return $newInstance;
		}
		return $instance;
	}

	/**
	* Internal method that actually applies the autoloader. See {@link setupAutoloader()}
	* for external usage.
	*/
	protected function _setupAutoloader()
	{
		spl_autoload_unregister(array(XenForo_Autoloader::getInstance(), 'autoload'));
		parent::_setupAutoloader();
	}

	/**
	 * Autoload the specified class.
	 *
	 * @param string $class Name of class to autoload
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function autoload($class)
	{
		if ($class == $this->_initListenersClass)
		{
			$this->_fireInitListeners();
		}

		if (class_exists($class, false) || interface_exists($class, false))
		{
			return true;
		}

		// Multi dynamic proxy class
		if (strpos($class, '__'))
		{
			list($baseClass, $counter) = explode('__', $class);
			$counter = intval($counter);
			if (!$counter || ($class != $baseClass . '__' . $counter))
			{
				return false;
			}

			$baseFile = $this->autoloaderClassToFile($baseClass);
			$timestamp = @filemtime($baseFile);
			if (!$baseFile || !$timestamp)
			{
				return false;
			}
			$proxyFile = $this->_proxyFile($class, $timestamp);
			//slow but safe eval fallback
			if ($this->_isEval())
			{
				if ($body = $this->_getDynamicBody($baseClass, $counter, $baseFile))
				{
					eval('?>' . $body);
				}
			}
			//if ready or created
			else if (
				file_exists($proxyFile)
				|| $this->_saveClass($proxyFile, $this->_getDynamicBody($baseClass, $counter, $baseFile))
			)
			{
				/** @noinspection PhpIncludeInspection */
				include($proxyFile);
			}
		}
		else if (isset($this->_events->listeners['load_class_proxy_class'][$class]))
		{
			$baseFile = $this->autoloaderClassToFile($class);
			$timestamp = @filemtime($baseFile);
			if (!$baseFile || !$timestamp)
			{
				return false;
			}

			$extend = array();
			XenForo_CodeEvent::fire('load_class_proxy_class', array($class, &$extend), $class);

			if ($extend)
			{
				$createClass = 'XFProxy_' . $class;
				$proxyFile = $this->_proxyFile($class, $timestamp);
				//slow but safe eval fallback
				if ($this->_isEval())
				{
					if ($body = $this->_getProxyBody($class, $baseFile))
					{
						eval('?>'.$body);
					}
				}
				//if ready or created
				else if (
					file_exists($proxyFile)
					|| $this->_saveClass($proxyFile, $this->_getProxyBody($class, $baseFile))
				)
				{
					/** @noinspection PhpIncludeInspection */
					include($proxyFile);
				}
				//dynamic resolve only if proxy class loaded
				if (class_exists($createClass, false) || interface_exists($createClass, false))
				{
					try
					{
						$type = 'class';
						foreach ($extend AS $dynamicClass)
						{
							if (is_array($dynamicClass))
							{
								if (!empty($dynamicClass[1]))
								{
									switch ($dynamicClass[1])
									{
										case 'abstract':
											$type = 'abstract class';
											break;
										case 'interface':
											$type = 'interface';
											break;
									}
									$dynamicClass = $dynamicClass[0];
								}
								else
								{
									continue;
								}
							}
							// XenForo Class Proxy, in case you're wondering
							$proxyClass = 'XFCP_' . $dynamicClass;
							eval($type . ' ' . $proxyClass . ' extends ' . $createClass . ' {}');
							$this->autoload($dynamicClass);
							$createClass = $dynamicClass;
						}
						eval($type . ' ' . $class . ' extends ' . $createClass . ' {}');
					}
					catch (Exception $e)
					{
						throw $e;
					}
				}
			}
			else
			{
				/** @noinspection PhpIncludeInspection */
				include($baseFile);
			}
			return (class_exists($class, false) || interface_exists($class, false));
		}
		return parent::autoload($class);
	}

	/**
	 * Resolves a class name to an autoload path.
	 *
	 * @param string $class Name of class to autoload
	 *
	 * @return string|boolean False if the class contains invalid characters.
	 */
	public function autoloaderClassToFile($class)
	{
		if (preg_match('#[^a-zA-Z0-9_]#', $class))
		{
			return false;
		}
		if ($this->_addonDir)
		{
			$chunks = explode('_', $class);
			if (sizeof($chunks) > 1)
			{
				//shot addon prefix (only addonName)
				$classPrefix = $chunks[0];
				$dir = $this->_addonDir . '/' . (!empty($this->_addonMap[$classPrefix]) ? $this->_addonMap[$classPrefix] : strtolower($classPrefix));
				if (sizeof($chunks)>2)
				{
					//long addon prefix (providerName_addonName)
					$classPrefix = $chunks[0] . '_' . $chunks[1];
					$dirLong = $this->_addonDir . '/' . (!empty($this->_addonMap[$classPrefix]) ? $this->_addonMap[$classPrefix] : strtolower($classPrefix));
					if (file_exists($dirLong))
					{
						$dir = $dirLong;
					}
				}
				// Short convention with _Extra dir
				$fileShort = $dir . '/' . implode('/', array_slice($chunks, 2)) . '.php';
				// Long convention with full path (upload/library)
				$fileLong = $dir . '/upload/library/' . implode('/', $chunks) . '.php';

				if (file_exists($fileShort))
				{
					return $fileShort;
				}
				else if (file_exists($fileLong))
				{
					return $fileLong;
				}
			}
		}
		return $this->_rootDir . '/' . str_replace('_', '/', $class) . '.php';
		//return parent::autoloaderClassToFile($class);
	}

	/**
	 * Gets the path to the proxy class directory (internal_data/proxy_classes).
	 * This directory can be moved above the web root.
	 *
	 * @return string Absolute path
	 */
	protected function _getProxyPath()
	{
		if ($this->_classDir)
		{
			return $this->_classDir;
		}
		if (XenForo_Application::isRegistered('config'))
		{
			return $this->_classDir = XenForo_Helper_File::getInternalDataPath() . '/proxy_classes';
		}
		else
		{
			return $this->_rootDir . '/../internal_data/proxy_classes';
		}
	}

	/**
	 * Creates directory for proxy classes
	 *
	 * @return boolean   Returns true on success
	 */
	protected function _createProxyDirectory()
	{
		if (!is_dir($path = $this->_getProxyPath()))
		{
			if (XenForo_Helper_File::createDirectory($path))
			{
				return XenForo_Helper_File::makeWritableByFtpUser($path);
			}
			else
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Generates body of proxy class for base XenForo class
	 *
	 * @param string  $class    Original class name
	 * @param string  $baseFile Path to original class file
	 *
	 * @return boolean|string     Returns body of class or false if error
	 */
	protected function _getProxyBody($class, $baseFile = '')
	{
		if (!$baseFile)
		{
			$baseFile = $this->autoloaderClassToFile($class);
		}
		if ($body = file_get_contents($baseFile))
		{
			$body = preg_replace('#([\s\n](class|interface)[\s\n]+)(' . $class . ')([\s\n\{])#u', '$1XFProxy_$3$4', $body, 1, $count);
			return ($count) ? $body : false;
		}
		return false;
	}

	/**
	 * Generates body of dynamic class for multi declarations of XFCP_ proxy classes
	 *
	 * @param string  $class    Original class name
	 * @param integer $counter  Class declaration counter (used as postfix)
	 * @param string  $baseFile Path to original class file
	 *
	 * @return boolean|string     Returns body of class or false if error
	 */
	protected function _getDynamicBody($class, $counter, $baseFile = '')
	{
		if (!$counter)
		{
			return false;
		}
		if (!$baseFile)
		{
			$baseFile = $this->autoloaderClassToFile($class);
		}
		if ($body = file_get_contents($baseFile))
		{
			$body = preg_replace('#([\s\n]class[\s\n]+)(' . $class . ')([\s\n]+extends[\s\n]+XFCP_)(' . $class . ')([\s\n\{])#u', '$1$2__' . $counter . '$3$4__' . $counter . '$5', $body, 1, $count);
			return ($count) ? $body : false;
		}
		return false;
	}

	/**
	 * Saves proxy class
	 *
	 * @param string  $proxyFile Path to proxy class file
	 * @param string  $body      Body of class
	 *
	 * @return boolean  Returns true on success
	 */
	protected function _saveClass($proxyFile, $body)
	{
		return (
			$this->_createProxyDirectory()
				&& !empty($body)
				&& file_put_contents($proxyFile, $body)
				&& XenForo_Helper_File::makeWritableByFtpUser($proxyFile)
		);
	}

	/**
	 * Resolves a class name to an proxyload path.
	 *
	 * @param string         $class     Name of class to proxyload
	 * @param string|integer $timestamp Modify time of base class
	 *
	 * @return string|boolean False if the class contains invalid characters.
	 */
	protected function _proxyFile($class, $timestamp)
	{
		if (preg_match('#[^a-zA-Z0-9_]#', $class) || !$timestamp)
		{
			return false;
		}

		return $this->_getProxyPath() . '/' . $class . '__' . $timestamp . '.php';
	}

	protected function _isEval()
	{
		return is_null($this->_eval) ? ($this->_eval = file_exists($this->_getProxyPath() . '/eval.txt')) : $this->_eval;
	}

}