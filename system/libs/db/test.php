<?php
	include('../system/singletone.php');
	include('db.php');
	include('mysql.php');
	
	\DB\DB::instance()->connect('localhost', 'root', 'root', 'vknige');
	
	$m = new \DB\MySQL('table');
	echo $m
		->select()
		->join('table2')->on('t1_id', 't2_id')
		->join('table3')->on('table2.id', 'another_id')
		->where('id = ?', 2)
		->group('field', \DB\MySQL::DESC)
		->order('field2')
		->limit(2, 1)
		->sql().PHP_EOL;
	//SELECT * FROM table JOIN table2 ON table.t1_id = table2.t2_id JOIN table3 ON table2.id = table3.another_id WHERE id = 2 GROUP BY field DESC ORDER BY field2 ASC LIMIT 2, 1;
	
	echo $m
		->insert(array(
			'field1'=>'value1',
			'field2'=>'value2',
			'field3'=>'value3'
		))->update('field4', 'value4')
		->sql().PHP_EOL;
	//INSERT INTO table (field1, field2, field3) VALUES ("value1", "value2", "value3") ON DUPLICATE KEY UPDATE (field4 = "value4");
	
	echo $m
		->update('field', 'value')
		->where('id = ?', 'bla')
		->sql().PHP_EOL;
	//UPDATE TABLE table SET (field = "value") WHERE id = "bla";

	
	echo $m
		->select_by_id(2)
		->sql().PHP_EOL;
	//SELECT * FROM table WHERE id = 2 LIMIT 0, 1;
