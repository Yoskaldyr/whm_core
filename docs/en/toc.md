WHM_Core - Development core
===========================

Common difficulties you'll face while doing addon development in XenForo
------------------------------------------------------------------------
Developers often face these problems during the development process in XenForo:

 1. It's not possible to extend basic classes in XenForo, especially static helpers.
 2. It's not possible to extend multiple XenForo classes with a single third party class due to limitation of a single class repetitious declaring (**Cannot redeclare class** error).
 3. Pushing input data from controller to data writer is really hard to do when you're dealing with an extension of common data types (nodes, messages, threads).
 4. Changing or adding any event listener requires configuring through admin control panel.
 5. You can't use VCS (Version control systems) for your addons because all your scripts are located inside XenForo libraries tree.



This core solves the issues stated above.

Contents
--------
#### 1. [Autoloader WHM_Core_Autoloader. Development and production modes.](autoloader.md) (Available only in russian)
#### 2. [WHM_Core_Listener class. Extended event handling.](listeners.md) (Available only in russian)
#### 3. [WHM_Core_Application class. Register. Pushing data to DataWriter.](application.md) (Available only in russian)
#### 4. [Simplified extension of most commonly used data types: nodes, threads, messages.](nodethreadpost.md) (Available only in russian)

Additional
----------
#### [nginx settings example.](nginx.md)
