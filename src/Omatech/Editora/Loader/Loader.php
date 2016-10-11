<?php

namespace Omatech\Editora\Loader;

use \Doctrine\DBAL\DriverManager;

class Loader {

		public $debug_messages = '';
		private static $conn;

		public function __construct($conn) {
				if (is_array($conn)) {
						$config = new \Doctrine\DBAL\Configuration();
						//..
						$connectionParams = array(
							'dbname' => $conn['dbname'],
							'user' => $conn['dbuser'],
							'password' => $conn['dbpass'],
							'host' => $conn['dbhost'],
							'driver' => 'pdo_mysql',
							'charset' => 'utf8'
						);
						$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
						$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
				}
				self::$conn = $conn;
		}

		public function delete_instance($inst_id) {

				$sql_values = 'DELETE
				FROM omp_values 
				WHERE inst_id = "' . $inst_id . '"';
				self::$conn->executeQuery($sql_values);
				//$ret_values = mysql_query($sql_values);

				$sql_inst_child = 'DELETE
				FROM omp_relation_instances 
				WHERE child_inst_id = "' . $inst_id . '"';
				self::$conn->executeQuery($sql_inst_child);
				//$ret_inst_child = mysql_query($sql_inst_child);

				$sql_inst_parent = 'DELETE
				FROM omp_relation_instances 
				WHERE parent_inst_id = "' . $inst_id . '"';
				self::$conn->executeQuery($sql_inst_parent);
				//$ret_inst_parent = mysql_query($sql_inst_parent);

				$sql_inst = 'DELETE 
				FROM omp_instances 
				WHERE id = "' . $inst_id . '"';
				self::$conn->executeQuery($sql_inst);
				//$ret_inst = mysql_query($sql_inst);

				$sql_inst = 'DELETE 
				FROM omp_niceurl 
				WHERE inst_id = "' . $inst_id . '"';
				self::$conn->executeQuery($sql_inst);
				//$ret_inst = mysql_query($sql_inst);

				$sql_inst = 'DELETE 
				FROM omp_instances_cache 
				WHERE inst_id = "' . $inst_id . '"';
				self::$conn->executeQuery($sql_inst);
				//$ret_inst = mysql_query($sql_inst);

				return true;
		}
    		
		public function update_values($inst_id, $values) 
		{
				$results = array();

				foreach ($values as $key => $value) 
        {
						$info = self::get_attr_info($key);
						if (empty($info)) 
						{
								echo("No existeix l'attribut: $key\n");
								return false;
						}
            else 
            {
              print_r($info);
            }
        }
    }

		
		
		protected function get_attr_info($key)
		{
				if (is_numeric($key))
				{
						$key=self::$conn->quote($key);
						$sql = "SELECT * FROM omp_attributes where id=$key";
				}
				else
				{
						$key=self::$conn->quote($key);
						$sql = "SELECT * FROM omp_attributes where name=$key";
				}
				return self::$conn->fetchAssoc($sql);
		}

		protected function get_attr_id($key) 
		{// get attribute id from key or empty string		
				if (is_numeric($key))
						return $key;

				$key=self::$conn->quote($key);
				$sql = "select id from omp_attributes where name=$key";
				$row = self::$conn->fetchAssoc($sql);

				if (isset($row['id']))
						return $row['id'];
				else
						return '';
		}

		public function get_inst_id_from_value($class_tag, $attr_key, $value) 
	  {// retorna -1 si no existeix la instancia d'aquesta class o el id si existeix
				$class_tag = self::$conn->quote($class_tag);
				$value = self::$conn->quote($value);

				$attr_info = self::get_attri_info($attr_key);

				$sql = "SELECT i.id
				FROM omp_instances i
				, omp_classes c
				, omp_values v
				WHERE 
				 i.class_id = c.id
				AND c.tag=$class_tag
				AND v.inst_id = i.id
				AND v.atri_id = ".$attr_info['id']."
				AND v.text_val = $value
				";

				$row = self::$conn->fetchAssoc($sql);

				if ($row) {
						return $row['id'];
				}
				return -1;
		}
		
}
