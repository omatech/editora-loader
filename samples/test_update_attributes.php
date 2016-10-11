<?php

$autoload_location=__DIR__.'/../vendor/autoload.php';
if (!is_file($autoload_location)) $autoload_location='../../../autoload.php';	

require_once $autoload_location;
require_once __DIR__.'/../conf/config.php';

use Omatech\Editora\Loader;

print_r($config_array);die;

$loader=new Loader($config_array);



$loader->update_values($inst_id, $values);
