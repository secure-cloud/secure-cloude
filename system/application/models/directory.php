<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pavel
 * Date: 24.02.12
 * Time: 10:37
 *
 */
class DirectoryModel implements IModel{


 	/**
	  * Преобразовывает пользовательский путь к файлу в серверный
	  * @static
	  * @param $userId
	  * @param $userPath
	  * @param $server
	  * @return string
	  */
	static function make_server_path($userId, $userPath, &$server){
		if(strtolower($server->data['system']) == "windows"){
			return $server->data['rootdir'].'/'.$userId.'/'.str_replace('\\','/',$userPath);
		}else{
			return $server->data['rootdir'].'\\'.$userId.'\\'.str_replace('/','\\',$userPath);
		}

	}
	/**
	 * Получает содержимое директории в ввиде массива array(name=>type)
	 * где type = dir || file
	 * @param $userId
	 * @param $userPath
	 * @return array
	 */
	public function get_content($userId, $userPath){
		$result = array();
		$redis = new \Cache\Redis('81.17.140.102','6379');
		if($userPath === '')
			$objects = $redis->smembers($userId.'/')->exec();
		else
			$objects = $redis->smembers($userId.'/'.md5($userPath))->exec();
		foreach($objects as $value){
			$value = base64_decode($value);
			$separator = substr($value,-1);
			if($separator == '\\' || $separator == '/'){
				$value = substr($value,0,strlen($value)-1);
				$result[$value]='dir';
			}
			else{
				$result[$value]='file';
			}
		}
		return $result;
	}
	/**
	 * @param $userId
	 * @param $userPath
	 */
	static public function save_path($userId, $userPath){
		$isDir = false;
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$separator = substr($userPath, -1);
		if($separator == '\\' || $separator == '/'){
			$isDir = true;
		}
		if(preg_match('|/|', $userPath) > 0){
			$pathArray = explode('/',$userPath);
			$path = '/';
			$dirSep = '/';
		}
		else{
			$pathArray = explode('\\',$userPath);
			$path = '\\';
			$dirSep = '\\';
		}
		foreach($pathArray as $key => $value){
			if($value != ''){
				$path .=$value.'/';
				if(isset($pathArray[$key+1]) && $pathArray[$key+1]!='')
					$redis->sadd($userId.'/'.md5($path).'/', $path.$pathArray[$key+1].'/');
			}
		}
	}

	function new_inst(){
		return new self;
	}

}
