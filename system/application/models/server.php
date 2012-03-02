<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pavel
 * Date: 24.02.12
 * Time: 10:37
 *
 */
class ServerModel implements IModel{
	protected $data = array();

	public function get_server_by_id($id){
		$this->data=array();
		$serversDB = new \DB\MySQL('servers');
		$server = $serversDB->select()
			->where('id = ?', $id)
			->first();
		if($server){
			foreach($server as $rowName => $value){
				$this->data[$rowName] = $value;
			}
			return $this;
		}
		else return NULL;
	}
	public function get_param($paramType){
		if(isset($this->data[$paramType])){
			return $this->data[$paramType];
		}
		else return NULL;
	}
	public function __get($name){
		return $this->get_param($name);
	}

	static public function get_servers($count=1){
		$result = array();
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$servers = $redis->zrange('servers','0', $count+1)->exec();
		$servers = array_slice($servers,0,$count);
		foreach($servers as $value){
			$server = new ServerModel();
			$server->get_server_by_id($value);
			$result[] = $server;
		}
		return $result;
	}
	public function add_datasize($size){
		$this->data['nonfreesize']+=$size;
		return $this;
	}
	public function refresh(){
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$workload = $this->data['disksize']/100;
		$workload = $this->data['datasize']/$workload;
		$redis->zadd('servers',$workload,$this->data['id']);
	return $this;
	}

	function new_inst(){
		return new self;
	}
}
