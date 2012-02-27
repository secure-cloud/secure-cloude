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
	private $argsCounter = 0;

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
		$this->argsCounter += 1+count($vars);
		$command="$".strlen($name)."\r\n".$name."\r\n";
		foreach($vars as $value){
			$command.="$".strlen($value)."\r\n".$value."\r\n";
		}
		array_push($this->cmdlist,$command);
		return $this;
	}
	public function exec(){
		try{
			$redis=$this->connect($this->host,$this->port);
			if($this->multi){
				$this->argsCounter += 2;
				array_unshift($this->cmdlist,"$5\r\nmulti\r\n");
				array_push($this->cmdlist,"$4\r\nexec\r\n");
			}
			if(!$redis)
				throw new \Exception("Can't connect to Redis");
			$command = "*".$this->argsCounter."\r\n";
			$command .= join('',$this->cmdlist);
			fwrite($redis,$command);
			$this->result = $this->_read_reply();
			fclose($redis);
			$this->connection=null;
			$this->cmdlist = array();
			$this->argsCounter = 0;
			$this->multi=false;
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

	public function add($vars){
		$this->argsCounter += 1+count($vars);
		$command="$".strlen("add")."\r\n"."add"."\r\n";
		foreach($vars as $value){
			$command.="$".strlen($value)."\r\n".$value."\r\n";
		}
		array_push($this->cmdlist,$command);
		return $this->exec();

	}
	public function get($vars){
		$this->argsCounter += 1+count($vars);
		$command="$".strlen("add")."\r\n"."add"."\r\n";
		foreach($vars as $value){
			$command.="$".strlen($value)."\r\n".$value."\r\n";
		}
		array_push($this->cmdlist,$command);
		return $this->exec();
	}
	public function set($data, $id){
		$this->argsCounter += 1+count($vars);
		$command="$".strlen("add")."\r\n"."add"."\r\n";
		foreach($vars as $value){
			$command.="$".strlen($value)."\r\n".$value."\r\n";
		}
		array_push($this->cmdlist,$command);
		return $this->exec();
	}
	public function tsend(){
		$redis = $this->connect($this->host,$this->port);
		$result = fwrite($redis,"*3\r\n$3\r\nSET\r\n$5\r\nmykey\r\n$7\r\nmyvalue\r\n");
		if(!$result){
			return false;
		}
		$result=$this->_read_reply();
		return $result;

	}

}
?>