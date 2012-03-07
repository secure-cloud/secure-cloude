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
		if(strtolower($server->system) == "windows"){
			$path = $server->rootdir.$userId.'\\'.str_replace('/','\\',$userPath);
			$separator = substr($path,-1);
			if($separator != '\\')
				$path.="\\";
			return $path;
		}else{
			$path = $server->rootdir.$userId.'/'.str_replace('\\','/',$userPath);
			$separator = substr($path,-1);
			if($separator != '/')
				$path.="/";
			return $path;
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
		if($userPath == "")
			$objects = $redis->smembers($userId.'/')->exec();
		else
			$objects = $redis->smembers($userId.'/'.md5($userPath))->exec();
		foreach($objects as $value){
			$separator = substr($value,-1);
			if($separator == '\\' || $separator == '/'){
				//$value = substr($value,0,strlen($value)-1); //Эта строчка на случай, если надо, чтоб директории приходили без слешей
				$result[$value]=array('type'=>'dir');
			}
			else{
				$file = new FileModel();
				$file->get_unic($userPath,$value,$userId);
				$result[$value]=array('type'=>'file','time'=>$file->time,'size'=>$file->file_size);
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
		}
		else{
			$pathArray = explode('\\',$userPath);
		}
		array_unshift($pathArray,'');
		foreach($pathArray as $key => $value){
				$path = '';
				$path .=$value.'/';
				$next = $key+1;
				if($next<count($pathArray)){
					if($pathArray[$next]!='')
						if($path == '')
							$redis->sadd($userId.'/'.'', $pathArray[$next].'/')->exec();
						else
							$redis->sadd($userId.'/'.md5($path), $pathArray[$next].'/')->exec();
				}
		}
	}

	public function dir_remove($userId,$userPath,$dirName){
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$redis->srem($userId.'/'.md5($userPath), $dirName)->exec();
	}

	public static function make_directory($ftp_stream, $dir){
		if(!self::safe_is_dir($ftp_stream, $dir)){
			if (!self::safe_mkdir($ftp_stream, $dir)) {
				self::make_directory($ftp_stream, dirname($dir));
				self::safe_mkdir($ftp_stream, $dir);
			}
			return true;
		}
		else{
			return true;
		}

		/*if (self::safe_is_dir($ftp_stream, $dir) || self::safe_mkdir($ftp_stream, $dir))
			return true;
		if (self::make_directory($ftp_stream, dirname($dir)))
			return false;
		return self::safe_mkdir($ftp_stream, $dir);
		*/
		/**try{
			if (\DirectoryModel::ftp_is_dir($ftp_stream, $dir) || @ftp_mkdir($ftp_stream, $dir))
				return true;
		}
		catch(Exception $e){}
			if (\DirectoryModel::make_directory($ftp_stream, dirname($dir)))
				return false;
			return @ftp_mkdir($ftp_stream, $dir);
		*/
	}

	public static  function safe_mkdir($ftp_stream, $dir) {
		$result = false;
		try {
			$result = ftp_mkdir($ftp_stream, $dir);
		} catch (Exception $e) {
			return false;
		}
		return $result;
	}

	public static function safe_is_dir($ftp_stream, $dir) {
		try {
			ftp_chdir($ftp_stream, $dir);
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	private static function ftp_is_dir($ftp_stream, $dir){
		$original_directory = ftp_pwd($ftp_stream);
		try{
			if(!@ftp_chdir($ftp_stream, $dir ))
				throw new Exception("Directory doesn't exist");
			else
				@ftp_chdir( $ftp_stream, $original_directory );
				return true;
		}
		catch(Exception $e){
			return false;
		}
	}

	function new_inst(){
		return new self;
	}

}
