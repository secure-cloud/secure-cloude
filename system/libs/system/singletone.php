<?php
	namespace System;
	/**
	* Singletone
	*
	* реализация родительского класса синглтона
	*
	* @author Константин Макарычев
	*/
	class Singletone {
		/**
		* private static $instances
		*
		* @var массив экземпляров всех синглтонов
		*/
		private static $instances = Array();

		//закрываем доступ к любому способу создания экземпляра класса извне
		private function __construct() {}
		private function __clone() {}
		private function __wakeup() {}

		/**
		* public static function instance
		*
		* метод для доступа и/или создания синглтона
		*
		* @return Singletone
		*/
		public static function instance(){
			//экземпляр дочернего класса
			$class = get_called_class();
			if (!isset(self::$instances[$class]))
				self::$instances[$class] = new $class();
			return self::$instances[$class];
		}
	}
