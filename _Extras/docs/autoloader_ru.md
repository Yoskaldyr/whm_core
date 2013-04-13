Автозагрузчик WHM_Core_Autoloader
=================================
Общие положения
---------------
Ядро для работы и реализации функционала использует собственный автозагрузчик классов и собственный аналог реестра очень похож на реестр XenForo_Application, но со своими особенностями.

Для включения полного функционала ядра, кроме установки дополнения через админку, надо включить подмену стандартного автозагрузчика XenForo.

Самый ранний вараинт без правки оригинальный файлов - это добавление инициализации автозагрузчика в `config.php`, которое никак не повлияет на обновление форума или на установку каких либо сторонних хаков и инициализируется приложением достаточно рано чтобы была возможность перехватить практически любой класс после его загрузки.

Обычный режим автозагрузчика. Production.
-----------------------------------------
Для включения автолоадера ядра надо в начало `config.php` добавить

~~~php
<?php
WHM_Core_Autoloader::getProxy();
~~~

При таком подходе `WHM_Core_Autoloader` грузится прямо перед `XenForo_FrontController` что позволяет при желании расширить  :

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

Хоть расширить теоретически можно любой класс, но практически удобное API, с динамическим наследованием, аналогичное стандартному API XenForo для обработчиков событий можно сделать только после стандартной загрузки `XenForo_CodeEvent`, и после прогрузки всех основных обработчиков событий из базы, т.е. после:

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

Режим разработки. Отдельная папка для каждого аддона.
-----------------------------------------------------
При разработке удобно когда каждый аддон лежит в полностью своей папке, что практически не осуществимо в текущей структуре папок XenForo (php, js и стили - все в разных папках).
Для этого у автолоадера есть режим поиска файлов в папке по альтернативному пути, причем внутри файлы хака могут располагаться исходя из нескольких вариантов соглашений (связано с тем что разработчики как только не называют свои классы при создании расширения).

Метод автозагрузчика для указания папки поиска дополнений `setAddonDir`.
Пример: инициализируем автозагрузчик и предварительно ищем дополнения в папке `/addons/`:

~~~php
<?php
WHM_Core_Autoloader::getProxy()->setAddonDir('addons');
~~~
Если автозагрузчик не находит класс по альтернативному пути, то он ищет его по первоначальному пути, т.е. в `/library/`.

### Соглашения по структуре и именованию папок дополнений
Разработчики при наименовании своих дополнений обычно используют 2 варианта наименования классов короткий и длинный:

1. Короткий - `AddOnName_SubClass` (соответсвенно хранится в `/library/AddOnName/SubClass.php`)
2. Длинный - `Author_AddOnName_SubClass` (соответсвенно хранится в `/library/Author/AddOnName/SubClass.php`)

Соотвественно id дополнения на основе названия классов можно считать `addonname` и `author_addonname` (для удобства http редиректов будут в нижнем регистре).
После чего простая последовательная проверка на наличие папок `author_addonname` и `addonname` позволяет точно сказать по какому соглашению проименованы классы в аддоне.

Учитывая уже сложившуюся структуру SVN репозиториев для WHM, когда файлы классов лежат в 1 папке `/library/WHM/AddonName/` (т.е. длинное наименование), а все дополнительные файлы (js/xml/style) лежат в `/library/WHM/AddonName/_Extras/`, то автозагрузчик обрабатывает и этот вариант хранения готового дополнения.

Т.е. дополнения с длинным наименованиями можно хранить так:

+ **Расположение по умолчанию:**
	+ xml файлов с аддоном и языками может вообще не быть в папках

~~~
/library/WHM/SomeAddon/Model/Forum.php
/library/WHM/SomeAddon/Model/Thread.php
/library/WHM/SomeAddon/Listener.php
/js/whm/someaddon/thread.js
/styles/whm/someaddon/image.jpg
~~~

+  **WHM-соглашение:**
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

+  **FullPath-соглашение:**
	+ папка дополнения первые 2 части класса через подчеркивание в нижнем регистре
	+ все кроме xml лежит в upload по полному пути
	+

~~~
/addons/whm_someaddon/upload/library/WHM/SomeAddon/Model/Forum.php
/addons/whm_someaddon/upload/library/WHM/SomeAddon/Model/Thread.php
/addons/whm_someaddon/upload/library/WHM/SomeAddon/Listener.php
/addons/whm_someaddon/upload/js/whm/someaddon/thread.js
/addons/whm_someaddon/upload/styles/whm/someaddon/image.jpg
/addons/whm_someaddon/xml/language.xml
~~~

Для дополнений с **коротким** стилем наименования используется только **FullPath-соглашение** только в качестве имени папки используется первая часть класса.

Во всех соглашениях за счет нижнего регистра названия аддона и присутствия частей названия аддона в путях к статическим файлам, легко сделать редирект с `/(js|styles)/` на соответствующую папку аддона.

### Привязка классов к определенному дополнению
Если дополнение использует сторонние классы, с другим префиксом/неймспейсом (типичный пример дополнение `TMS` использует сторонние классы `Diff_*`), то может понадобиться принудительно указать в какой папке искать класс с заданным префиксом.

Для это используется метод автозагрузчика `addAddonMap`, пример использования ниже в примере конфига.

### Типичный конфиг
Типичный конфиг, который добвляется в `config.php`, когда надо чтобы дополнения лежали в `/addons/`, при условии что сам хак ядра тоже будет лежать в этой папке:

~~~php
<?php
//вручную инклудим автозагрузчик
include('addons/whm_core/Autoloader.php');
//инициализируем автозагрузчик и устанавливаем путь поиска дополнений
WHM_Core_Autoloader::getProxy()->setAddonDir('addons');

//указываем что надо искать классы Diff_* в папке аддона tms
WHM_Core_Autoloader::getProxy()->addAddonMap(
	array(
	     'Diff' => 'tms'    //в качестве ключа самая первая/первые две части класса
	                        //в качестве значения папка дополнения
	)
);
~~~