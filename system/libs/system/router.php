<?php
	namespace System;
	/**
	* Router
	*
	* класс для работы с маршрутами
	* конфигурация по умолчанию в ROOT/application/config/router.php
	*
	* @author Константин Макарычев
	*/
	class Router {
		/**
		* $_routers
		*
		* @var Array хранит в экземпляре настройки маршрутов
		*/
		private $_routers = array();
		
		/**
		* public function __construct
		*
		* конструктор класса
		*
		* @var Array $routers настройки маршрутов
		*/
		public function __construct(array $routers) {
			$this->_routers = $routers;
		}
		
		/**
		* public function run
		*
		* разбирает запрос и делегирует управление соответсвующему действию контроллера
		*
		* @var string $request 
		*/
		public function run($request) {
			// TODO: исправить этот говнокод!
			if ($request == '') {
				$runpath = \System\Config::instance()->route['default'];
				$segments = explode('/', $runpath);
				$controller = ucfirst(array_shift($segments)).'Controller';
				$method = array_shift($segments).'_action';
				$controllerInst = new $controller;
				if (!is_callable(array($controllerInst, $method)))
					throw new \System\HttpException("Method $controller::$method doesn't exist.", 404);
				$controllerInst->_pre_action();
				$controllerInst->$method();
				$controllerInst->_post_action();
				return;
			}
			foreach ($this->_routers as $name => $params) {
				//ищем нужный конфиг по регулярке
				if (preg_match('|'.$params['regexp'].'|', $request) == 0)
					continue;
				//на основе регулярки, строим внутренний путь
				$runPath = preg_replace('|'.$params['regexp'].'|', $params['run'], $request);
				//делим на сегменты
				$segments = explode('/', $runPath);
				//первый сегмент - контроллер
				$controller = ucfirst(array_shift($segments)).'Controller';
				//второй - действие
				$method = array_shift($segments).'_action';
				//остальное параметры
				$parameters = $segments;
				$controllerInst = new $controller;
				if (!is_callable(array($controllerInst, $method)))
					throw new \System\HttpException("Method $controller::$method doesn't exist.", 404);
				if (isset($params['layout']))
					$controllerInst->set_layout($params['layout']);
				if (isset($params['view']))
					$controllerInst->set_view($params['view']);
				$controllerInst->_pre_action();
				call_user_func_array(array($controllerInst, $method), $parameters);
				$controllerInst->_post_action();
				return;
			}
			throw new \System\HttpException("Path ".$request." is not found.", 404);
		}
	}
