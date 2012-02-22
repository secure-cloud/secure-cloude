<?php

	file_put_contents('file',print_r($_POST,true));
	session_start();
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	define('DIR_ROOT', dirname(__FILE__).'/../');
	define('SERVER_PATH_SEPARATOR', '/');
	//все ошибки переделываем под исключения для более удобного отлова
	function error_handler($errno, $errstr, $errfile, $errline) {
		throw new Exception($errno.' '.$errstr.' in '.$errfile.' at '.$errline);
	}
	set_error_handler('error_handler');

	function __autoload($classname) {
		$possibilities = array(
			'/^I[A-Z]\w+/' => DIR_ROOT.'system/interfaces/'.strtolower($classname).'.php',
			'/^\w+Controller$/' => DIR_ROOT.'system/application/controllers/'.strtolower(str_replace('Controller', '', $classname)).'.php',
			'/^\w+Model$/' => DIR_ROOT.'system/application/models/'.strtolower(str_replace('Model', '', $classname)).'.php',
			'/^\w+View$/' => DIR_ROOT.'system/application/views/'.strtolower(str_replace('View', '', $classname)).'.php',
			'/^\w+Command$/' => DIR_ROOT.'system/application/commands/'.strtolower(str_replace('Command', '', $classname)).'.php',
			'/\w+\\w+/i' => DIR_ROOT.'system/libs/'.strtolower(preg_replace('/^(\w+)\\\(\w+)/', '\1', $classname)).'/'.strtolower(preg_replace('/(\w+)\\\(\w+)$/', '\2', $classname)).'.php'
		);
		$included = false;
		foreach ($possibilities as $regexp => $path) {
			if (preg_match($regexp, $classname) == 1) {
					if (is_file($path)) {
						include($path);
						return;
					} else {
						throw new Exception('Unknown class '.$classname);
					}
			}
		}
	}
	
	if (isset($argv) && isset($argc)) {
		\System\Config::instance()->init(dirname(__FILE__).'/../system/application/config/config.php');
		\DB\DB::instance()->connect(
			\System\Config::instance()->db['host'],
			\System\Config::instance()->db['user'],
			\System\Config::instance()->db['password'],
			\System\Config::instance()->db['database']
		);

		if ($argc == 1 || ($argc == 2 && ($argv[1] == '-h' || $argv[1] == '--help')))
			die("Usage:\n\t/path/to/php /path/to/index.php <command> [<action> <parameters>]\n");
		if ($argc >= 2) {
			$class = ucfirst($argv[1]).'Command';
			$action = 'index_action';
			$parameters = array();
			if ($argc >= 3) {
				$action = $argv[2].'_action';
				$parameters = array_slice($argv, 3);
			}
			$command = new $class;
			try {
				call_user_func_array(array($command, $action), $parameters);
			} catch (Exception $e) {
				echo 'Ooops '.$e;
			}
		}
	} else {
		\System\Profile::instance()->system_start = microtime(true);
		\System\Config::instance()->init(dirname(__FILE__).'/../system/application/config/config.php');
		\DB\DB::instance()->connect(
			\System\Config::instance()->db['host'],
			\System\Config::instance()->db['user'],
			\System\Config::instance()->db['password'],
			\System\Config::instance()->db['database']
		);
		$request = '';
		if(!empty($_SERVER['REQUEST_URI']))
			$request = trim($_SERVER['REQUEST_URI'], '/');
		if(!empty($_SERVER['PATH_INFO']))
			$request = trim($_SERVER['PATH_INFO'], '/');
		if(!empty($_SERVER['QUERY_STRING']))
			$request = trim($_SERVER['QUERY_STRING'], '/');
		$routes = include(\System\Config::instance()->route['config']);
		$router = new \System\Router($routes);
		try {
			$router->run($request);
		} catch (Exception $e) {
			echo 'Ooops: '.$e;
		}
		\System\Profile::instance()->system_finish = microtime(true);
	}
