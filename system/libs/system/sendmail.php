<?php
/**
 * Замечательный класс, который должен... Теоретически... Отправлять почту. <br>
 * Работает примерно так:
 * $mail = new SendMail
 * 			->message($message)
 * 			->to("ivan@ivanov.ru")
 * 			->from("petrov@petr.ru")
 * 			->subject("Test")
 * 			->send();
 */
namespace System;
class SendMail{
	private $encoding 	="utf8";
	private $message  	= NULL;
	private $to 	 	= NULL;
	private $from 		= NULL;
	private $subject 	= NULL;
	private $headers 	= array("Content-type: text/html; charset=utf8");

	public function encoding($encoding){
		$this->encoding = $encoding;
		return $this;
	}
 /**
 * Функция задает сообщение для отправки
 * @param $message
 * @return SendMail
 */
	public function message($message){
		$this->message = $message;
		return $this;
	}
/**
 * Функция задает получателя
 * @param $to
 * @return SendMail
 */
	public function to($to){
		$this->to = $to;
		return $this;
	}
/**
 * Фнкция задает отправителя
 * @param $from
 * @return SendMail
 */
	public function from($from){
		$this->from = $from;
		return $this;
	}
/**
 * Функция задает тему письма
 * @param $subject
 * @return SendMail
 */
	public function subject($subject){
		$this->subject = $subject;
		return $this;
	}
/**
 * Функция задает дополнительные заголовки
 * @param array $headers
 * @return SendMail
 */

	public function headers($headers = array()){
		$this->headers = $headers;
		return $this;
	}
/**
 * Возвращает значения заданных параметров класса, включая заголовки
 * @param $name
 * @return bool
 */
	public function __get($name){
		if(isset($this->$name))
			return $this->$name;
		elseif(isset($this->headers[$name]))
			return $this->headers[$name];
		else
			return false;
	}
	/**
	 * Функция отправляет сформерованное письмо и вощвращает true или false в зависимости от результата
	 *
	 * @param string $tpl
	 * @param null $layout
	 * @param bool $html
	 * @return bool
	 */
	public function send($tpl="mail", $layout = NULL, $html=true){
		if($html){
			$this->prepare_message($tpl);
		}
		$this->prepare_to();
		$this->prepare_headers();
		return mail($this->to,$this->subject,$this->message,$this->headers);
	}
	/**
	 * готовит сообщение к отправке(погружает в шаблонец)
	 * @param $tpl
	 */
	private function prepare_message($tpl){
		$view = new \Abstracts\View;
		$this->message = $view->to_string($tpl, array("message"=>$this->message));
		//$this->message = htmlspecialchars(stripslashes(trim($this->message)));

	}
	/**
	 * готовим тему письма
	 */
	private function prepare_subject(){
		$this->subject = htmlspecialchars(stripslashes(trim($this->subject)));
	}
	/**
	 * если был передан массив получателей,
	 */
	private function prepare_to(){
		if(!is_scalar($this->to))
		$this->to = join(",",$this->to);
	}
	private function prepare_headers(){
		//ToDo Добавить проверку: В хедаре максимальная строка -- 70 символов, потом "\n"
		$this->headers = join("\r\n",$this->headers);
	}
}