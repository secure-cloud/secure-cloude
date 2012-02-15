<?php
	return array(
		'route'=>array(
			'default'=>'index/index',
			'config'=>DIR_ROOT.'system/application/config/router.php'
		),
		'db'=>array(
			'host'=>'localhost',
			'user'=>'root',
			'password'=>'root',
			'database'=>'vknige'
		),
		'js'=>array(
			'default'=>array(
				'script.js',
			),
			'path'=>DIR_ROOT.'public/js/',
			'cache'=>DIR_ROOT.'public/js/cache/',
			'include'=>'/js/cache/'
		),
		'css'=>array(
			'default'=>array(
				'style.css',
				'main.css'
			),
			'path'=>DIR_ROOT.'public/css/',
			'cache'=>DIR_ROOT.'public/css/cache/',
			'include'=>'/css/cache/'
		),
		'layout'=>array(
			'default'=>'main',
			'path'=>DIR_ROOT.'system/application/templates/layouts/'
		),
		'viewpath'=>DIR_ROOT.'system/application/templates/',
		'log'=>array(
			'path'=>DIR_ROOT.'system/application/logs/',
			'template'=>array(
				'default'=>"[{date} {time}]\n{text}\n\n"
			)
		),
		'cache'=>array(
			'memcache'=>array(
				'address'=>'localhost',
				'port'=>'11211'
			)
		),
		'filetransfer'=>array(
			'localtmp'=>DIR_ROOT.'tmp/filetransfer/',
			'serverpath'=>'/var/secure-cloud/',
			'webserver'=>'localhost'

		)

	);