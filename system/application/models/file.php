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
		$lib = $fileDB->select()
			->where($paramType.' = ?', $paramValue)
			->first();
		if($lib){
			foreach($lib as $rowName => $value){
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
		if($this->fileData['id']){
			foreach($this->fileData as $rowName => $value){
				if ($rowName!='id'){
					$update[$rowName]=$value;
				}
			}
			return $currentFilesDB->update($update)
				->exec();
		}
		else{
			foreach($this->fileData as $rowName => $value){
				$update[$rowName]=$value;
				return $currentFilesDB->insert($update)
					->exec();
			}
		}
		return NULL;

	}

	public function set_into_catalog($userId, $userPath){
		$path = $this->make_sever_path($userId, $userPath);
		if(substr($path, -1)=='\\' || substr($path, -1)=='/'){ //Если true, то счситаем, что работаем с каталогом
			preg_match('(.*\\)(w+\\)$', $path, $matches);
			$redis = new \Cache\Redis;
			$redis->sadd($matches[2],$matches[1]);
			return $this;
		}else{
			preg_match('(.*\\)(w+\.?w*)$', $path, $matches);
			$redis = new \Cache\Redis;
			$redis->sadd($matches[2],$matches[1]);
			return $this;
		}
	}

	public function get_catalog($userId, $userPath){
		//ToDo добавить в каталог

	}
	private function delete($userId, $userPath){
		try{
			$serverPath = \System\Config::instance()->filetransfer['serverpath'].$this->make_sever_path($userId,$userPath);
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
	private function make_sever_path($userId, $userPath){
		if(preg_match('|(\w):(.*)|', $userPath, $matches)!=1)
			throw new Exception('Wrong file path');
		return $userId.'/'.$matches[1].'/'.str_replace('\\',SERVER_PATH_SEPARATOR,$matches[2]);
	}

	public function load_file($userId, $userPath){
		try{
			$serverPath = \System\Config::instance()->filetransfer['serverpath'].$this->make_sever_path($userId,$userPath);
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
					$ftp = ftp_ssl_connect($server);
					if(!$ftp)
						throw new Exception("Could not connect to web-server while loading file.");
					$localPath = \System\Config::instance()->filetransfer['localtmp'].'/'.md5($serverPath);
					$result = ftp_get($ftp,$localPath, $serverPath, FTP_BINARY);
				}
				if(!$result)
					throw new Exception("Couldn't find file. Please contact with our manager");
			}
			if($this->post_file($localPath)!==true)
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
	private function post_file($filePath){
		try{
			$host=\System\Config::instance()->filetransfer['fileposturl'];
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
	 * @param $localFilePath
	 * @param int $bu_serverCount
	 * @return bool|string
	 * @throws Exception
	 */
	public function save_file($userId,$userPath, $localFilePath, $bu_serverCount = 2){
		$ftp = false;
		$server = '';
		$serverExeption = array();
		$bu_servers = array();
		\System\Config::instance()->init(dirname(__FILE__).'/../system/application/config/config.php');
		$RemoteFilePath = \System\Config::instance()->filetransfer['serverpath'].$this->make_sever_path($userId,$userPath);

		try{
			//Сохраняем файл на первичный сервер
			while(!$ftp){
				$timeOut = time();
				$server = $this->get_server($serverExeption);
				if(!$server){
					throw new Exception('Error while choosing server: Sorry, but all servers are unavaliable');
				}
				$ftp = ftp_ssl_connect($server);
				if($timeOut > (time()+5))
					throw new Exception('Internal error: Service connection timeout');
			}

			$result = ftp_put($ftp, $RemoteFilePath, $localFilePath, FTP_BINARY);
			if(!$result){
				throw new Exception('Internal error: Can not save file to server '.$server);
			}
			$this->fileData['server'] = $server;
			$serverExeption[] = $server;

			//Сохраняем в БэкАп сервера
			for($i = 0; $i < $bu_serverCount; $i++){
				while(!$ftp){
					$timeOut = time();
					$server = $this->get_server($serverExeption);
					if(!$server){
						throw new Exception('Error while choosing server: Sorry, but all servers are unavaliable');
					}
					$ftp = ftp_ssl_connect($server);
					if($timeOut > (time()+5))
						throw new Exception('Internal error: Can not connetct to service');
				}

				$result = ftp_put($ftp, $RemoteFilePath, $localFilePath, FTP_BINARY);
				if(!$result){
					throw new Exception('Internal error: Can not save file to server '.$server);
				}
				$bu_servers[] = $server;
				$serverExeption[] = $server;
			}
			preg_match('|(.*)/(w+).(w+)|',$RemoteFilePath,$matches); //ToDo: переписать РегЭксп на выборку пути, расширения и имени файла
			$this->fileData['name']= $matches[2];
			$this->fileData['ext']= $matches[3];
			$this->fileData['path']= $matches[1].'/';
			$this->fileData['fullname']= $matches[0];
			$this->fileData['bu_server']=join(',', $bu_servers);
			$this->fileData['hash'] = md5_file($localFilePath);


			$this->save_params();
			return true;

		}
		catch(Exception $e){
			unset($this->fileData['server']);
			unset($this->fileData['bu_server']);
			return $e->getMessage();
		}

	}
	/**
	 * Функция возвращает ip случайного сервера. Принимает массив ip серверов, которые не должны участвовать в выборке.
	 *
	 * @param array $exept
	 * @return array
	 */
	private function get_server($exept = array()){
		$serversDB = new \DB\MySQL('servers');
		return $serversDB->select('ip')->where('ip NOT IN ? ORDER BY RAND() LIMIT(1)', join(',', $exept))->first();
	}

	function new_inst(){
		return new self;
	}
}
