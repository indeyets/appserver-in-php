AppServer, a set of components for building fast universal web-apps in PHP
==========================================================================

Web server interface for PHP, inspired by Ruby’s Rack and Python’s WSGI. It 
provides a common API for connecting PHP frameworks and applications to webservers.

The main idea is, that your app, if built for this protocol, will be able to 
preload resources, preconnect to databases and response to requests **really** fast.

PHP 5.3+ is required, as it provides new garbage collector for cyclic references,
which is critical for long-running apps.

Instalation
-----------

You can get latest release using PEAR:

    pear channel-discover pear.indeyets.pp.ru
    pear install indeyets/AppServer


Usage
-----

Take a look at [example](https://github.com/indeyets/appserver-in-php/tree/master/examples/new/).

* MyApp.class.php — application class. "__invoke()" method is the entry point
* aip.yaml — defines that this application should be served both as HTTP and SCGI

run with "aip app path/to/aip.yaml" command


Discuss
-------

Please join [our discussion group](http://groups.google.com/group/aip-php-dev)

There's also [#appserver-in-php](irc://chat.freenode.net#appserver-in-php) IRC-channel on [freenode](http://freenode.net)
