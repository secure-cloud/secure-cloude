<?php
	namespace Cache;
	
	class File implements ICache {
		public function add($data, $key) {
			$filename = DIR_ROOT.'system/application/cache/'.$key;
			if (!is_file($filename))
				touch($filename);
			return $this->save($data, $filename);
		}
		
		public function get($id) {
			$result = @file_get_contents(DIR_ROOT.'system/application/cache/'.$id);
			if ($result === false)
				return NULL;
			if (($ret = @unserialize($result)) === false)
				return $result;
			else
				return $ret;
		}
		
		public function set($data, $id) {
			$filename = DIR_ROOT.'system/application/cache/'.$key;
			if (!is_file($filename))
				return false;
			return $this->save($data, $filename);
		}
		
		private function save($data, $filename) {
			if (!is_scalar($data))
				$data = serialize($data);
			$result = @file_put_contents($filename, $data);
			return $result !== false;
		}
	}
