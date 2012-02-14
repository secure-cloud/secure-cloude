<?php
/**
 * Моделька пользователя
 * @author Vishin Pavel
 *
 */
class UserModel implements IModel{
	/**
	 * Служебная запись для работы Сеттеров
	 * @var array
	 */
	protected $editableUser = array(
	);

	protected $userStack;
	/**
	 * Если указан email и пароль для авторизации, то авторизует и пишет в
	 * сессию и в печеньки. Если не  указан, то пытается авторизовать по сессии,
	 * а потом и по печенькам.
	 *
	 * @param null $mail
	 * @param null $password
	 * @return bool
	 */
	public function login ($mail = NULL,$password=NULL){ //ToDo: Пока работает с открытыми паролями. Нужно засолить и заМДпятировать
		$session = new \System\Session;
		$cookie = new \System\Cookie;
		$usersDB = new \DB\MySQL('users');
		if($mail && $password){
			$user = $usersDB
				->select_by_mail($mail)
				->first();
			if($user && $password==$user['password']){
				$session->email = $user['email'];
				$session->password = $user['password'];
				$session->userId = $user['id'];

				$cookie->set('email', $user['email'], 3600);
			}

			return true;
		}
		elseif($session->email){
			$user = $usersDB
				->select_by_mail($session->email)
				->first();
			$cookie->set('email', $user['email'], 3600);
			return true;
		}

		return false;
	}
	/**
	 * Эмм... Ну... Да, это -- та самая функция...<br />
	 * Зануляет сессию, убивает выпечку.
	 */
	public function logout(){
		$session = new \System\Session;
		$cookie = new \System\Cookie;
		$session->mail = NULL;
		$session->userId = NULL;
		$session->password = NULL;

		$cookie->set('email', NULL, -1);
	}
	/**
	 * Волшебник __Гет() такой добрый, что всегда вернет тебе параметр текущего пользователя, который ты попросишь
	 * @param $name
	 * @return mixed
	 */
	public function __get($name){
		return $this->get_user_param($name);
	}
	/**
	 * возвращает массив данных пользователей из списка контатов.<br>
	 * Все параметры не обязательны.<br>
	 * Если параметр $userId не указан, то выводятся контакты текущего пользователя.
	 * Если не указаны Лимиты, то выводятся все контакты
	 * @param null $from
	 * @param null $limit
	 * @param null $userId
	 * @return array
	 */
	public function get_contacts($userId=NULL,$from=NULL,$limit=NULL){
		$currentContactsDB = new \DB\MySQL('contacts');
		$session = new \System\Session;
		$userId=$userId?:$session->user_id; //Если указан юзер ID, то используем его. в противном случае берем из сессии
		$currentContactsDB->select_by_user_id($session->$userId);
		if($from && $limit){
			$currentContactsDB->limit($from, $limit);
		}
		return $currentContactsDB->all();

	}
	/**
	 * Функция получает данные о пользователе и кладет их в массивчик для дальнейшей
	 * обработки данных. Функции передается два параметра: имя параметра для выборки и значение.
	 *
	 * @param $paramType
	 * @param $paramValue
	 * @return UserModel|null
	 */
	public function get_user_by($paramType, $paramValue){
		$this->editableUser=array();
		$currentUserDB = new \DB\MySQL('users');
		$user=$currentUserDB
			->select()
			->where($paramType.' = ?', $paramValue)
			->first();
		if ($user){
			foreach($user as $rowName => $value){
				$this->editableUser[$rowName] = $value;
			}
			return $this;
		}
		else return NULL;
	}



	/**
	 * Функция отчищает запись редактируемого пользователя, чтобы создать нового
	 */
	public function  get_new_user(){
		$this->editableUser=array();
	}

	/**
	 * Функция устанавливает в запись редактируемого пользователя некоторое значения.
	 * Первый параметр -- имя параметра. Второй -- значение параметра
	 *
	 * @param $paramType
	 * @param $paramValue
	 * @return UserModel
	 */
	public function set_user_param($paramType, $paramValue){
		if(!is_scalar($paramType) && !is_scalar($paramValue)){
			foreach($paramType as $key => $name){
				$this->editableUser[$name] = $paramValue[$key];
			}
			return $this;
		}
		elseif(is_scalar($paramType) && is_scalar($paramValue))
			$this->editableUser[$paramType] = $paramValue;
		return $this;
	}
	/**
	 * Геттер для параметров текущего ползователя.
	 * @param $paramType
	 * @return mixed
	 */
	public function get_user_param($paramType){
		if(isset($this->editableUser[$paramType])){
			return $this->editableUser[$paramType];
		}
		else return NULL;
	}

	/**
	 * Функция сохраняет параметры текущего пользователя в базу
	 * Если в системной записи указан ID пользователя, то обнвляется соответствующая
	 * запись в базе, в противном случае создается пользователь с соответствуюющими параметрами
	 */
	public function save_user(){
		$currentUserDB = new \DB\MySQL('users');
		$update=array();
		if($this->editableUser['id']){
			foreach($this->editableUser as $rowName => $value){
				if ($rowName!='id'){
					$update[$rowName]=$value;
				}
			}
			return $currentUserDB->update($update)->exec();
		}
		else{
			foreach($this->editableUser as $rowName => $value){
				$update[$rowName]=$value;
				return $currentUserDB->insert($update)->exec();
			}
		}
		return NULL;
	}
	/**
	 * Устанавливает данные текущего системного пользователя в массивчик пользователя.
	 * И возвращает самого себя.
	 * @return UserModel
	 */
	public function system_user(){
		$this->editableUser=array();
		$currentUserDB = new \DB\MySQL('users');
		$session = new System\Session;
		$user=$currentUserDB
			->select_by_mail($session->mail)
			->first();
		if($user){
			foreach($user as $rowName => $value){
				$this->editableUser[$rowName] = $value;
			}
			return $this;
		}
		return NULL;
	}

	function new_inst(){
		return new self;
	}
}
