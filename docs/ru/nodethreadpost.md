Упрощение расширения часто используемых типов данных - узлы, темы, сообщения
============================================================================

Большое количество аддонов для XenForo - это аддоны, расширяющие основные форумные типы данных, т.е. узлы, темы и сообщения. Поэтому хоть пробрасывать данные из контроллера в датарайтер и можно, но это приводит к дублированию однотипного кода в каждом из хаков. Поэтому была сделана возможность однотипного описания расширения через конфиг.
Для этого достаточно в событии `init_application` записать в реестр описание входных данных, которые будут поступать в момент сохранения, записать с ключем `WHM_Core_Application::INPUT_FIELDS` и описание таблиц и их полей с ключем `WHM_Core_Application::DW_FIELDS`, которое аналогично описанию структуры в `_getFields`  датарайтеров.
В качестве ключа класса используются названия соответствующих классов датарайтеров:
`XenForo_DataWriter_Node` - для всех типов узлов,
`XenForo_DataWriter_Forum` - только для форумов (аналогично для страниц и категорий),
`XenForo_DataWriter_Discussion_Thread` - для тем,
`XenForo_DataWriter_DiscussionMessage_Post` - для сообщений в темах.

Почти всегда, все проверки входных данных спокойно можно делать, через валидаторы датарайтеров, поэтому нет смысла делать дополнительные проверки в контроллере.

Пример
------
Например, надо расширить все типы узлов, добавив строковое поле `addon_data`, и сообщения добавив целочисленное поле `addon_counter`, с  валидатором `My_Addon_Class::addonFieldValidator`.

~~~php
<?php
public static function initApplication(WHM_Core_Application $app)
{
	//Описание входных данных для узлов
	$app->set(
		WHM_Core_Application::INPUT_FIELDS,
		'XenForo_DataWriter_Node',
		array(
		     'addon_data' => XenForo_Input::STRING
		)
	);
	//Описание дополнительных полей для узлов
	$app->set(
		WHM_Core_Application::DW_FIELDS,
		'XenForo_DataWriter_Node',
		array(
		     'xf_node' => array(
			     'addon_data' => array('type' => XenForo_DataWriter::TYPE_STRING, 'default' => '')
		     )
		)
	);
	//Описание входных данных для сообщений
	$app->set(
		WHM_Core_Application::INPUT_FIELDS,
		'XenForo_DataWriter_DiscussionMessage_Post',
		array(
		     'addon_counter' => XenForo_Input::UINT
		)
	);
	//Описание дополнительных полей для сообщений
	$app->set(
		WHM_Core_Application::DW_FIELDS,
		'XenForo_DataWriter_DiscussionMessage_Post',
		array(
		     'xf_post' => array(
			     'addon_counter' => array('type' => XenForo_DataWriter::TYPE_UINT, 'default' => 0, 'verification' => array('My_Addon_Class', 'addonFieldValidator')),
		     )
		)
	);
}
~~~
Кроме описания конфига потребуется только класс `My_Addon_Class` с методом валидатора `addonFieldValidator` и все, расширять датарайтеры, модели и контроллеры не надо будет.