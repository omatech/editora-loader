<?php
//à

if (empty($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST']='prod';

if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'devel')
{
	$_SERVER['HTTP_HOST']='devel';	
}

if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'preprod')
{
	$_SERVER['HTTP_HOST']='preprod';	
}


if ((strpos($_SERVER['HTTP_HOST'],'oma.lan')!==false || strpos($_SERVER['HTTP_HOST'],'devel')!==false)) 
{// entorn de devel
	echo "ENTORN DE DEVEL!!!!\n";
	define("dbhost","localhost");
	define("dbuser","");
	define("dbpass",'');
	define("dbname","");
}
elseif ((strpos($_SERVER['HTTP_HOST'],'.omatech.com')!==false || strpos($_SERVER['HTTP_HOST'],'preprod')!==false)) 
{// entorn de preprod al mateix server que prod
	echo "ENTORN DE DEVEL (PREPROD)!!!!\n";
	define("dbhost","localhost");
	define("dbuser","");
	define("dbpass",'');
	define("dbname","");             
}
else
{// PROD
	define("dbhost","localhost");
	define("dbuser","");
	define("dbpass",'');
	define("dbname","");
}

$config_array=[
	'dbhost'=>dbhost
	, 'dbuser'=>dbuser
	, 'dbpass'=>dbpass
	, 'dbname'=>dbname
];