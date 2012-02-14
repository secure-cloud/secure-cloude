<?php
	namespace System;
	/**
	 * Config
	 * 
	 * Конфигурация приложения в синглтоне
	 * 
	 * @author Константин Макарычев
	 */
	class Config extends \System\Singletone {
		private $_config = array();
		
		/**
		 * public function init
		 * 
		 * инициализирует конфиг из файла
		 * 
		 * @param string $configfile путь к файлу конфига
		 */
		public function init($configfile) {
			$this->_config = include($configfile);
		}
		
		public function __get($name) {
			return isset($this->_config[$name]) ? $this->_config[$name] : NULL;
		}
		
		public function __set($name, $value) {
			$this->_config[$name] = $value;
		}
	}
