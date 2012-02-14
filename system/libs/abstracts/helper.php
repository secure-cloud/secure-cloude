<?php
	namespace Abstracts;
	/**
	 * class Helper
	 * 
	 * класс-обертка для подключения и использования хелперов
	 * 
	 * @author Константин Макарычев
	 */
	class Helper {
		/**
		 * private static $_helpers
		 * 
		 * @var array здесь хранится список подключенных хелперов
		 */
		private static $_helpers = array();
		
		/**
		 * __callStatic
		 * 
		 * магический метод 
		 * @link http://www.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
		 * по его названию определяем какой хелпер подключить
		 * 
		 * @param string $name
		 * @param array $params
		 * @return Helper 
		 */
		public static function __callStatic($name, $params) {
			if (!in_array($name, self::$_helpers)) {
				self::$_helpers[] = $name;
				include(DIR_ROOT.'system/application/helpers/'.$name.'.php');
			}
			return new self;
		}
		
		/**
		 * __call
		 * 
		 * обертываем вызов хелпера, как вызов метода объекта класса
		 * 
		 * @param string $name имя функции хелпера
		 * @param array $params параметры функции
		 * @param mixed результат выполнения хелпера
		 */
		public function __call($name, $params) {
			return call_user_func_array($name, $params);
		}
	}