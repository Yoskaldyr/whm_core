Production mode
===============
All addons stored in /library/ (default location)
Add to config.php:

~~~php
WHM_Core_Autoloader::getProxy();
~~~

-------------

Development mode
================
All addons (with WHM_Core add-on) stored in /addons/ (path configurable)
Add to config.php:

~~~php
include('addons/whm_core/upload/library/WHM/Core/Autoloader.php');
WHM_Core_Autoloader::getProxy()
	->setAddonDir('addons')
		//Additional external library with different namespace stored with TMS addon
	->addAddonMap(
		array(
	        'Diff' => 'tms'
		)
	);
~~~
