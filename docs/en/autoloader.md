Autoloader WHM_Core_Autoloader
==============================
Description
-----------
The core for development and building functional uses own class autoloader and own analog of registry which is very similar to registry XenForo_Application but has special features.

To activate full functional of the core you need to proceed standart installation process and enable replacement of standard XenForo autoloader.

The earliest method without edition of original files is initialization of autoloader in `config.php`. This method doesn't affect on forum update or installing any other addons. The core is initialized is early enough to catch almost any class after its initialization.

Standard mode of autoloader. Production.
----------------------------------------
To activate core's autoloader insert the following into beginning of `config.php`:

~~~php
<?php
WHM_Core_Autoloader::getProxy();
~~~

Using such method `WHM_Core_Autoloader` is loaded right before `XenForo_FrontController`. It allows to extend almost every class:

~~~
1. index.php
2. library/XenForo/Autoloader.php
3. library/XenForo/Application.php
4. library/Zend/Registry.php
5. library/Lgpl/utf8.php
6. library/Zend/Config.php
7. library/config.php
8. library/WHM/Core/Autoloader.php
9. library/XenForo/FrontController.php
~~~

To provide suitable API compatible with dinamic class resolution API proxy loader is initialized after `XenForo_CodeEvent`:

~~~
9.  library/XenForo/FrontController.php
10. library/XenForo/Dependencies/Public.php
11. library/XenForo/Dependencies/Abstract.php
12. library/Zend/*
    ...
17. library/XenForo/Model.php
18. library/XenForo/Model/DataRegistry.php
19. library/XenForo/CodeEvent.php
20. library/Zend/Db*
    ...
30. library/WHM/Core/Application.php
~~~

Development mode. Separate folder for each addon.
-------------------------------------------------
During development it is convenient when each addon has own folder. It is impossible with current file structure of XenForo (php, js and styles are in different directories).
For this autoloader has mode of searching files in directory with alternative path. File structure of this directory need to satisfy one of the conventions (since there exists several addon structure standards).

Autoloader's method for setting directory of searching addons is `setAddonDir`.

> Remark. All the public setters including `setAddonDir` return the autoloader object. It allows to set all the options by chain of calls.

Example: we initialize autoloader and right after we search addons in directory `/addons/`:

~~~php
<?php
WHM_Core_Autoloader::getProxy()->setAddonDir('addons');
~~~
If autoloader fails to find class by alternative path it seaches it by the default path, i.e. `/library/`.

### Conventions about file structure.
Developers usually follow one of two conventions, short and long:

1. Short  - `AddOnName_SubClass` (located in `/library/AddOnName/SubClass.php`)
2. Long  - `Author_AddOnName_SubClass` (located in`/library/Author/AddOnName/SubClass.php`)

Then, we may assume addon id is `addonname` и `author_addonname` (lowercase for convenient work with http redirects).

I.e. addon with long naming `Author_AddOnName_SubClass` we may keep as following:

+ **Default file placement:**
	+ xml (optionally)

~~~
/library/Author/SomeAddon/Model/Forum.php
/library/Author/SomeAddon/Model/Thread.php
/library/Author/SomeAddon/Listener.php
/js/author/someaddon/thread.js
/styles/author/someaddon/image.jpg
~~~

+  **WHM-convetion:**
    + addon dir, 2 first parts of the class name using "_" in lowcase
    + last part of class name as in library
    + the rest is in _Extras
    + xml is in _Extras

~~~
/addons/author_someaddon/Model/Forum.php
/addons/author_someaddon/Model/Thread.php
/addons/author_someaddon/Listener.php
/addons/author_someaddon/_Extras/js/whm/someaddon/thread.js
/addons/author_someaddon/_Extras/styles/whm/someaddon/image.jpg
/addons/author_someaddon/_Extras/xml/language.xml
~~~

+  **FullPath-convention:**
	+ addon dir first two parts of the class using "_" as delimiter
	+ all except xml is in upload folder by full path

~~~
/addons/author_someaddon/upload/library/Author/SomeAddon/Model/Forum.php
/addons/author_someaddon/upload/library/Author/SomeAddon/Model/Thread.php
/addons/author_someaddon/upload/library/Author/SomeAddon/Listener.php
/addons/author_someaddon/upload/js/author/someaddon/thread.js
/addons/author_someaddon/upload/styles/author/someaddon/image.jpg
/addons/author_someaddon/xml/language.xml
/addons/author_someaddon/xml/addon.xml
~~~

For addons with **short** style of naming `AddOnName_SubClass` it is used only **FullPath-convention** but first part of the class serves as dir name.

Addon dir uses lowercase and pathes to static files include parts of addon name. Due to this in all the conventions it is easy to make redirect from `/(js|styles)/` to corresponding addon dir.

### Associating classes to the addon
If addon uses third-party classes with other prefix/namespase (like `TMS` uses classes `Diff_*`), then it may need to specify the class of such prefix.

For this purpose it is used autoloader method `addAddonMap`, example of using it in config:

### Typical config
Typical config which added to `config.php` when we need addons to be in `/addons/` folder (Core addon also need to be in this folder):

~~~php
<?php
//manually include autoloader
include('addons/whm_core/upload/library/WHM/Core/Autoloader.php');
//init autoloader and set path for searching addons
WHM_Core_Autoloader::getProxy()
	->setAddonDir('addons')
		//since setters may be called by chain
		//then, point that Diff_* classes are in tms addon dir
	->addAddonMap(
		array(
	        'Diff' => 'tms' //first/first two parts of the class as key
	                        //dir as value
		)
);
~~~
