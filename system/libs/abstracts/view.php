<?php
	namespace Abstracts;
	/**
	 * abstract class View
	 * 
	 * класс абстрактной вьюшки: умеет рендерить, склеивать статичные js и css
	 * 
	 * @author Константин Макарычев
	 */
	class View {
		/**
		 * public css
		 * 
		 * @var array хранит подключенные css'ки
		 */
		public $css = array();
		/**
		 * public js
		 * 
		 * @var array хранит подключенные js-скрипты
		 */
		public $js = array();
		/**
		 * public mode
		 * 
		 * @var string текущий вид отображения (напр. xml, html, json)
		 * по умолчанию html
		 */
		public $mode = 'html';
		/**
		 * public layout
		 *
		 * @var mixed текущий layout
		 * пустая строка - layout по умолчания из конфига
		 * false - не рендерить layout
		 * все остальное - название layout'а
		 */
		public $layout = '';
		
		public function __construct() {
		}
		
		/**
		 * public function render
		 * 
		 * рендерит вьюшку с layout'ом
		 * 
		 * @param string $fileofview1234 путь к файлу вьюшки. странное название
		 * @param array $params массив параметров для вьюшки
		 */
		public function render($fileofview1234, $params = array()) {
			if ($this->mode == 'json')
				return $this->render_json($params);
			//извлекаем переменные из $params
			//поэтому название первого параметра такое странное, чтобы не было
			//коллизий при извлечении
			extract($params);
			ob_start();
			//подключаем нужный файл вьюшки, а результат записываем в $content,
			//который, теоретически, выводится в layout
			include(\System\Config::instance()->viewpath.$fileofview1234.'.'.$this->mode);
			$content = ob_get_contents();
			ob_end_clean();
			//в зависимости от $this->mode может быть контент разного типа, т.ч.
			//заголовки не помешают
			$this->header('Content-Type: text/'.$this->mode);
			$this->render_layout($content);
		}
		
		/**
		 * public function render_partial
		 * 
		 * рендерит только вьюшку
		 * 
		 * @param string $fileofview1234 файл вьюшки
		 * @param array $params параметры вьюшке
		 */
		public function render_partial($fileofview1234, $params = array()) {
			if ($this->mode == 'json')
				return $this->render_json($params);
			$this->header('Content-Type: text/'.$this->mode);
			extract($params);
			include(\System\Config::instance()->viewpath.$fileofview1234.'.'.$this->mode);
		}
		
		/**
		 * public function render_text
		 * 
		 * рендерит только текст
		 * 
		 * @param string $content текст для рендеринга
		 */
		public function render_text($content) {
			$this->render_layout($content);
		}
		
		/**
		 * public function to_string
		 * 
		 * рендерит вьюшку в строку
		 * 
		 * @param string $view файл вьюшки
		 * @param array $params параметры вьюшке
		 * @return string
		 */
		public function to_string($view, $params) {
			ob_start();
			$this->render($view, $params);
			$result = ob_get_contents();
			ob_end_clean();
			return $result;
		}
		
		/**
		 * public function html
		 * 
		 * устанавливает тип отображения в html
		 * 
		 * @return View 
		 */
		public function html() {
			$this->mode = 'html';
			return $this;
		}
		
		/**
		 * public function xml
		 * 
		 * устанавливает тип отображения в xml
		 * 
		 * @return View 
		 */
		public function xml() {
			$this->mode = 'xml';
			return $this;
		}
		
		/**
		 * public function json
		 * 
		 * устанавливает тип отображения в json
		 * 
		 * @return View 
		 */
		public function json() {
			$this->mode = 'json';
			return $this;
		}
		
		/**
		 * public function header
		 * 
		 * отправляет заголовки, алиас для header()
		 * @link http://ru.php.net/manual/en/function.header.php
		 * 
		 * @param string $header заголовок
		 */
		public function header($header) {
			header($header);
		}
		
		/**
		 * public function js
		 * 
		 * сливает, кэширует и возвращает путь к js-скриптам
		 * 
		 * @return string абсолютный путь к файлу кэша
		 */
		protected function js() {
			return $this->static_cache('js');
		}
		
		/**
		 * public function css
		 * 
		 * сливает, кэширует и возвращает путь к css'кам
		 * 
		 * @return string абсолютный путь к файлу кэша
		 */
		protected function css() {
			return $this->static_cache('css');
		}
		
		/**
		 * public function static_cache
		 * 
		 * обобщенный метод для js() и css()
		 * 
		 * @param string $type тип файлов
		 * @return string
		 */
		protected function static_cache($type) {
			//объединим динамически подгруженные файлы и общие из конфига
			$config = \System\Config::instance()->$type;
			$files = array_merge($config['default'], $this->$type);
			$cachefile = md5(join('', $files)).'.'.$type;
			$filename = $config['cache'].$cachefile;
			if (is_file($filename))
				return $config['include'].$cachefile;
			touch($filename);
			//просто сливаем все в один файл
			foreach ($files as $file) {
				$path = $config['path'].$file;
				if (is_file($path))
					file_put_contents($filename, file_get_contents($path).PHP_EOL, FILE_APPEND);
			}
		}

		private function render_json($params) {
			$this->header('Content-Type: application/json');
			echo json_encode($params);
		}
		
		private function render_layout($content) {
			if ($this->layout === FALSE)
				echo $content;
			elseif ($this->layout === '')
				include(\System\Config::instance()->layout['path'].\System\Config::instance()->layout['default'].'.'.$this->mode);
			else
				include(\System\Config::instance()->layout['path'].$this->layout.'.'.$this->mode);
		}
	}
