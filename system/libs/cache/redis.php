<?php
namespace Cache;
/**
 * MCache
 *
 * реализует ICache для работы с memcached
 *
 * @author Константин Макарычев
 */
class Redis implements \ICache{
	private $host = 'localhost';
	private $port = 6379;
	private $cmdlist = array();
	private $multi = false;
	private $result = NULL;
	private $connection = NULL;

	/**
	 *
	 * @param string $host
	 * @param string $port
	 */
	public function __construct($host = 'localhost', $port = '6379')
	{
		$this->host = $host;
		$this->port = $port;
	}

	public function __call($name,$vars){
		$cmd = $name." ";
		foreach($vars as $key => $value){
			if(is_string($value) && (preg_match('/\s/',$value)==1)){
				$cmd.=' "'.$value.'" ';
			}
			elseif(is_array($value)){
				$cmd.=join(' ',$value);
			}
			else{
				$cmd.= ' '.$vars.' ';
			}
		}
		$this->cmdlist[] = $cmd;
		return $this;

	}
	public function exec(){
		try{
			if($this->multi){
				array_unshift($this->cmdlist,"MULTI");
				array_push($this->cmdlist,"EXEC");
			}
			$redis=$this->connect($this->host,$this->port);
			if(!$redis)
				throw new \Exception("Can't connect to Redis");
			fwrite($redis,array_shift($this->cmdlist));
			$this->result = $this->_read_reply();
			fclose($redis);
			return $this->result;

		}
		catch(\Exception $e){
			return $e;
		}
	}

	private function multi(){
		$this->multi=true;
		return $this;
	}
	private function connect($host, $port)
	{
		if (!empty($this->connection))
		{
			fclose($this->connection);
			$this->connection = NULL;
		}
		$socket = fsockopen($host, $port, $errno, $errstr);
		if (!$socket)
		{
			$this->reportError('Connection error: '.$errno.':'.$errstr);
			return false;
		}
		$this->connection = $socket;
		return $socket;
	}

	protected function _read_reply()
	{
		$server_reply = fgets($this->connection);
		if ($server_reply===false)
		{
			if (!$this->connect($this->host, $this->port))
			{
				return false;
			}
			else
			{
				$server_reply = fgets($this->connection);
				if (empty($server_reply))
				{
					$this->repeat_reconnected = true;
					return false;
				}
			}
		}
		$reply = trim($server_reply);
		$response = null;

		/**
		 * Thanks to Justin Poliey for original code of parsing the answer
		 * https://github.com/jdp
		 * Error was fixed there: https://github.com/jamm/redisent
		 */
		switch ($reply[0])
		{
/* Error reply */
			case '-':
				$this->reportError('error: '.$reply);
				return false;
/* Inline reply */
			case '+':
				return substr($reply, 1);
/* Bulk reply */
			case '$':
				if ($reply=='$-1') return null;
				$response = null;
				$read = 0;
				$size = intval(substr($reply, 1));
				if ($size > 0)
				{
					do
					{
						$block_size = min($size-$read, 4096);
						if ($block_size < 1) break;
						$data = fread($this->connection, $block_size);
						if ($data===false)
						{
							$this->reportError('error when reading answer');
							return false;
						}
						$response .= $data;
						$read += $block_size;
					} while ($read < $size);
				}
				fread($this->connection, 2); /* discard crlf */
				break;
/* Multi-bulk reply */
			case '*':
				$count = substr($reply, 1);
				if ($count=='-1') return null;
				$response = array();
				for ($i = 0; $i < $count; $i++)
				{
					$response[] = $this->_read_reply();
				}
				break;
/* Integer reply */
			case ':':
				return intval(substr($reply, 1));
				break;
			default:
				$this->reportError('Non-protocol answer: '.print_r($server_reply, 1));
				return false;
		}

		return $response;
	}

	protected function reportError($msg)
	{
		trigger_error($msg, E_USER_WARNING);
	}

	public function add($data){
		$this->cmdlist[]="set ".$data; //Todo: Добавить проверку, на случай, если параметр -- строка с пробелами
		return $this->exec();

	}
	public function get($id){
		$this->cmdlist[]="get ".$id; //Todo: Добавить проверку, на случай, если параметр -- строка с пробелами
		return $this->exec();
	}
	public function set($data, $id){
		$this->cmdlist[]="set ".$id." ".$data; //Todo: Добавить проверку, на случай, если параметр -- строка с пробелами
		return $this->exec();
	}
}
?>