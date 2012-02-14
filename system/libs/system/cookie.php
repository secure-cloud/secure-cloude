<?php
	namespace System;
	/**
	 * class Cookie
	 * 
	 * класс обертка для печенек
	 * 
	 * @author Константин Макарычев
	 */
	class Cookie {
		private $_cookie = array();
		
		const MINUTE = 60;
		const HOUR = 3600;
		const DAY = 86400;
		const MONTH = 2592000;
		const YEAR = 31536000;
		
		public function __construct() {
			$this->_cookie = $_COOKIE;
		}
		
		/**
		 * public function set
		 * 
		 * установить куки
		 * 
		 * @param string $name имя куки
		 * @param mixed $value значение (не скалярное будет сериализовано)
		 * @param int $time время жизни
		 */
		public function set($name, $value, $time = 3600) {
			if (!is_scalar($value))
				$value = serialize($value);
			$time += time();
			setcookie($name, $value, $time, '/');
			$this->_cookie[$name] = $value;
		}
		
		/**
		 * public function get
		 * 
		 * возвращает куку по имени. если значение было сериализовано, 
		 * рассериализует
		 * 
		 * @param string $name имя куки
		 * @return mixed
		 */
		public function __get($name) {
			if (!isset($this->_cookie[$name]))
				return NULL;
			if (($value = @unserialize($this->_cookie[$name])) !== FALSE)
				return $value;
			else
				return $this->_cookie[$name];
		}
	}
