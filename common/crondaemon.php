<?php

require __DIR__ . '/..//libs/composer/vendor/autoload.php';

$crondaemon = new \BUSaragon\common\controller();
print_r( $crondaemon->crondaemon() );
