<?php
/**
 * Класс для оперативного тестирования новых моделек и некоторого функционаля
 */
class TestController extends \Abstracts\Controller{
	public function test_action(){
		$redis = new \Cache\Redis('81.17.140.102','6379');
		$result = $redis->sadd("3/2e26d345f370672ca0f8916422dd2717","yu7r6+Dv8V/i7uvt7uLu6V/08+3q9ujoLw==")->exec();

		$result = NULL;
	}
}
