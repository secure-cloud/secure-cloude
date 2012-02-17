<?php
	/**
	 * function check_sum
	 * 
	 * проверяет контрольную сумму параметров
	 * 
	 * @param array $params параметры для проверки
	 * @param int $partner_id идентификатор партнера, т.к. для каждого разная соль
	 */
	function check_sum(array $params) {
		if (!isset($params['check_sum']))
			return false;
		$sum = $params['check_sum'];
		unset($params['check_sum']);
		$checksum = compute_sum($params);
		return $checksum !== false && $checksum == $sum;
	}
	
	/**
	 * function compute_sum
	 *
	 * вычисляет контрольную сумму параметров
	 * @param array $params
	 * @return string
	 */
	function compute_sum(array $params) {
		ksort($params);
		$paramstr = '';
		foreach ($params as $param)
			$paramstr .= $param;
		return md5(sha1($paramstr)."banana_king");
	}
	
	/**
	 * function unique_key
	 * 
	 * создает уникальный ключ на основе email
	 * 
	 * @param text $email
	 * @return string
	 */
	function unique_key($email) {
		return md5(sha1($email).mktime(true));
	}