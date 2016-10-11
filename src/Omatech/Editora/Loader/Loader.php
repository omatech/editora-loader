<?php

namespace Omatech\Editora\Loader;

use \Doctrine\DBAL\DriverManager;

class Loader {

		public $debug_messages = '';
		public static $file_base = '';
		public static $url_base = '';
		public static $geocoder;
		private static $conn;

		public function __construct($conn, $file_base, $url_base, $geocoder=null, $debug=false) {
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
						if ($debug)
						{
						  $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
						}
						$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
				}
				self::$file_base=$file_base;
				self::$url_base=$url_base;
				self::$conn = $conn;
				self::$geocoder = $geocoder;
		}

		public function delete_instance($inst_id) {

				self::$conn->executeQuery('start transaction');
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
				self::$conn->executeQuery('commit');

				return true;
		}
		
		public function insert_instance ($class_id, $nom_intern, $values, $status='O', $publishing_begins=null, $publishing_ends=null)
		{
				self::$conn->executeQuery('start transaction');
				$status=self::$conn->quote($status);
				
				if ($publishing_begins==null)
				{
						$publishing_begins='now()';
				}
				else
				{
						if (is_int($publishing_begins))
						{// es un timestamp
								$publishing_begins=self::$conn->quote(date("Y-m-d H:m:s", $publishing_begins));
						}
						else
						{// confiem que esta en el format correcte
								$publishing_begins=self::$conn->quote($publishing_begins);								
						}
				}
				
				if ($publishing_ends==null)
				{
						$publishing_ends='null';
				}
				else
				{
						if (is_int($publishing_ends))
						{// es un timestamp
								$publishing_ends=self::$conn->quote(date("Y-m-d H:m:s", $publishing_ends));
						}
						else
						{// confiem que esta en el format correcte
								$publishing_ends=self::$conn->quote($publishing_ends);								
						}
				}

				$sql="insert into omp_instances (class_id, key_fields, status, publishing_begins, publishing_ends, creation_date, update_date)
						values ($class_id, ".self::$conn->quote($nom_intern).", $status, $publishing_begins, $publishing_ends, now(), now())";
				self::$conn->executeQuery($sql);
				$inst_id=self::$conn->lastInsertId();
				$this->update_values($inst_id, ['nom_intern'=>$nom_intern]);
				$this->update_values($inst_id, $values);
				self::$conn->executeQuery('commit');
				return $inst_id;
		}

		public function update_values($inst_id, $values) 
		{
				$results = array();
				self::$conn->executeQuery('start transaction');
				foreach ($values as $key => $value) 
        {
						$attr_info = self::get_attr_info($key);
						if (empty($attr_info)) 
						{
								self::$conn->executeQuery('rollback');
								echo("No existeix l'attribut: $key\n");
								return false;
						}
            else 
            {// podem continuar, existeix l'atribut
							//print_r($attr_info);
							if ($attr_info['type']=='I')
							{// image
								$this->insert_update_image_val($inst_id, $attr_info['id'], $value);																		
							}
							elseif ($attr_info['type']=='D')
							{// date
								$this->insert_update_date_val($inst_id, $attr_info['id'], $value);																		
							}
							elseif ($attr_info['type']=='N')
							{// number
								$this->insert_update_num_val($inst_id, $attr_info['id'], $value);									
							}
							elseif ($attr_info['type']=='L')
							{// lookup
								$this->insert_update_lookup_val($inst_id, $attr_info['id'], $attr_info['lookup_id'], $value);
							}
							elseif ($attr_info['type']=='M')
							{// Maps
								$this->insert_update_geopos_val($inst_id, $attr_info['id'], $value);									
							}
							else
							{
								$this->insert_update_text_val($inst_id, $attr_info['id'], $value);
							}
            }
        }
				$sql="update omp_instances set update_date=now() where id=$inst_id";
				self::$conn->executeQuery($sql);
				self::$conn->executeQuery('commit');

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

		
		
		protected function get_lookup_value_id($lookup_id, $value) 
		{
				$value=self::$conn->quote($value);

				$sql = "select lv.id
				from omp_lookups_values lv
				where lv.lookup_id=$lookup_id
				and (lv.value=$value or
						lv.caption_ca=$value or
						lv.caption_es=$value or 
						lv.caption_en=$value
				)
				";

				return self::$conn->fetchAssoc($sql);
		}
		
		
		public function exist_value ($inst_id, $atri_id)
		{
				$sql="select count(*) num
				from omp_values v
				where v.inst_id=$inst_id
				and v.atri_id=$atri_id
				";
				$row=self::$conn->fetchAssoc($sql);
				return ($row['num']==1);
		}
		
		
		public function insert_update_geopos_val ($inst_id, $atri_id, $value)
		{
				$geoinfo=self::$geocoder->geocode($value);
				//print_r($geoinfo);die;
				$value=self::$conn->quote($geoinfo['lat'].':'.$geoinfo['lng'].'@'.$value);
				if ($this->exist_value($inst_id, $atri_id))
				{// update
						$sql="update omp_values v
						set text_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						";
				}
				else
				{// insert
						$sql="insert into omp_values (inst_id, atri_id, text_val)
						values ($inst_id, $atri_id, $value)";
				}
				self::$conn->executeQuery($sql);
		}

		public function insert_update_text_val ($inst_id, $atri_id, $value)
		{
				$value=self::$conn->quote($value);
				if ($this->exist_value($inst_id, $atri_id))
				{// update
						$sql="update omp_values v
						set text_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						";
				}
				else
				{// insert
						$sql="insert into omp_values (inst_id, atri_id, text_val)
						values ($inst_id, $atri_id, $value)";
				}
				self::$conn->executeQuery($sql);
		}
		
		public function insert_update_lookup_val ($inst_id, $atri_id, $lookup_id, $value)
		{
				$lv_id = -1;
				$lv_id = $this->get_lookup_value_id($lookup_id, $value);

				if ($lv_id == -1) 
				{// error al obtenir el value del lookup
						echo "Value $value not found for atri_id=$atri_id\n aborting!\n";
						die;						
				}
				
				if ($this->exist_value($inst_id, $atri_id))
				{// update
						$sql="update omp_values v
						set num_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						";
				}
				else
				{// insert
						$sql="insert into omp_values (inst_id, atri_id, num_val)
						values ($inst_id, $atri_id, $value)";
				}
				self::$conn->executeQuery($sql);								
		}

		
		
		public function insert_update_num_val ($inst_id, $atri_id, $value)
		{
				$value=self::$conn->quote($value);
				if ($this->exist_value($inst_id, $atri_id))
				{// update
						$sql="update omp_values v
						set num_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						";
				}
				else
				{// insert
						$sql="insert into omp_values (inst_id, atri_id, num_val)
						values ($inst_id, $atri_id, $value)";
				}
				self::$conn->executeQuery($sql);								
		}
    		
		public function insert_update_date_val ($inst_id, $atri_id, $value)
		{
				$value=self::$conn->quote($value);
				if ($this->exist_value($inst_id, $atri_id))
				{// update
						$sql="update omp_values v
						set date_val=$value
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						";
				}
				else
				{// insert
						$sql="insert into omp_values (inst_id, atri_id, date_val)
						values ($inst_id, $atri_id, $value)";
				}
				self::$conn->executeQuery($sql);				
		}
		
		
		
		public function insert_update_image_val ($inst_id, $atri_id, $value)
		{
				if (!file_exists(self::$file_base.$value)) die("No existe el fichero ".self::$file_base.$value.", error!\n");
				
				list($width, $height) = getimagesize(self::$file_base.$value);
				$value=self::$conn->quote(self::$url_base.$value);
				if ($this->exist_value($inst_id, $atri_id))
				{// update
						$sql="update omp_values v
						set text_val=$value
						, img_info='$width.$height'
						where v.inst_id=$inst_id
						and v.atri_id=$atri_id					  
						";
				}
				else
				{// insert
						$sql="insert into omp_values (inst_id, atri_id, text_val, img_info)
						values ($inst_id, $atri_id, $value, '$width.$height')";
				}
				self::$conn->executeQuery($sql);				
		}
		
		
}
