WHM_Core - Development core
==============================

Common difficulties you'll face while doing addon development in XenForo
-------------------------------------------------------
Developers often face these problemns during the development process in XenForo:

 1. It's not possible to extend basic classes in XenForo, especially static helpers. Невозможность расширения базовых классов XenForo, особенно статических хелперов.
 2. It's not possible to extend multiple XenForo classes with a single third party class due to limitation of a single class repetitious declaring.
 3. Pushing input data from controller to data writer is really hard to do when you're dealing with an extension of common data types (nodes, messages, threads).
 4. Changing or adding any handler requires configuring through admin control panel.
 5. You can't use versioning or IDE in your addon because all your scripts are located inside XenForo libraries tree.



This core solves the issues stated above.

Contents
----------
#### 1. [Autoloader WHM_Core_Autoloader. Development and production modes.](autoloader.md)
#### 2. [WHM_Core_Listener class. Extended event handling.](listeners.md)
#### 3. [WHM_Core_Application class. Register. Pushing data to DataWriter.](application.md)
#### 4. [Simplified extension of most commonly used data types: nodes, threads, messages.](nodethreadpost.md)

Additional
-------------
#### [nginx settings example.](nginx.md)
