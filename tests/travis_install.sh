#!/bin/bash

# Install Pake
# FIXME: This should happen eventually with Composer
pyrus channel-discover pear.indeyets.ru
pyrus install -f http://pear.indeyets.ru/get/pake-1.6.3.tgz

# Install with Composer
wget http://getcomposer.org/composer.phar 
php composer.phar install
