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
		'Registration'=>array(
			'regexp' => 'user/registration',
			'path' => 'user/registration',
			'view' => 'json'
		)
	);
