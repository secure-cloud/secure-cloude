<?php
	/**
	 * function salted_hash
	 * 
	 * солит и хеширует строку 
	 * 
	 * @param string $str входная строка
	 * @return string
	 */
	function salted_hash($str) {
		return md5(sha1($str).'Nja(:20soOnHJ^_^1');
	}