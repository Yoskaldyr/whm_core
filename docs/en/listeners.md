Class WHM_Core_Listener. Extended work with events
==================================================
One of the bottlenecks of XenForo during active development is the need to setup each event in admin panel. One can work with events straightforward using `XenForo_CodeEvent`, but it has only basic functional, only static methods and not very handy API. That's why there was created a class for extended work with events `WHM_Core_Listener` which extends  `XenForo_CodeEvent`. It allowed to access all the original event listeners of XenForo.

Basic work with events
----------------------
For convenience of setup all the event listeners there was created a special event `init_listeners` which runs from autoloader before all the other events including `init_dependencies`.

Event callbacks signature:
`public static function initListeners(WHM_Core_Listener $events)`

**Attention!** This events starts before the full initialization of the application, for example, before options. Then, it's recommended to use only `WHM_Core_Listener` methods.

Adding of events
----------------
In the object `$events` there is an array (info about event listeners from admin panel) of event listeners  `$listeners`,where keys are events and values are arrays of callbacks (event listeners for event).
Example of adding some events in the end queue:

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->listeners['init_application'][] = array('Some_Class_Listener', 'initApplication');
	$events->listeners['template_hook'][] = array('Some_Class_Listener', 'templateHook');
}
~~~
Also listeners can be added using methods:
`prependListener($event, $callback)` - add listener to the beginning of queue for event
`appendListener($event, $callback)` - add listener to the end of queue for event
`addListeners(array $listeners, $prepend = false)` - add the set of listeners to beginning or to the end of queue, i.e. just merging of two arrays in appropriate order

Extending management
--------------------
Dynamic extending using `load_class_*` events is convenient in XenForo, but this events have to be described in more than one place. Also one need to write lots of repeating code (most of listeners are similar, only class name is the difference). But in 99% one just needs to extend some given class by another.
To simplify it and to reduce description to one place (no need `load_class_*` listeners) there are added special extending methods. I.e. it's enough to add chain of extendings in event `init_listeners` and this will create listeners automatically.

For this is is used method
`public function addExtenders($extenders, $prepend = false)`

Example of extension of `XenForo_DataWriter_Page` by `Some_Addon_DataWriter_Node` and `Some_Addon_DataWriter_Page`, and also
extension `XenForo_ViewPublic_Page_View` by `Some_Addon_ViewPublic_Page_View`:

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->addExtenders(
        'datawriter' => array(
            'XenForo_DataWriter_Page' => array(
                'Some_Addon_DataWriter_Page'
            ),
            'XenForo_DataWriter_Forum' => array(
                'Some_Addon_DataWriter_Forum'
            )
        ),
        'view' => array(
            'XenForo_ViewPublic_Page_View' => array(
                'Some_Addon_ViewPublic_Page_View'
            )
        )
	);
}
~~~
**Attention!** Code event listeners automatically generated for `load_class_*` events based on description `addExtenders` run **before the others**, i.e. before listeners described in ACP XenForo and before listeners described using `listeners` methods.

Inheritance of basic XenForo classes
------------------------------------
Using own autoloader for files we solve the problem of extension of non-extending classes of XenForo.
For example, in order to extend the class called directly everywhere (`XenForo_Link`), it is possible to make a phisical copy of the class with changed name, for example, `XFProxy_XenForo_Link`, then dinamically extend a chain of classes like every dynamic class in XenForo (declared with 'eval'), after that again declare class `XenForo_Link` using eval.

I.e. instead of including `XenForo_Link` we obtain a chain:

1. Create a copy of file with changed name by name with prefix, i.e. `XFProxy_XenForo_Link`.
2. Standard dynamic declaration of chains of `XFCP_*` classes using `eval` with includes of addons' files.
3. Declaration of original class `XenForo_Link` using `eval` which extends the last class in the chain above.

For convenient work with such a proxy extension there was added event `load_class_proxy_class` with interface similar to other `load_class_*` events, but with some differences due to specific of such an extension.

### Особенности реализации
Копия создается при первой инициализации прокси класса с проверкой времени модификации файла, так что это не вызывает проблем в производительности на продакшене или проблем частого обновления файлов при разработке.

Т.к. это динамическое наследование происходит внутри автозагрузчика классов, то проверять для каждого загружаемого класса возможность расширения его с помощью события `load_class_proxy_class` неэффективно в плане производительности, поэтому для этого сделан отдельный метод класса `WHM_Core_Autoloader` - `public function addClass($class = '')`. Только после добавления имени обрабатываемого класса в автозагрузчик для этого класса в момент автозагрузки запустится событие `load_class_proxy_class`.

Пример добавления класса `Some_Class` для обработки событием `load_class_proxy_class`

~~~php
<?php
WHM_Core_Autoloader::getProxy()->addClass('Some_Class')
~~~

При использовании метода `addExtenders` для события `load_class_proxy_class` классы автоматически добавятся в автолоадер, т.е. при вызове:

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->addExtenders(
		array(
	        'proxy_class' => array(
		        'Some_XenForo_Class' => array(
			        'Some_AddOn_Class'
		        )
	        )
		)
	);
}
~~~
вызывать `addClass('Some_XenForo_Class')` нет необходимости.
Т.е. наследование через метод `addExtenders` - рекомендованное использование.

**Внимание!** При расширении абстрактных классов вместо имени класса, которым надо расширить, указывается массив из этого имени и 2-го поля со значением `'abstract'`, например:

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->addExtenders(
		array(
	        'proxy_class' => array(
				'XenForo_DataWriter' => array(
					array('AddOn_DataWriter_Abstract', 'abstract')
				)
			)
		)
	);
}
~~~

**Внимание!** Стоит помнить что при такой динамической подмене тело статических методов начинает принадлежать другому классу. Для примера, еслу будем расширять
аддоном класс `XenForo_Link` (хороший пример, т.к. в нем одни статические методы), то тело всех методов (т.е. сами базовые родительские методы) будет находиться в `XFProxy_XenForo_Link`, а не внутри `XenForo_Link`. И как следствие обращаться к статическим методам оригинального класса из дочерних методов аддона, динамически отнаследованых в `load_class_proxy_class` надо будет только по полному пути, напрмиер `XFProxy_XenForo_Link::buildAdminLink` или `parent::buildAdminLink`, но не `XenForo_Link::buildAdminLink`, т.к. класс с именем `XenForo_Link` еще не загружен, и не `self::buildAdminLink`, который может давать неожиданные результаты, учитывая особенности видимости `self` для статических методов не инстанцированных классов.

Мультинаследование или наследование одним классом аддона нескольких классов XenForo.
------------------------------------------------------------------------------------
Из-за невозможности повторного декларирования одного и того же класса, нельзя использовать 1 класс для расширения однотипных классов XenForo. Типичный пример  - датарайтеры узлов или обработчики ббкодов. Например, чтобы добавить функционал во все типы узлов приходится в аддоне создавать отдельный класс для каждого типа узла, даже если содержание в них будет полностью одинаковым.
Но используя метод автоматического создания копии при повторном декларировании (метод аналогичный методу расширения базовых классов ксена описанному выше), можно добиться мультинаследованияпрокси классов без дублирования кода.

Например, можно расширить 1 классом `Some_Addon_DataWriter_Node`, три класса: `XenForo_DataWriter_Node`, `XenForo_DataWriter_Page` и `XenForo_DataWriter_Forum` и это не вызовет ошибки даже если в коде все 3 класса будут одновременно использоваться в одном вызове.

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->addExtenders(
        'datawriter' => array(
            'XenForo_DataWriter_Node' => array(
                'Some_Addon_DataWriter_Node'
            ),
            'XenForo_DataWriter_Page' => array(
                'Some_Addon_DataWriter_Node'
            ),
            'XenForo_DataWriter_Forum' => array(
                'Some_Addon_DataWriter_Node'
            )
        )
	);
}
~~~
**Внимание!** Мультинаследование работает только при описании наследования через `addExtenders`, во всех других случаях повторное декларирование будет вызывать стандартную ошибку `PHP Fatal error: Cannot redeclare class`.
