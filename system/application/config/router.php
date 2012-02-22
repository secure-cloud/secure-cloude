<?php
	/**
	* Конфигурация маршрутизатора
	*
	* array(
	*   'RouterName' =>
	*     'regexp' => регексп, описывающий запрос
	*     'run'    => внутренний маршрут 'controller/action/param1/param2..paramN'
	*     'layout' => шаблон по умолчанию, false для отключения шаблона
	*     'view'   => тип вьюшки по умолчанию
	* )
	*/
	return array(
		'UserRegistration' => array(
			'regexp' => 'api/user/registration',
			'run' => 'user/registration',
			'layout' => false,
			'view' => 'json'
		),
		'UserAuth' => array(
			'regexp' => 'api/user/auth',
			'run' => 'user/auth',
			'layout' => false,
			'view' => 'json'
		),
		'UserInfo' => array(
			'regexp' => 'api/user/info',
			'run' => 'user/info',
			'layout' => false,
			'view' => 'json'
		),
		'UserEdit' => array(
			'regexp' => 'api/user/edit',
			'run' => 'user/edit',
			'layout' => false,
			'view' => 'json'
		),
		'ContactAdd' => array(
			'regexp' => 'api/user/contacts/add',
			'run' => 'user/add_contact',
			'layout' => false,
			'view' => 'json'
		),
		'RestorePassword' => array(
			'regexp' => 'api/user/restore/password',
			'run' => 'user/restore_password',
			'layout' => false,
			'view' => 'json'
		),
		'DeleteUser' => array(
			'regexp' => 'api/user/delete',
			'run' => 'user/delete',
			'layout' => false,
			'view' => 'json'
		),
		'Test' => array(
			'regexp' => 'test/test',
			'run' => 'test/test',
			'layout' => false,
			'view' => 'json'
		)
	);