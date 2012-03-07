<?php
	/**
	 * Моделька Файла
	 * @author Vishin Pavel
	 *
	 */
class FileModel implements IModel{

	protected $fileData = array();

	public function get_by($paramType, $paramValue){
		$this->fileData=array();
		$fileDB = new \DB\MySQL('files');
		if(!is_scalar($paramType)){
			$whereParams = join(' = ? ', $paramType);
			$whereParams .=' = ?';
			$file = $fileDB->select()
				->where($whereParams , $paramValue)
				->first();
		}
		else{
			$file = $fileDB->select()
				->where($paramType.' = ?', $paramValue)
				->first();
		}
		if($file){
			foreach($file as $rowName => $value){
				$this->fileData[$rowName] = $value;
			}
			return $this;
		}
		else return NULL;
	}
	public function get_unic($path, $name, $userId){
		$this->fileData=array();
		$path = str_replace('\\','/',$path);
		$fileDB = new \DB\MySQL('files');
			$file = $fileDB->select()
				->where('path = ? AND name = ? AND ouner_id = ?', $path, $name, $userId)
				->first();
		if($file){
			foreach($file as $rowName => $value){
				$this->fileData[$rowName] = $value;
			}
			return $this;
		}
		else return NULL;
	}

	/**
	 * Возвращает либо параметр из $fileData либо NULL если параметра нет
	 * @param $paramType
	 * @return null
	 */
	public function get_param($paramType){
		if(isset($this->fileData[$paramType])){
			return $this->fileData[$paramType];
		}
		else return NULL;
	}
	/**
	 * Магический $__get возвращает параметры текущего файла
	 * @param $name
	 * @return null
	 */
	public function __get($name){
		return $this->get_param($name);
	}
	public function get_file_data($filePath,$fileName){
		$fileDB = new \DB\MySQL('files');
		$file = $fileDB ->select()
						->where('path = ? AND name = ?', $filePath, $fileName)
						->first();
		if ($file){
			foreach($file as $rowName => $value){
				$this->editableUser[$rowName] = $value;
			}
			return $this;
		}
		else return NULL;
	}
	/**
	 * Устанавливает параметр текущей файла
	 * @param $paramType
	 * @param $paramValue
	 * @return FileModel
	 */
	public function set_param($paramType, $paramValue){
		if(!is_scalar($paramType) && !is_scalar($paramValue)){
			foreach($paramType as $key => $name){
				$this->fileData[$name] = $paramValue[$key];
			}
		}
		elseif(is_scalar($paramType) && is_scalar($paramValue))
			$this->fileData[$paramType] = $paramValue;
		return $this;
	}
	/**
	 * Сохраняетпараметры текущий файл в базу либо создает  новую запись в базе, если в параметрах текущего файла
	 * не указан ID
	 *
	 * @return int|null
	 */
	public function save_params(){
		$currentFilesDB = new \DB\MySQL('files');
		$update=array();
		if(isset($this->fileData['id'])){
			foreach($this->fileData as $rowName => $value){
				if ($rowName!='id'){
					$update[$rowName]=$value;
				}
			}
			return $currentFilesDB->update($update)
				->where('id = ?', $this->id)
				->exec();
		}
		else{
			foreach($this->fileData as $rowName => $value)
				$update[$rowName]=$value;
			return $currentFilesDB->insert($update)
					->exec();

		}
	}

	/*
	 * Функция извлекает из Redis содержимое указанного каталога.
	 */
	public function get_catalog($userId, $userPath){
		//ToDo добавить в каталог

	}

	/**
	 * Отдает файл пользователю
	 * @param $userId
	 * @param $userPath
	 * @param $fileName
	 * @param $host
	 * @return bool|string
	 * @throws Exception
	 */
	public function send_file($userId, $userPath,$fileName,$host){
		try{
			$this->get_unic($userPath,$fileName,$userId);
			$servers[] = $this->server;
			array_merge($servers,explode(',',$this->bu_server));
			$ftp = false;
			$server = NULL;
			$isLogin = false;
			while(isset($servers[0]) || !$isLogin){
				$serverID = array_shift($servers);
				$server = new ServerModel;
				$server->get_server_by_id($serverID);
				$ftp = ftp_ssl_connect($server->ip);
				$isLogin = ftp_login($ftp,$server->username,$server->password);
			}
				if(!$ftp)
					throw new Exception("Could not connect to web-server while loading file.");
			$localPath = \System\Config::instance()->filetransfer['localtmp'].'/'.$userId.'/'.md5($userPath);
			$serverPath = \DirectoryModel::make_server_path($userId, $userPath,$server);
			$result = ftp_get($ftp,$localPath, $serverPath, FTP_BINARY);
			if($this->post_file($localPath,$host)!==true)
				throw new Exception("Coudn't get file from server.");
			unlink($localPath);
			return true;
		}
		catch(Exception $e){
			unlink($localPath);
			return $e->getMessage();
		}

	}
	/**
	 * Сохраняет в локальное хранилище файл. Одновременно читать можно только из одного файла.
	 * Возвращат массив с первой частью файла (до |\n\n\n\n|) и флаг окончания файла(закончился файл или нет)
	 * Если чтение завершено, то файл удаляется.
	 * @param $userId
	 * @param $userPath
	 * @param $fileName
	 * @return array
	 * @throws Exception
	 */
	public function start_stream($userId, $userPath,$fileName){
		$this->get_unic($userPath,$fileName,$userId);
		$servers[] = $this->server;
		array_merge($servers,explode(',',$this->bu_server));
		$ftp = false;
		$server = NULL;
		$isLogin = false;
		while(isset($servers[0]) || !$isLogin){
			$serverID = array_shift($servers);
			$server = new ServerModel;
			$server->get_server_by_id($serverID);
			$ftp = ftp_connect($server->ip);
			$isLogin = ftp_login($ftp,$server->username,$server->password);
		}
		if(!$ftp)
			throw new Exception("Could not connect to web-server while loading file.");
		$localPath = \System\Config::instance()->filetransfer['localtmp'].'filestream/'.$userId.'/';
		$serverPath = \DirectoryModel::make_server_path($userId, $userPath,$server);
		if(!is_dir($localPath))
			mkdir($localPath, 0777, true);
		$result = ftp_get($ftp,$localPath.'/stream.tmp', $serverPath.$fileName, FTP_BINARY);
		$user = new UserModel();
		$user->get_user_by('id',$userId);
		$user->set_user_param('stream_start','0');
		$user->set_user_param('stream_end', '0');
		if(!is_file($localPath.'/stream.tmp'))
			throw new Exception('EOF',2000);
		$file = fopen($localPath.'/stream.tmp','rb');
		$postString = '';
		$stopRead = false;
		$EOF = false;
		$strEnd = 0;
		while(!$stopRead){
			$status = fgets($file, 256);
			if(!$status){
				$EOF = true;
				break;
			}
			$postString .= $status;
			$strEnd = strpos($postString, "|\n\n\n\n|");
			if($strEnd){
				$stopRead = true;
				$postString = substr($postString,0,$strEnd);
			}
		}
		$strEnd+=6;
		if ($strEnd<6)
			throw new Exception('Bad File');
		$user->set_user_param('stream_end', $strEnd);
		$user->save_user();
		$status = fgets($file, 256);
		if(!$status){
			$EOF = true;
			unlink($file);
		}
		return array('file'=>$postString,'EOF'=>$EOF);
	}
	/**
	 * Читает Следующий кусочек ранее открытого при помощи start_stream файла
	 * Возврашает такой же массив данных, что и start_stream и Так же удаляет файл, если он закончился
	 * @param $userId
	 * @return array
	 * @throws Exception
	 */
	function next_part($userId){
		$localPath = \System\Config::instance()->filetransfer['localtmp'].'filestream/'.$userId.'/';
		$user = new UserModel();
		$user->get_user_by('id',$userId);
		$user->set_user_param('stream_start', $user->stream_end);
		if(!is_file($localPath.'/stream.tmp'))
			throw new Exception('EOF',2000);
		$file = fopen($localPath.'/stream.tmp','rb');
		fseek($file,$user->stream_end);
		if(!$file)
			throw new Exception('Last file alrady read and deleted. Try to reopen it.');
		$postString = '';
		$stopRead = false;
		$EOF = false;
		$strEnd = 0;
		while(!$stopRead){
			$status = fgets($file, 256);
			if(!$status){
				$EOF = true;
				break;
			}
			$postString .= $status;
			$strEnd = strpos($postString, "|\n\n\n\n|");
			if($strEnd){
				$stopRead = true;
				$postString = substr($postString,0,$strEnd);
			}
		}
		$strEnd+=6;
		$strEnd+=$user->stream_end;
		$user->set_user_param('stream_end', $strEnd);
		$user->save_user();
		$status = fgets($file, 256);
		if(!$status){
			$EOF = true;
			unlink($localPath.'/stream.tmp');
		}
		return array('file'=>$postString,'EOF'=>$EOF);

	}
	/**
	 * Отправляет файл методом POST получателю $host
	 * @param $filePath
	 * @param $host
	 * @return bool|Exception
	 * @throws Exception
	 */
	private function post_file($filePath,$host){
		try{
			$file_send=$filePath;
			$boundary = md5(rand(0,32000));
			$filesize = filesize($file_send);

			$data= "--".$boundary."\r\n";
			$data.="Content-Disposition: form-data; md5=". md5_file($filePath) ."\r\n\r\n";
			$data.="значение переменной mass[qwe]\r\n";

			$head_file="--".$boundary."\r\n";
			$head_file.="Content-Disposition: form-data; name=\"var_file\"; filename=\"".$file_send."\"\r\n";
			$head_file.="Content-Type: ".mime_content_type($file_send)."\r\n\r\n";

			$contentlength = strlen($data) + strlen($head_file) + $filesize + strlen("--".$boundary."--\r\n");

			$headers = "POST /test/file.php HTTP/1.0\r\n";
			$headers.="Host: ".$host."\r\n";
			$headers.="Referer: ".$host."\r\n";
			$headers.="User-Agent: Opera\r\n";
			$headers.="Content-type: multipart/form-data, boundary=".$boundary."\r\n";
			$headers.="Content-length: ".$contentlength."\r\n\r\n";


			if(!$fp = fsockopen($host))
				throw new Exception("Error while sending file by POST: Can't open connection to webserver.");
			fputs($fp, $headers);
			fputs($fp, $data);
			fputs($fp, $head_file);

			$fp2 = fopen($file_send, "rb");
			while(!feof($fp2))
			{
				$as=fgets($fp2, 2048);
				fputs($fp, $as);
			}
			fclose($fp2);

			fputs($fp, "\r\n--".$boundary."--\r\n");

			fclose($fp); // закрыли поток.
			return true;
		}
		catch(Exception $e){
			return $e;
		}

	}
	/**
	 * Функция сохраняет файл на основной сервер и на некоторое количество дополнительных
	 * Количество дополнительных серверов указывается отдельным параметром по умолчанию -- 2.
	 * Параметры файла сохраняются в базу.
	 * @param $userId
	 * @param $userPath
	 * @param $filename
	 * @param $localFilePath
	 * @param $filesize
	 * @param $hash
	 * @param $timeStamp
	 * @param int $bu_serverCount
	 * @return bool|string
	 * @throws Exception
	 */
	public function save_file($userId, $userPath, $filename, $localFilePath, /*$filesize, $hash, $timeStamp,*/ $bu_serverCount = 2){

			//дополним пользовательский путь нужным слэшем, чтобы обоззначить его, как директорию
			$separator = substr($userPath, -1);
			if($separator != '\\' && $separator != '/'){
				if(preg_match('|/|', $userPath)>0)
					$userPath.='/';
				else
					$userPath.='\\';
			}
			$separator = substr($userPath, 1);
			if($separator != '/')
			$userPath='/'.$userPath;

			$filesize = filesize($localFilePath);
			$timestamp = date('Y-m-d H:i:s',filectime($localFilePath));
			$fileExist = $this->get_unic($userPath,$filename,$userId);
			if($fileExist != NULL){
				return $this->reload($localFilePath,$userId,$userPath,$filename);
			}
			$servers = \ServerModel::get_servers($bu_serverCount+1);
			$bu_servers = array();
			//Сохраняем файл на первичный сервер
			$server = array_shift($servers);
			if(!$server){
				throw new Exception('Error while choosing server: Sorry, but all servers are unavaliable');
			}
			$ftp = ftp_connect($server->ip);
			$isLogin = ftp_login($ftp,$server->username,$server->password);
				if(!$isLogin)
					throw new Exception("Can't login");
			$serverFilePath = \DirectoryModel::make_server_path($userId,$userPath,$server);
		//	ftp_mkdir($ftp, $serverFilePath);
			\DirectoryModel::make_directory($ftp,$serverFilePath);
			$result = ftp_put($ftp, $serverFilePath.$filename, $localFilePath, FTP_BINARY);
			if(!$result){
				throw new Exception('Internal error: Can not save file to server '.$server);
			}
			$this->fileData['server'] = $server->id;
			$server->add_datasize($filesize)
				   ->save_params();
			$server->refresh();

			//Сохраняем в БэкАп сервера
			while(isset($servers[0])){
					$server = array_shift($servers);
					if(!$server){
						throw new Exception('Error while choosing server: Sorry, but all servers are unavaliable');
					}
				$ftp = ftp_connect($server);
				$isLogin = ftp_login($ftp,$server->username,$server->password);
				if(!$isLogin)
					throw new Exception("Can't login");
				\DirectoryModel::make_directory($ftp,$serverFilePath);
				$result = ftp_put($ftp, $serverFilePath.$filename, $localFilePath, FTP_BINARY);
				if(!$result){
					throw new Exception('Internal error: Can not save file to server '.$server);
				}
				$bu_servers[] = $server->id;
				$server->add_datasize($filesize)
					   ->save_params();
				$server->refresh();
			}
			\DirectoryModel::save_path($userId,$userPath);
			$this->save_file_path($userId,$userPath,$filename);
			$this->fileData['name']= $filename;
			$this->fileData['path']= str_replace('\\','/',$userPath);
			$this->fileData['bu_server']=join(',', $bu_servers);
			$this->fileData['hash'] = md5_file($localFilePath);
			$this->fileData['ouner_id'] = $userId;
			$this->fileData['file_size'] = $filesize;
			$this->fileData['time'] = $timestamp;

			$this->save_params();
			return true;

	}
	private function reload($localFilePath,$userId,$userPath,$filename){
			$filesize = filesize($localFilePath);
			$timestamp = date('Y-m-d H:i:s',filectime($localFilePath));
			$bu_servers = array();
			//Сохраняем файл на первичный сервер
			$server = new ServerModel;
			$server->get_server_by_id($this->server);
			$ftp = ftp_connect($server->ip);
			$isLogin = ftp_login($ftp,$server->username,$server->password);
			if(!$isLogin)
				throw new Exception("Can't login");
			$serverFilePath = \DirectoryModel::make_server_path($this->ouner_id,$this->path,$server);
			$result = ftp_put($ftp, $serverFilePath.$this->name, $localFilePath, FTP_BINARY);
			if(!$result){
				throw new Exception('Internal error: Can not save file to server '.$server);
			}
			$server ->del_datasize($this->file_size)
					->add_datasize($filesize)
					->save_params();
			$server->refresh();

			//Сохраняем в БэкАп сервера
			$serverIdArr = $this->bu_server;
			if(is_array($serverIdArr)){
				foreach($serverIdArr as $serverId){
					$server = new ServerModel;
					$server->get_server_by_id($serverId);
					if(!$server){
						throw new Exception('Error while choosing server: Sorry, but all servers are unavaliable');
					}
					$ftp = ftp_connect($server);
					$isLogin = ftp_login($ftp,$server->username,$server->password);
					if(!$isLogin)
						throw new Exception("Can't login");
					$result = ftp_put($ftp, $serverFilePath.$this->name, $localFilePath, FTP_BINARY);
					if(!$result){
						throw new Exception('Internal error: Can not save file to server '.$server);
					}
					$bu_servers[] = $server->id;
					$server ->del_datasize($this->file_size)
						->add_datasize($filesize)
						->save_params();
				}
			}

			\DirectoryModel::save_path($userId,$userPath);
			$this->save_file_path($userId,$userPath,$filename);
			$this->fileData['name']= $this->name;
			$this->fileData['path']= $this->path;
			$this->fileData['bu_server']=join(',', $bu_servers);
			$this->fileData['hash'] = md5_file($localFilePath);
			$this->fileData['file_size'] = $filesize;
			$this->fileData['time'] = $timestamp;


			$this->save_params();
			return true;


	}
	private function save_file_path($userId,$userPath,$fileName){
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$userPath = str_replace('\\','/', $userPath);
		$separator = substr($userPath, 1);
		if($separator != '/')
				$userPath='/'.$userPath;
		$redis->sadd($userId.'/'.md5($userPath), $fileName)->exec();
	}
	public function file_copy($userId,$userPath,$fileName,$newPath){
		\DirectoryModel::save_path($userId,$newPath);
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$userPath = str_replace('\\','/', $newPath);
		$redis->sadd($userId.'/'.md5($newPath), $fileName)->exec();

	}
	public function file_move($userId,$userPath,$fileName,$newPath){
		\DirectoryModel::save_path($userId,$newPath);
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$userPath = str_replace('\\','/', $newPath);
		$redis->sadd($userId.'/'.md5($newPath), $fileName)->exec();
		$redis->srem($userId.'/'.md5($userPath), $fileName)->exec();
	}

	public function file_remove($userId,$userPath,$fileName){
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$redis->srem($userId.'/'.md5($userPath), $fileName)->exec();
		$fileDB = new \DB\MySQL('file');
		$this->get_unic($userPath,$fileName,$userId);
		$fileDB->delete()->where('id=?',$this->id)->exec();
	}

	function new_inst(){
		return new self;
	}
}