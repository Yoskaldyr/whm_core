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

### Соглашения по структуре и именованию папок дополнений
Разработчики при наименовании своих дополнений обычно используют 2 варианта наименования классов короткий и длинный:

1. Короткий - `AddOnName_SubClass` (соответсвенно хранится в `/library/AddOnName/SubClass.php`)
2. Длинный - `Author_AddOnName_SubClass` (соответсвенно хранится в `/library/Author/AddOnName/SubClass.php`)

Соотвественно id дополнения на основе названия классов можно считать `addonname` и `author_addonname` (для удобства http редиректов будут в нижнем регистре).
После чего простая последовательная проверка на наличие папок `author_addonname` и `addonname` позволяет точно сказать по какому соглашению проименованы классы в аддоне.

Учитывая уже сложившуюся структуру SVN репозиториев для WHM, когда файлы классов лежат в 1 папке `/library/WHM/AddonName/` (т.е. длинное наименование), а все дополнительные файлы (js/xml/style) лежат в `/library/WHM/AddonName/_Extras/`, то автозагрузчик обрабатывает и этот вариант хранения готового дополнения.

Т.е. дополнения с длинным наименованиями можно хранить так:

+ **Default file placement:**
	+ xml файлов с аддоном и языками может вообще не быть в папках

~~~
/library/WHM/SomeAddon/Model/Forum.php
/library/WHM/SomeAddon/Model/Thread.php
/library/WHM/SomeAddon/Listener.php
/js/whm/someaddon/thread.js
/styles/whm/someaddon/image.jpg
~~~

+  **WHM-convetion:**
	+ папка дополнения первые 2 части класса через подчеркивание в нижнем регистре
	+ остальная часть пути класса как в library
	+ все остальное лежит в _Extras
	+ xml лежит в _Extras

~~~
/addons/whm_someaddon/Model/Forum.php
/addons/whm_someaddon/Model/Thread.php
/addons/whm_someaddon/Listener.php
/addons/whm_someaddon/_Extras/js/whm/someaddon/thread.js
/addons/whm_someaddon/_Extras/styles/whm/someaddon/image.jpg
/addons/whm_someaddon/_Extras/xml/language.xml
~~~

+  **FullPath-convention:**
	+ папка дополнения первые 2 части класса через подчеркивание в нижнем регистре
	+ все кроме xml лежит в upload по полному пути

~~~
/addons/whm_someaddon/upload/library/WHM/SomeAddon/Model/Forum.php
/addons/whm_someaddon/upload/library/WHM/SomeAddon/Model/Thread.php
/addons/whm_someaddon/upload/library/WHM/SomeAddon/Listener.php
/addons/whm_someaddon/upload/js/whm/someaddon/thread.js
/addons/whm_someaddon/upload/styles/whm/someaddon/image.jpg
/addons/whm_someaddon/xml/language.xml
~~~

Для дополнений с **коротким** стилем наименования используется только **FullPath-convention** только в качестве имени папки используется первая часть класса.

Во всех соглашениях за счет нижнего регистра названия аддона и присутствия частей названия аддона в путях к статическим файлам, легко сделать редирект с `/(js|styles)/` на соответствующую папку аддона.

### Привязка классов к определенному дополнению
Если дополнение использует сторонние классы, с другим префиксом/неймспейсом (типичный пример дополнение `TMS` использует сторонние классы `Diff_*`), то может понадобиться принудительно указать в какой папке искать класс с заданным префиксом.

Для это используется метод автозагрузчика `addAddonMap`, пример использования ниже в примере конфига.

### Типичный конфиг
Типичный конфиг, который добвляется в `config.php`, когда надо чтобы дополнения лежали в `/addons/`, при условии что сам хак ядра тоже будет лежать в этой папке:

~~~php
<?php
//вручную инклудим автозагрузчик
include('addons/whm_core/upload/library/WHM/Core/Autoloader.php');
//инициализируем автозагрузчик и устанавливаем путь поиска дополнений
WHM_Core_Autoloader::getProxy()
	->setAddonDir('addons')
		//т.к. сеттеры можно вызывать цепочкой последовательно, то далее
		//указываем что надо искать классы Diff_* в папке аддона tms
	->addAddonMap(
		array(
	        'Diff' => 'tms' //в качестве ключа самая первая/первые две части класса
	                        //в качестве значения папка дополнения
		)
);
~~~
