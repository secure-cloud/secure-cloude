<?php
/**
 * Класс для оперативного тестирования новых моделек и некоторого функционаля
 */
class TestController extends \Abstracts\Controller{
	public function test_action(){
		$redis = new \Cache\Redis();
		$result = $redis->sadd("myset","set1")->exec();
		$result = $redis->smembers("myset")->exec();
	}
}
