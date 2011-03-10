sfErrorNotifierPlugin plugin
============================

The `sfErrorNotifierPlugin` sends automatic email notifications when application errors (exceptions) occur and are not caught. It's easy configuration allows you to set which environments to enable for the notifications.

Authors
-------
 
* Daniele Occhipinti <danieleocchipinti.it@gmail.com>
* Olmo Maldonado <ibolmo@gmail.com>

Installation
------------

	git clone https://github.com/ibolmo/sfErrorNotifierPlugin.git ./plugins/sfErrorNotifierPlugin
	git submodule update --init

Configure
---------

Take a look at all the configure options in:

	./plugins/sfErrorNotifierPlugin/config/app.yml

Update your application(s) `app.yml`. For example:

	# apps/frontend/config/app.yml
	all:
	  sf_error_notifier_plugin:
	    email_to: email@domain.tld
	dev:
	  sf_error_notifier_plugin:
	    enabled: false
			
Modify Email Template
---------------------

`sfErrorNotifierPlugin` uses a partial to generate the emails. Since `2.0` you'll able to create your own module and partial to override the default template.

	mkdir -p ./apps/frontend/modules/sfErrorNotifier/templates
	$EDITOR ./apps/frontend/modules/sfErrorNotifier/templates/_notify.php


Changelog
---------

### 2011-03-09 | 2.0
* New and improved. Uses Swift_Mailer as mailer. Partial for email body.

### 2010-10-13 | 1.6
* In the configuration, we have added a key ('enabled') that you need to set (we wanted to enable the plugin in a more standard way)
* New option report404 to specifically target 404 errors
* fixed a bug: when page_not_found event was fired a PHP error was fired

### 2010-08-05 | 1.5
* works with Symfony 1.4 (thanks to luctus)
* it should alert also for PHP fatal errors (such as memory exausted) (thanks to maksim_ka)
* fixed a bug: under certain conditions, the $user variable wasn't an object and that was triggering a Fatal Error
* you can specify a mailer function (or method) to use rather than the native PHP mail method
* the notification now also contains some further details for the request
* the notification now also contains the name of the application
* tested on Symfony 1.2, 1.3 and 1.4

### 2010-03-23 | 1.2
* Fixed a bug in the documentation
* Made the code a bit more robust

### 2009-10-28 | 1.1
* Added nice HTML format for the email (thanks to Gustavo Garcia)
* Added user information to the email (thanks to Gustavo Garcia)
* Added the possibility to also trigger the notification email explicitly via a standard method call 

### 2009-04-30 | 1.0.2
* Improved the documentation
* Made the email subject more explanatory

### 2009-04-27 | 1.0.1
* Improved the documentation

### 2009-04-26 | 1.0.0
* converted the plugin to 1.1
