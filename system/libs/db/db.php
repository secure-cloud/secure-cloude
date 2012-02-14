<?php
	namespace DB;
	/**
	* DB
	*
	* класс-обертка для работы с mysql
	*
	* @author Константин Макарычев
	*/
	class DB extends \System\Singletone {
		/**
		* $_link
		*
		* @var mysql-хендлер
		*/
		private $_link = FALSE;
		
		/**
		* $_host
		*
		* @var адрес mysql-сервера (string)
		*/
		private $_host = '';
		/**
		* $_user
		*
		* @var пользователь mysql
		*/
		private $_user = '';
		/**
		* $_pass
		*
		* @var пароль от mysql
		*/
		private $_pass = '';
		/**
		* $_db
		*
		* @var текущая база
		*/
		private $_db = '';
		
		/**
		* public function connect
		*
		* запоминает данные для позднего подключения
		* реальное подключение проихойдет при первом запросе
		*
		* @var string $host адрес mysql-сервера
		* @var string $user пользователь mysql
		* @var string $pass пароль от mysql
		* @var string $db база
		*/
		public function connect($host, $user, $pass, $db) {
			$this->_host = $host;
			$this->_user = $user;
			$this->_pass = $pass;
			$this->_db 	 = $db;
		}
		
		/**
		* public function select_db
		*
		* переключает текущую базу
		*
		* @var string $db имя базы
		*/
		public function select_db($db) {
			if (!$this->_link)
				$this->lazy_connect();
			$this->_db = $db;
			$result = mysql_select_db($db, $this->_link);
			if ($result === FALSE)
				throw new \Exception('Error in database selection: '.mysql_error());
		}
		
		/**
		* public function post
		*
		* делает запрос в базу
		*
		* @var string $sql sql-запрос
		* @return MySQL-Resource
		*/
		public function post($sql) {
			if (!$this->_link)
				$this->lazy_connect();
			$result = mysql_query($sql, $this->_link);
			if ($result === FALSE)
				throw new \Exception('Error in mysql query: '.mysql_error());
			return $result;
		}
		
		/**
		* public function affected
		*
		* обертка для mysql_affected_rows
		* @link http://ru.php.net/manual/en/function.mysql-affected-rows.php
		*
		* @return int
		*/
		public function affected() {
			if (!$this->_link)
				$this->lazy_connect();
			return mysql_affected_rows($this->_link);
		}
		
		/**
		 * public function insert_id
		 * 
		 * обертка для mysql_insert_id
		 * @link http://ru.php.net/manual/en/function.mysql-insert-id.php
		 * 
		 * @return int
		 */
		public function insert_id() {
			if (!$this->_link)
				$this->lazy_connect();
			return mysql_insert_id($this->_link);
		}
		
		/**
		 * public function last_error
		 * 
		 * обертка для mysql_error
		 * @link http://ru.php.net/manual/en/function.mysql-error.php
		 * 
		 * @return string
		 */
		public function last_error() {
			if (!$this->_link)
				$this->lazy_connect();
			return mysql_error($this->_link);
		}
		
		/**
		* public function result
		*
		* получает результат запроса
		*
		* @var string $sql sql-запрос
		* @return array
		*/
		public function result($sql) {
			if (!$this->_link)
				$this->lazy_connect();
			$result = $this->post($sql);
			$ret = array();
			while ($row = mysql_fetch_assoc($result))
				$ret[] = $row;
			return $ret;
		}
		
		/**
		* public function escape
		*
		* обертка для mysql_real_escape_string
		* @link http://ru.php.net/manual/en/function.mysql-real-escape-string.php
		*
		* @var mixed $string - экранируемая строка
		* @return string
		*/
		public function escape($param) {
			if (!$this->_link)
				$this->lazy_connect();
			
			$ret = mysql_real_escape_string($param, $this->_link);
			if (is_string($param))
				return '"'.$ret.'"';
			else
				return intval($param);
		}
		
		/**
		* private function lazy_connect
		*
		* реальное подключение к базу
		*/
		private function lazy_connect() {
			$this->_link = mysql_connect(
				$this->_host,
				$this->_user,
				$this->_pass
			);
			if ($this->_link === FALSE)
				throw new \Exception('Error while db connection');
			$result = mysql_select_db($this->_db, $this->_link);
			if ($result === FALSE)
				throw new \Exception('Error in database selection: '.mysql_error());
			mysql_query('SET NAMES utf8;');
		}
	}
