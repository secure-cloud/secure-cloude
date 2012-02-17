<?php
	/**
	 * funсtion email
	 * 
	 * проверяет правильность email
	 * 
	 * @param type $mail строка для проверки
	 * @return boolean
	 */
	function email($mail) {
		return preg_match('/^[-.\w]+@(?:[a-z\d][-a-z\d]+\.)+[a-z]{2,6}$/', $mail) == 1;
	}