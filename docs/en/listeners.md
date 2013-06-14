Class WHM_Core_Listener. Extended work with events
==================================================
One of the bottlenecks of XenForo during active development is the need to setup each event in admin panel. One can work with events straightforward using `XenForo_CodeEvent`, but it has only basic functional, only static methods and not very handy API. That's why there was created a class for extended work with events `WHM_Core_Listener` which extends  `XenForo_CodeEvent`. It allowed to access all the original event listeners of XenForo.

Basic work with events
----------------------
For convenience of setup all the event listeners there was created a special event `init_listeners` which runs from autoloader before all the other events including `init_dependencies`.

Event callbacks signature:
`public static function initListeners(WHM_Core_Listener $events)`

**Attention!** This events starts before the full initialization of the application, for example, before options. Then, it's recommended to use only `WHM_Core_Listener` methods.

Добавление событий
------------------
Внутри события в объекте `$events` доступен оригинальный массив (данные об обработчиках из админки) обработчиков событий `$listeners`, где ключи названия событий, а значения массивы колбеков-обработчиков этих событий.
Пример добавления нескольких событий в конец очереди:

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->listeners['init_application'][] = array('Some_Class_Listener', 'initApplication');
	$events->listeners['template_hook'][] = array('Some_Class_Listener', 'templateHook');
}
~~~
Также обработчки можно добавлять используя методы:
`prependListener($event, $callback)` - добавить обработчик в начало очереди события
`appendListener($event, $callback)` - добавить обработчик в конец очереди события
`addListeners(array $listeners, $prepend = false)` - добавить набор обработчиков для событий в начало или конец очереди, т.е. попросту объединение 2-х массивов обработчиков в нужном порядке.

Управление наследованием
------------------------
В XenForo очень удобно реализовано расширение классов через динамическое наследование используя `load_class_*` события, но описание их приходится делать в нескольких местах, также приходится писать много однотипного кода (обработчики событий `load_class_*` практически всегда одинаковые только отличаются названием классов), хотя в 99% случаев надо просто расширить какой-то один класс другим.
Для облегчения этого и чтобы все можно было прописать в 1 месте без необходимости делать обработчики для `load_class_*` событий добавлены специальные методы для описания наследования. Т.е. достаточно прописать в событии `init_listeners` цепочку наследования и обработчики событий для динамического наследования создадутся автоматом.

Для этого используется метод
`public function addExtenders($extenders, $prepend = false)`

Пример описания расширения `XenForo_DataWriter_Page` с помощью `Some_Addon_DataWriter_Node` и `Some_Addon_DataWriter_Page`, а также
 расширения `XenForo_ViewPublic_Page_View` с помощью `Some_Addon_ViewPublic_Page_View`:

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
**Внимание!** Обработчики событий, автоматически сгенерированные для событий `load_class_*` на основе описания `addExtenders` запускаются **в самую первую очередь** до обработчиков прописанных как в админке XenForo, так и обработчиков прописанных через `listeners` методы.

Наследование базовых классов XenForo
------------------------------------
Используя собственный автозагрузчик файлов можно решить проблему наследования нерасширяемых классов XenForo.
Например, чтобы динамически расширить класс который вызывается по прямому имени - тот же, `XenForo_Link`, можно сделать физическую копию класса, но с измененным именем, например `XFProxy_XenForo_Link`, потом от него динамически отнаследовать цепочку как любой динамический класс в XenForo (задекларировать через `eval`), после чего опять же через `eval` задекларировать класс `XenForo_Link`.

Т.е. вместо инклуда файла с `XenForo_Link` получаем цепочку:

1. Создание копии файла с подменой оригинального имени класса на класс с префиксом, т.е. `XFProxy_XenForo_Link`.
2. Стандартное динамическое декларирование с наследованием цепочки `XFCP_*` классов  через `eval` с инклудами файлов аддонов.
3. Декларирование оригинального имени класса `XenForo_Link` через `eval`, который расширяет последний класс в цепочке динамического наследования из предыдущего п.2.

Для удобной работы с таким прокси наследованием добавлено новое событие `load_class_proxy_class` по интерфейсу аналогичное остальным `load_class_*` событиям, только с некоторыми особенностями связанными со спецификой такого наследования.

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
