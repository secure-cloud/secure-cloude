<?php
	namespace System;
	
	class Session {
		private $_session = array();
		public function __construct() {
			$this->_session = $_SESSION;
		}
		
		public function __set($name, $value) {
			$_SESSION[$name] = $value;
			$this->_session[$name] = $value;
		}
		
		public function __get($name) {
			if (!isset($this->_session[$name]))
				return NULL;
			return $this->_session[$name];
		}
	}