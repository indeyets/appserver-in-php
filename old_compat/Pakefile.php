<?php

pake_import('pear');

pake_desc('package and install current snapshot');
pake_task('install', 'pear_package');

function run_install()
{
    pake_superuser_sh('pear install -f AppServer-0.5.0.tgz');
}
