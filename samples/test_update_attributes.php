<?php

$autoload_location=__DIR__.'/../vendor/autoload.php';
if (!is_file($autoload_location)) $autoload_location='../../../autoload.php';	

require_once $autoload_location;
require_once __DIR__.'/../conf/config.php';

use Omatech\Editora\Loader\Loader;

//print_r($config_array);die;

$debug=true;

//$multigeocoder=null;
//Use only if there're map attributes
$multigeocoder=new Omatech\Editora\Loader\MultiGeoCoder($available_hosts);

$loader=new Loader($config_array, 'c:/apons/', '/uploads/', $multigeocoder, $debug);


$inst_id=308;
$values=['title_ca'=>'Àptima Centre Clínic MútuaTerrassa333'
	, 'th_center'=>'dni_apons_2.JPG'
	, 'map'=>'Pl. del Dr. Robert, 5. 08221 Terrassa'
	, 'phone'=>'93 736 70 20'
	, 'poblacion'=>'Terrassa'
	, 'address'=>'Pl. del Dr. Robert, 5. 08221 Terrassa'
];
$loader->update_values($inst_id, $values);

$inst_id=$loader->insert_instance(270, 'Centre Aptima TEST', $values);
echo "Instancia creada $inst_id \n";

//$loader->delete_instance(996);
//$loader->delete_instance(997);




