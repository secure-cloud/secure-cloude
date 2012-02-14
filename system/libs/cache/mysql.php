<?php
namespace Cache;

class Mysql implements ICache {
	
	private $_mysql = NULL;
	
	public function __construct() {
		$this->_mysql = new \DB\MySQL('cache');
	}
	
	public function add($data, $key) {
		if (!is_scalar($data))
			$data = serialize($data);
		try {
			$result = $this
				->_mysql
				->insert(array(
					'key'=>$key,
					'value'=>$value
				))
				->exec();
			return $result > 0;
		} catch (Exception $e) {
			return false;
		}
	}
	
	public function get($id) {
		$result = '';
		try {
			$result = $this
				->_mysql
				->select('value')
				->where('key = ?', $key)
				->scalar();
			if (($ret = @unserialize($result)) === false)
				return $result;
			else
				return $ret;
		} catch (Exception $e) {
			return NULL;
		}		
	}
	
	public function set($data, $id) {
		if (!is_scalar($data))
			$data = serialize($data);
		try {
			$result = $this
				->_mysql
				->update('value', $data)
				->where('key = ?', $value)
				->exec();
			return $result > 0;
		} catch (Exception $e) {
			return false;
		}
	}
}
?>
