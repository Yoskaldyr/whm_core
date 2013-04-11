#Production mode
All addons stored in /library/ (default location)
Add to config.php:

~~~php
WHM_Core_Autoloader::getProxy();
~~~

---------------

#Development mode
All addons stored in /addons/ (path configurable)

~~~php
include('addons/whm_core/Autoloader.php');
WHM_Core_Autoloader::getProxy()->setAddonDir('addons');

//Additional external library with different namespace stored with TMS addon
WHM_Core_Autoloader::getProxy()->addAddonMap(
	array(
	     'Diff' => 'tms'
	)
);
~~~
