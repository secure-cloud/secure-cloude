<?php
	namespace Abstracts;
	/**
	 * Model
	 * 
	 * класс для динамической подгрузки моделей и хранения коллекций объектов
	 * (на самом деле фабрика)
	 * 
	 * @author Константин Макарычев
	 */
	class Model {
		private $_namespace = '';
		private $_models = array();
		public function __construct() {
		}
		
		public function __call($func, $args) {
			//если вызывается функция - это указание на namespace
			$_namespace = $func;
			return $this;
		}
		
		public function __get($name) {
			$namespace = $this->_namespace;
			$this->_namespace = '';
			$class = '';
			//имя_класса_модельки => ИмяКлассаМоделькиModel
			for ($i = 0; $i < strlen($name); $i++) {
				if ($name[$i] == '_')
					$class = strtoupper($name[++$i]);
				else
					$class .= $name[$i];
			}
			$class .= 'Model';
			$nameclass = ucfirst($class);
			if ($namespace != '')
				$nameclass = '\\'.$namespace.'\\'.$nameclass;
			//не будем плодить экземпляры на каждый вызов
			if (!isset($this->_models[$nameclass]))
				$this->_models[$nameclass] = new $nameclass;
			return $this->_models[$nameclass];
		}
	}
