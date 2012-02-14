<?php
	namespace Abstracts;
	/**
	 * abstract class Controller
	 * 
	 * абстрактный контроллер
	 * 
	 * @author Константин Макарычев
	 */
	abstract class Controller {
		/**
		 * protected layout 
		 * 
		 * @var string хранит layout для вьюшки
		 */
		protected $_layout = '';
		/**
		 * protected view
		 * 
		 * @var View хранит экземпляр вьюшки
		 */
		protected $_view = NULL;
		/**
		 * protected model
		 * 
		 * @var Model хранит экземпляр фабрики моделей
		 */
		protected $_model = NULL;
		/**
		 * protected jss
		 * 
		 * @var array хранит подключенные js-скрипты
		 */
		protected $_jss = array();
		/**
		 * protected css
		 * 
		 * @var array хранит подключенные css'ки
		 */
		protected $_css = array();
		/**
		 * protected viewmode
		 * 
		 * @var array хранит текущий тип отображения
		 */
		protected $_viewmode = NULL;
		/**
		 * protected cookie
		 * 
		 * @var \System\Cookie хранит экземпляр класса куков
		 */
		protected $_cookie = NULL;
		/**
		 * protected session
		 * 
		 * @var \System\Session хранит экземпляр класса сессии
		 */
		protected $_session = NULL;
		/**
		 * protected sendmail
		 *
		 * @var array хранит экземпляр класса почты
		 */
		protected $_sendmail = NULL;
		/**
		 * protected request
		 * 
		 * @var \System\Request хранит экземпляр класса запросов
		 */
		protected $_request = NULL;
		private $_ishelper = false;
		private $_iscache = false;
		public function __construct() {
		}
		
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
						$controller = preg_replace('/Controller/', '', $controller);
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
				case 'sendmail':
					if ($this->_sendmail === NULL)
						$this->_sendmail = new \System\SendMail;
					return $this->_sendmail;
				case 'post':
					return (object)$_POST;
				case 'get':
					return (object)$_GET;
				case 'cookie':
					if ($this->_cookie === NULL)
						$this->_cookie = new \System\Cookie;
					return $this->_cookie;
				case 'session':
					if ($this->_session === NULL)
						$this->_session = new \System\Session;
					return $this->_session;
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
				case 'request':
					if ($this->_request === NULL)
						$this->_request = new \System\Request;
					return $this->_request;
			}
		}
		
		/**
		 * public function set_layout
		 * 
		 * устанавливает layout
		 * 
		 * @param string $layout 
		 */
		public function set_layout($layout) {
			$this->_layout = $layout;
		}
		
		/**
		 * public function set_view
		 * 
		 * устанавливает тип отображения
		 * 
		 * @param type $viewmode 
		 */
		public function set_view($viewmode) {
			$this->_viewmode = $viewmode;
		}
		
		/**
		 * public function add_js
		 * 
		 * добавляет js-скрипт
		 * 
		 * @param string $js путь к скрипту
		 */
		public function add_js($js) {
			$this->_jss[] = $js;
		}
		
		/**
		 * public function add_css
		 * 
		 * добавляет css'ку
		 * 
		 * @param string $css путь к css'ке
		 */
		public function add_css($css) {
			$this->_css[] = $css;
		}
		
		/**
		 * public function redirect
		 * 
		 * редирект
		 * 
		 * @param string $url адрес переадресации
		 * @param int $code http-код редиректа
		 */
		public function redirect($url, $code = 301) {
			if ($code !== NULL) {
				$codes = array(
					300 => 'Multiple Choices',
					301 => 'Moved Permanently',
					302 => 'Found',
					303 => 'See Other',
					304 => 'Not Modified',
					305 => 'Use Proxy',
					307 => 'Temporary Redirect'
				);
				if (in_array($code, $codes))
					header('HTTP/1.1 '.$code.' '.$codes[$code]);
			}
			header('Location: '.$url);
			exit;
		}
		
		public function _pre_action(){}
		public function _post_action(){}

	}
