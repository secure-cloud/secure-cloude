<?php
	namespace Abstracts;
	
	class Command {
		/**
		 * protected model
		 * 
		 * @var Model хранит экземпляр фабрики моделей
		 */
		protected $_model = NULL;
		private $_ishelper = false;
		private $_iscache = false;

		public function __get($name) {
			if ($this->_ishelper) {
				$this->_ishelper = false;
				return \Abstracts\Helper::$name();
			}
			if ($this->_iscache) {
				$this->_iscache = false;
				return \System\Cache::factory(constant('\System\Cache::'.$name));
			}
			switch($name) {
				case 'view':
					if ($this->_view === NULL) {
						//имя класса вьюшки берем из названия класса контроллера
						$controller = get_called_class();
						$controller = preg_replace('/Command/', '', $controller);
						$viewclass = $controller.'View';
						//если класс есть - подключаем, 
						//нет - используем абстрактный
						try {
							$this->_view = new $viewclass;
						} catch (\Exception $e) {
							$this->_view = new \Abstracts\View;
						}
					}
					$this->_view->css = $this->_css;
					$this->_view->js = $this->_jss;
					if ($this->_viewmode !== NULL)
						$this->_view->mode = $this->_viewmode;
					return $this->_view;
				case 'model':
					if ($this->_model === NULL)
						$this->_model = new \Abstracts\Model;
					return $this->_model;
				case 'helper':
					$this->_ishelper = true;
					return $this;
				case 'config':
					return \System\Config::instance();
				case 'cache':
					$this->_iscache = true;
					return $this;
				case 'profile':
					return \System\Profile::instance();
			}
		}
	}