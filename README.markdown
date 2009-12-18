AppServer, a set of components for building fast universal web-apps in PHP
==========================================================================

Web server interface for PHP, inspired by Ruby’s Rack and Python’s WSGI. It 
provides a common API for connecting PHP frameworks and applications to webservers.

The main idea is, that your app, if built for this protocol, will be able to 
preload resources, preconnect to databases and response to requests **really** fast.

PHP 5.3+ is recommended (and required, if you use main branch), as it provides 
new garbage collector for cyclic references, which is critical for long-running 
apps. We also have a special "backported" version for those of you, who are stuck 
with PHP 5.2.

You can get latest release using PEAR:

    pear channel-discover pear.indeyets.pp.ru
    pear install indeyets/AppServer-beta

Or, if you need version backported to php 5.2:

    pear channel-discover pear.indeyets.pp.ru
    pear install indeyets/AppServer_backport52-beta
