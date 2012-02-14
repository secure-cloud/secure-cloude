<?php
	namespace System;
	/**
	 * class Request
	 * 
	 * класс для запросов на удаленные сервера
	 * 
	 * @author Константин Макарычев
	 */
	class Request {
		private $_address = '';
		private $_handler = false;
		
		public function __construct() {
			$this->_address = \System\Config::instance()->request['default'] ?: '';
			$this->_handler = curl_init();
			//устанавливаем дефолтные параметры
			$this->returntransfer = true;
			$this->followlocation = true;
			$this->timeout = \System\Config::instance()->request['timeout'];
			$this->cookiefile = 'cookie';
			$this->cookiejar = 'cookie';
		}
		
		public function __destruct() {
			//сделал дело - протри станок
			curl_close($this->_handler);
		}
		
		/**
		 * public function post
		 * 
		 * делает запрос с отправкой post
		 * 
		 * @param string $url адрес, куда идет запрос
		 * @param array $params post-параметры
		 * @return string
		 */
		public function post($url, array $params) {
			if (!$url)
				throw new Exception('Illegal url');
			if (preg_match("#(https?|ftp)://\S+[^\s\.,>)\];'\"!?]#"))
				$this->url = $url;
			elseif ($this->_address != '')
				$this->url = $this->address.$url;
			else
				throw new Exception('Illegal url');
			$this->post = true;
			$this->postfields = $params;
			return $this->exec();
		}
		
		/**
		 * public function exec
		 * 
		 * совершает запрос
		 * 
		 * @return string
		 */
		public function exec() {
			return curl_exec($this->_handler);
		}
		
		public function __set($name, $value) {
			//все поля - установки curl'а
			$const = 'CURLOPT_'.strtoupper($name);
			if (!defined($const))
				throw new Exception('Undefined variable Request::'.$name);
			curl_setopt($this->_handler, constant($const), $value);
		}
	}