<?php
	namespace DB;
	/**
	* MySQL
	*
	* класс-обертка для запросов к MySQL
	*
	* @author Константин Макарычев
	*/
	class MySQL {
		//текущее соединение для удобства
		private $_connection = NULL;
		//текущая таблица
		private $_table = '';
		//данные для select'а
		private $_fields = '*';
		private $_where = '';
		private $_group = '';
		private $_order = '';
		private $_limit = '';
		private $_join = '';
		//данные для insert'а
		private $_inserts = '';
		private $_ignore = false;
		//данные для update'а
		private $_updates = '';
		//текущее действие
		private $_action = '';
		private $_system = false;
		private $_sign = '';
		//константы группировки
		const ASC = 'ASC';
		const DESC = 'DESC';
		
		public function __construct($table) {
			$this->_connection = \DB\DB::instance();
			$this->_table = $table;
		}
		
		/**
		* public function insert
		*
		* вставляет новую строку в таблицу
		*
		* @param array $values массив с данными вида [поле1 => значение1, поле2 => значение2, ..., полеN => значениеN]
		* @return MySQL
		*/
		public function insert(array $values) {
			$this->_action = 'insert';
			$this->_inserts = $values;
			return $this;
		}
		
		/**
		 * public function ignore
		 * 
		 * игнорирует ошибки дублирования при insert'е
		 * 
		 * @return MySQL
		 */
		public function ignore() {
			$this->_ignore = true;
			return $this;
		}
		
		/**
		* public function update
		*
		* обновляет таблицу
		*
		* @param array $updates массив с данными вида [поле1 => значение1, поле2 => значение2, ..., полеN => значениеN]
		* или массив со списком обновляемых полей
		* или строка с названием поля
		* @param array $arg2 массив с данными для полей из первого параметра
		* или данные для вставки для поля из первого параметра
		* @return MySQL
		*/
		public function update($updates, $arg2 = NULL) {
			//если update указан после insert, это update для on duplicate key
			if ($this->_action != 'insert')
				$this->_action = 'update';
			//если только один параметр массив - это все, что нам нужно
			if ($arg2 == NULL && is_array($updates))
				$this->_updates = $updates;
			//если есть оба параметра и оба массива - первый поля, второй данные
			elseif ($arg2 != NULL && is_array($updates) && is_array($args2))
				$this->_updates = array_combine($updates, $arg2);
			//если оба параметра скалярны - первый поле, второй данные
			elseif ($arg2 != NULL && is_scalar($updates) && is_scalar($arg2))
				$this->_updates = array($updates => $arg2);
			return $this;
		}
		
		/**
		 * public function increment
		 * 
		 * прибавление к полю(ям)
		 * 
		 * @param mixed $fields список полей в массиве или одно поле в строке
		 * @param int $difference второе слагаемое
		 * @return MySQL 
		 */
		public function increment($fields, $difference = 1) {
			$this->_sign = '+';
			return $this->change_fields($fields, $difference);
		}
		
		/**
		 * public function decrement
		 * 
		 * вычитание из поля(ей) 
		 * 
		 * @param mixed $fields список полей в массиве или одно поле в строке
		 * @param type $difference вычитаемое
		 * @return MySQL
		 */
		public function decrement($fields, $difference = 1) {
			$this->_sign = '-';
			return $this->change_fields($fields, $difference);
		}
		
		/**
		 * public function delete
		 * 
		 * удаляет из таблицы
		 * 
		 * @return MySQL 
		 */
		public function delete() {
			$this->_action = 'delete';
			return $this;
		}
		
		/**
		* public function select
		*
		* Записывает нужные поля при выборке
		*
		* @param mixed $arg выбираемое поле, либо массив полей, либо перечесление
		* @return MySQL
		*/
		public function select() {
			$this->_action = 'select';
			if (func_num_args() == 0)
				return $this;
			if (func_num_args() == 1 && is_array(func_get_arg(0)))
				$args = func_get_arg(0);
			$args = func_get_args();
			$fields = array();
			foreach ($args as $arg) {
				if (!is_string($arg))
					continue;
				$fields[] = $arg;
			}
			$this->_fields = join(
				', ', 
				array_map(function($val){
					return '`'.$val.'`';
				}, $fields)
			);
			return $this;
		}
		
		/**
		* public function where
		*
		* Записывет условия выборки
		*
		* @param string condition строка с условием вида 'id > ? and id < ?'
		* @param mixed $arg1..N даные для подстановки в условие
		* @return MySQL
		*/
		public function where($condition) {
			//если есть только условие, делать ничего не надо
			if (func_num_args() == 1) {
				$this->_where = $condition;
				return $this;
			}
			$arguments = array();
			//пробег по аргументам
			for ($i = 1; $i < func_num_args(); $i++) {
				$arg = func_get_arg($i);
				//скалярные просто экранируем
				if (is_scalar($arg)) {
					$arg = $this->_connection->escape($arg);
				//массивы оформляем в (val1, val2, val3, ..., valN)
				} elseif (is_array($arg)) {
					$enumeration = '';
					foreach ($arg as $item) {
						$enumeration .= $this->_connection->escape($item).', ';
					}
					$arg = '('.substr($enumeration, 0, -2).')';
				}
				$arguments[] = $arg;
			}
			//заменяем все ? на соответсвующие данные
			$this->_where = preg_replace(array_fill(0, count($arguments), '/\?/'), $arguments, $condition, 1);
			return $this;
		}
		
		/**
		* public function group
		*
		* Записывает информацию по группировке
		*
		* @param mixed $by группировочное поле, либо массив полей
		* @param string $order порядок группировки MySQL::ASC или MySQL::DESC
		* @return MySQL
		*/
		public function group($by, $order = 'ASC') {
			if (is_string($by))
				$this->_group = $by.' '.$order;
			elseif (is_array($by))
				$this->_group = join(', ', $by).' '.$order;
			return $this;
		}
		
		/**
		* public function order
		*
		* Записывает информацию о сортировке
		*
		* @param mixed $by сортировочное поле или массив полей
		* @param string $order порядок сортировки MySQL::ASC или MySQL::DESC
		* @return MySQL
		*/
		public function order($by, $order = 'ASC') {
			if (is_string($by))
				$this->_order = $by.' '.$order;
			elseif (is_array($by))
				$this->_order = join(', ', $by).' '.$order;
			return $this;
		}
		
		/**
		* public function limit
		*
		* Устанавливает количество записей и смещение выборки
		*
		* @param int $offset количество выбираемых записей или смещение, если указан второй параметр
		* @param int $limit количество выбираемых записей
		* @return MySQL
		*/
		public function limit($offset, $limit = NULL) {
			if ($limit == NULL)
				$this->_limit = $offset;
			else
				$this->_limit = $offset.', '.$limit;
			return $this;
		}
		
		/**
		* public function join
		*
		* Подготавливает выборку к join'у
		*
		* @param string $table таблица для присоединения
		* @return MySQL
		*/
		public function join($table) {
			$this->_join[] = $table;
			return $this;
		}
		
		/**
		* public function on
		*
		* Устанавливает правило присоединения таблицы
		*
		* @param string $field поле, по которому проходит соединения (поле исходной таблицы, если указан второй параметр)
		* @param string $joinfield поле присоединяющейся таблицы
		* @return MySQL
		*/
		public function on($field, $joinfield = NULL) {
			$firstfield = $field;
			$secondfield = $joinfield;
			if (strpos($firstfield, '.') === FALSE)
				$firstfield = $this->_table.'.'.$field;
			if (strpos($secondfield, '.') === FALSE)
				$secondfield = end($this->_join).'.'.$joinfield;
			if ($joinfield === NULL)
				$this->_on[] = $firstfield.' = '.$firstfield;
			elseif ($joinfield !== NULL)
				$this->_on[] = $firstfield.' = '.$secondfield;
			return $this;
		}
		
		/**
		* public function sql
		*
		* Строит sql-запрос по имеющимся данным
		*
		* @return string
		*/
		public function sql() {
			//вот зачем нужен $_action :)
			$method = $this->_action.'_sql';
			$ret = $this->$method();
			$this->flush();
			return $ret;
		}
		
		/**
		* public function first
		*
		* Возвращает первую строку результата запроса
		*
		* @return array
		*/
		public function first() {	
			//самый простой и оптимальный способ выбрать первый на стороне сервера БД
			$this->limit(0, 1);
			$ret = $this->_connection->result($this->sql());
			return reset($ret);
		}
		
		/**
		* public function last
		*
		* Возвращает последнюю строку запроса
		*
		* @return array
		*/
		public function last() {
			$ret = array();
			//если есть порядок сортировки, просто подменим его
			if (!empty($this->_order)) {
				if (strstr($this->_order, 'ASC') !== FALSE)
					$this->_order = str_replace('ASC', 'DESC', $this->_order);
				elseif (strstr($this->_order, 'DESC') !== FALSE)
					$this->_order = str_replace('DESC', 'ASC', $this->_order);
				$this->limit(0, 1);
				$ret = reset($this->_connection->result($this->sql()));
			//иначе сделаем весь запрос, потом вытащим последнюю строку
			} else {
				$ret = $this->_connection->result($this->sql());
				$ret = last($ret);
			}
			return $ret;
		}
		
		/**
		* public function scalar
		*
		* Возвращает скалярное значение запроса
		*
		* @return mixed
		*/
		public function scalar() {
			$this->limit(0, 1);
			$ret = $this->_connection->result($this->sql());
			if (is_array($ret))
				$ret = reset($ret);
			if (is_array($ret))
				$ret = reset($ret);
			return $ret;
		}
		
		/**
		* public function all
		*
		* Возвращает полный результат запроса
		*
		* @return array
		*/
		public function all() {
			$ret = $this->_connection->result($this->sql());
			return $ret;
		}
		
		
		/**
		* public function exec
		*
		* Выполняет запрос и возвращает количество затронутых строк (для insert'ов и update'ов и delete'ов)
		*
		* @return int
		*/
		public function exec() {
			if ($this->_action != 'insert' && 
				$this->_action != 'update' &&
				$this->_action != 'delete')
					return false;
			$this->_connection->post($this->sql());
			return $this->_connection->affected();
		}
		
		public function __call($name, $arguments) {
			$pos = strpos($name, 'select_by');
			if ($pos === FALSE)
				throw new \Exception('Method ' . $name . ' does not exists in '.get_called_class());
			$select = substr($name, $pos+strlen('select_by')+1, strlen($name));
			return $this->select()
						->where($select.' = ?', reset($arguments));
		}
		
		/**
		 * private function delete_sql
		 * 
		 * Строит запрос для delete
		 * 
		 * @return string
		 */
		private function delete_sql() {
			$sql = 'DELETE FROM '.$this->_table;
			if (!empty($this->_where))
				$sql .= ' WHERE '.$this->_where;
			if (!empty($this->_order))
				$sql .= ' ORDER BY '.$this->_order;
			if (!empty($this->_limit))
				$sql .= ' LIMIT '.$this->_limit;
			$sql .= ';';
			return $sql;
		}
		
		/**
		* private function select_sql
		*
		* Строит запрос для select'а
		*
		* @return string
		*/
		private function select_sql() {
			$sql = 'SELECT '.$this->_fields.' FROM '.$this->_table;
			if (!empty($this->_join)) {
				//$sql .= ' JOIN '.$this->_join;
				if (count($this->_join) != count($this->_on))
					throw new \Exception('Error in SQL-expression: join without on');
				for ($i = 0; $i < count($this->_join); $i++)
					$sql .= ' JOIN '.$this->_join[$i].' ON '.$this->_on[$i];
			}
			if (!empty($this->_where))
				$sql .= ' WHERE '.$this->_where;
			if (!empty($this->_group))
				$sql .= ' GROUP BY '.$this->_group;
			if (!empty($this->_order))
				$sql .= ' ORDER BY '.$this->_order;
			if (!empty($this->_limit))
				$sql .= ' LIMIT '.$this->_limit;
			$sql .= ';';
			return $sql;
		}
		
		/**
		* private function insert_sql
		*
		* Строит запрос для insert'а
		*
		* @return string
		*/
		private function insert_sql() {
			$sql = 'INSERT INTO '.$this->_table.' ';
			$sql .= '(';
			$sql .= join(', ', 
				array_map(
					function($val) {
						return '`'.$val.'`';
					},
					array_keys($this->_inserts)
				)
			);
			$sql .= ') VALUES (';
			$sql .= join(
				', ', 
				array_map(
					function($value) {
						return \DB\DB::instance()->escape($value);
					}, 
					$this->_inserts
				)
			);
			$sql .= ')';
			//если указан update, строим конструкцию on duplicate key update
			if (empty($this->_updates))
				return $sql.';';
			$sql .= ' ON DUPLICATE KEY UPDATE (';
			foreach ($this->_updates as $field=>$values) {
				$sql .= $field.' = '.$this->_connection->escape($values).', ';
			}
			$sql = substr($sql, 0, -2);
			$sql .= ');';
			return $sql;
		}
		
		/**
		* private function update_sql
		*
		* Строит запрос для update'а
		*
		* @return string
		*/
		private function update_sql() {
			$sql = 'UPDATE '.$this->_table.' SET ';
			foreach ($this->_updates as $field=>$values) {
				$sql .= '`'.$field.'`'.' = ';
				if ($this->_system === false)
					$sql .= $this->_connection->escape($values);
				else
					$sql .= $values;
				$sql .= ', ';
			}
			$sql = substr($sql, 0, -2);
			$sql .= '';
			if (!empty($this->_where))
				$sql .= ' WHERE '.$this->_where;
			if (!empty($this->_order))
				$sql .= ' ORDER '.$this->_order;
			if (!empty($this->_limit))
				$sql .= ' LIMIT '.$this->_limit;
			$sql .= ';';
			return $sql;
		}

		private function change_fields($fields, $difference) {
			if (!is_array($fields))
				$fields = array($fields);			
			$this->_action = 'update';
			$this->_system = true;
			$this->updates = array();
			foreach ($fields as $field)
				$this->_updates[$field] = '`'.$field.'` '.$this->_sign.' '.$difference;
			return $this;
		}

		private function flush() {
			$this->_fields = '*';
			$this->_where = '';
			$this->_group = '';
			$this->_order = '';
			$this->_limit = '';
			$this->_join = '';
			$this->_inserts = '';
			$this->_updates = '';
			$this->_action = '';
			$this->_system = false;
			$this->_sign = '';
		}
	}
