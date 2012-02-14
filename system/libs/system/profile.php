<?php
	namespace System;
	/**
	 * class Profile
	 * 
	 * класс для профилирования различных частей системы
	 * 
	 * @author Константин Макарычев
	 */
	class Profile extends \System\Singletone {
		
		private $_tags = array();
		private $_last = NULL;
		
		public $system_start;
		public $system_finish;
		
		/**
		 * public function start
		 * 
		 * начать замер времени выполнения
		 * 
		 * @param string $tag имя временнОй метки
		 */
		public function start($tag) {
			$arr = array();
			$arr['title'] = $tag;
			$arr['start'] = microtime(true);
			$last = array_push($this->_tags, $arr);
			$this->_last = $last ?: 0;
		}
		
		/**
		 * public function finish
		 * 
		 * закончить замер времени
		 * 
		 * @param string $tag имя временнОй метки
		 */
		public function finish($tag) {
			if ($this->_last === NULL)
				return;
			$arr = $this->_tags[$this->_last];
			if (isset($arr['finish']))
				return;
			$arr['finish'] = microtime(true);
			$this->_tags[$this->_last] = $arr;
			$this->_last = NULL;
		}
	}