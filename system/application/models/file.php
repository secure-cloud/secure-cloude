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
	public function get_unic($path, $name){
		$this->fileData=array();
		$fileDB = new \DB\MySQL('files');
			$file = $fileDB->select()
				->where('path = ? AND name = ? ', $path, $name)
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
		if ($user){
			foreach($user as $rowName => $value){
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
	/*
	 * Функция удаляет файл
	 */
	private function delete($userId, $userPath){
		try{
			$serverPath = \System\Config::instance()->filetransfer['serverpath'].DirectoryModel::make_server_path($userId,$userPath);
			$this->get_by('fullname', $serverPath);
			$servers[] = $this->fileData['server'];
			$servers = array_merge($servers, array_shift(explode(',', $this->fileData['bu_server'])));
			while(!empty($servers)){
				$ftp = ftp_ssl_connect(array_shift($servers));
				ftp_delete($ftp,$serverPath);
			}
			$fileDB = new \DB\MySQL('files');
			$fileDB->delete()->where('id = ?', $this->fileData['id'])->exec();
			return true;
		}
		catch (Exception $e){
			return $e;
		}
	}

	/*
	 * Загрузить файл на сервер.
	 */
	public function load_file($userId, $userPath,$host){
		try{
			$serverPath = \System\Config::instance()->filetransfer['serverpath'].DirectoryModel::make_server_path($userId,$userPath);
			$this->get_by('fullname', $serverPath);
			$ftp = ftp_ssl_connect($this->fileData['server']);
				if(!$ftp)
					throw new Exception("Could not connect to web-server while loading file.");
			$localPath = \System\Config::instance()->filetransfer['localtmp'].'/'.md5($serverPath);
			$result = ftp_get($ftp,\System\Config::instance()->filetransfer['localtmp'].'/'.$localPath, $serverPath, FTP_BINARY);
			if(!$result){
				$bu_servers = explode(',', $this->fileData['bu_server']);
				while(!empty($bu_servers) || $result){
					$server = array_shift($bu_servers);
					$ftp = ftp_connect($server);
					$isLogin = ftp_login($ftp,$server->username,$server->password);
					if(!$isLogin)
						throw new Exception("Can't login");
					if(!$ftp)
						throw new Exception("Could not connect to web-server while loading file.");
					$localPath = \System\Config::instance()->filetransfer['localtmp'].'/'.md5($serverPath);
					$result = ftp_get($ftp,$localPath, $serverPath, FTP_BINARY);
				}
				if(!$result)
					throw new Exception("Couldn't find file. Please contact with our manager");
			}
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
	 * Отправляет сохраненный локально файл указанному в конфиге серверу
	 * @param $filePath
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
	public function save_file($userId, $userPath, $filename, $localFilePath, $filesize, $hash, $timeStamp, $bu_serverCount = 2){
		try{
			if($hash != md5_file($localFilePath))
				throw new Exception('Wrong hash sum. Probably file was broken');
			$fileExist = $this->get_unic($userPath,$filename);
			if($fileExist != NULL){
				return $this->reload($hash, $timeStamp,$filesize,$localFilePath);
			}

			//дополним пользовательский путь нужным слэшем, чтобы обоззначить его, как директорию
			$separator = substr($userPath, -1);
			if($separator != '\\' && $separator != '/'){
				if(preg_match('|/|', $userPath)>0)
					$userPath.='/';
				else
					$userPath.='\\';
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
			$this->fileData['path']= $userPath;
			$this->fileData['bu_server']=join(',', $bu_servers);
			$this->fileData['hash'] = md5_file($localFilePath);
			$this->fileData['ouner_id'] = $userId;


			$this->save_params();
			return true;

		}
		catch(Exception $e){
			unset($this->fileData['server']);
			unset($this->fileData['bu_server']);
			return $e->getMessage();
		}

	}
	private function reload($hash, $timestamp,$filesize,$localFilePath){
		if($this->hash != $hash && $this->time != $timestamp){
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
			$this->fileData['name']= $this->name;
			$this->fileData['path']= $this->path;
			$this->fileData['bu_server']=join(',', $bu_servers);
			$this->fileData['hash'] = md5_file($localFilePath);
			$this->fileData['file_size'] = $filesize;
			$this->fileData['time'] = $timestamp;


			$this->save_params();
			return true;

		}
	}
	private function save_file_path($userId,$userPath,$fileName){
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$userPath = str_replace('\\','/', $userPath);
		$redis->sadd($userId.'/'.md5($userPath), $fileName)->exec();
	}

	function new_inst(){
		return new self;
	}
}