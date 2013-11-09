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
In the object `$events` there is an array (info about event listeners from admin panel) of event listeners `$listeners` (array `[event_name][event_hint] => callback_array`). And `'_'` as default event hint (empty hint).
Example of adding some events in the end queue:

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->listeners['init_application']['_'][] = array('Some_Class_Listener', 'initApplication');
	$events->listeners['template_hook']['_'][] = array('Some_Class_Listener', 'templateHook');
}
~~~
Also listeners can be added using methods:
`prependListener($event, $callback, $hint)` - add listener to the beginning of queue for event
`appendListener($event, $callback, $hint)` - add listener to the end of queue for event
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
		array(
            'XenForo_DataWriter_Page' => array(`Some_Addon_DataWriter_Node`, `Some_Addon_DataWriter_Page`),
            'XenForo_ViewPublic_Page_View' => 'Some_Addon_ViewPublic_Page_View'
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

### Features of realisation
Copy is created at first initialisation of proxy class. There is a modification time check for the file. So this will not cause problems with performance on production or problems with often update on development.

This dynamic extention is inside of autoloader, then checking for each loaded class the ability of its extention with `load_class_proxy_class` is bad for performance if it used without event hint, and disabled.

Using method `addProxyExtenders` for event `load_class_proxy_class` classes hints are added automatically in event listener:

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->addProxyExtenders(
		array(
	        'Some_XenForo_Class' => 'Some_AddOn_Class'
		)
	);
}
~~~

**Atention!** Extending abstract classes you have to point array consisting of name of the class and second field 'abstract':

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->addProxyExtenders(
		array(
			'XenForo_DataWriter' => array(
				array('AddOn_DataWriter_Abstract', 'abstract')
			)
		)
	);
}
~~~

**Atention!** Remember that using such dynamic replacement body of static methods belongs to another class.
For example if we extend `XenForo_Link` (good example since it have only static methods), then body of all methods will be placed in `XFProxy_XenForo_Link`, not in `XenForo_Link`. And so you have to call such static methods by the full path, for example `XFProxy_XenForo_Link::buildAdminLink` or `parent::buildAdminLink`, but not `XenForo_Link::buildAdminLink`, since class with name `XenForo_Link` is not loaded yet and not `self::buildAdminLink` which can give unexpected results due to features of visibility of `self` for static methods of not instantiated classes.

Multiextention or extention of several classes of XenForo by one class of addon.
--------------------------------------------------------------------------------
Since it is imposible to redeclare of one class, it is impossible to use 1 class to extend similar classes of XenForo. Example: datawriters of nodes or bbcode handlers. For example, in order to add stuff to all node types one have to create one class for each node type even if they have same content.
But using method of auto creation of copy at redeclaration(method similar to the one for extention of basic classes described before) one may get multiextention with proxy classes without copying of code.

For example, one may extend by one class `Some_Addon_DataWriter_Node`, three classes: `XenForo_DataWriter_Node`, `XenForo_DataWriter_Page` and `XenForo_DataWriter_Forum` and this will not cause errors even if all these theree classes will be used same timе.

~~~php
<?php
public static function initListeners(WHM_Core_Listener $events) {
	$events->addExtenders(
		array(
            'XenForo_DataWriter_Node'  => 'Some_Addon_DataWriter_Node',
            'XenForo_DataWriter_Page'  => 'Some_Addon_DataWriter_Node',
            'XenForo_DataWriter_Forum' => 'Some_Addon_DataWriter_Node'
        )
	);
}
~~~
**Atention!** Multiextention works only by declaration with `addExtenders`. In other declaration it will cause standard error `PHP Fatal error: Cannot redeclare class`.
