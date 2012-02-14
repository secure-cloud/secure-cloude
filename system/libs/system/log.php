<?php
	namespace System;
	
	/**
	 * class Log
	 * 
	 * логгирование с ротацией и шаблонами
	 * 
	 * @author Константин Макарычев
	 */
	class Log {
		
		/**
		 * public static function log
		 * 
		 * записывает лог в файл
		 * 
		 * @param string $text что нужно записать
		 * @param string $type тип записи (от этого зависит путь)
		 * @param string $template шаблон записи (определены в конфиге)
		 */
		public static function log($text, $type, $template = 'default') {
			if (!is_string($type) || preg_match('/[^\w\_\-]/i', $type) == 1)
				return;
			$log = self::template($text, $template);
			$path = \System\Config::instance()->log['path'];
			if (!is_dir($path.$type))
				mkdir($path.$type, 0777);
			$filename = $path.$type.'/log'.date('dmY');
			if (!is_file($filename))
				touch($filename);
			file_put_contents($filename, $log, FILE_APPEND);
		}
		
		private static function template($text, $template) {
			$tmpl = \System\Config::instance()->log['template'][$template];
			$tmpl = str_ireplace('{date}', date('d.m.Y'), $tmpl);
			$tmpl = str_ireplace('{time}', date('H:i:s'), $tmpl);
			$tmpl = str_ireplace('{text}', $text, $tmpl);
			return $tmpl;
		}
	}