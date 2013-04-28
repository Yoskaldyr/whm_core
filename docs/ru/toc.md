WHM_Core - Ядро для разработки
==============================

Основные проблемы при разработке дополнений под XenForo
-------------------------------------------------------
При разработке и поддержке дополнений для XenForo основные проблемы с которыми сталкивается разработчик:

 1. Невозможность расширения базовых классов XenForo, особенно статических хелперов.
 2. Невозможность расширения одним динамическим классом нескольких классов XenForo из-за невозможности повторного декларирования 1 класса
 3. Трудность пробрасывания входных данных из контроллера в датарайтер при расширении функционала основных типов данных (узлы, сообщения, темы)
 4. Неудобство в разработке когда надо изменить какой-либо обработчик в админке, вместо простой правки кода, следовательно любое добавление обработчика требует обязательного обновления хака через админку
 5. Невозможность простого использования репозитариев для каждого отдельного дополнения (все находятся в одном дереве, что не дает нормально использовать функционал IDE по работе с системами контроля версий)



Все эти проблемы и неудобства позволяет решить ядро

Содержание
----------
#### 1. [Автозагрузчик WHM_Core_Autoloader. Режим разработки и production.](autoloader.md)
#### 2. [Класс WHM_Core_Listener. Расширенная работа с событиями.](listeners.md)
#### 3. [Класс WHM_Core_Application. Реестр. Проброс данных в DataWriter.](application.md)
#### 4. [Упрощение расширения часто используемых типов данных - узлы, темы, сообщения.](nodethreadpost.md)

Дополнительно
-------------
#### [Пример настройки nginx-а.](nginx.md)