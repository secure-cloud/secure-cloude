<?php
/**
 * Контроллер пользователя.
 * @author Константин Макарычева
 * @modify Vishin Pavel
 */
class UserController extends \Abstracts\Controller{
	public function registration_action() {
		try {
			if (!isset($this->post->mail) ||
				!$this->helper->validator->email($this->post->mail))
				throw new Exception('Mail is missing or incorrect');
			if (!isset($this->post->password) || empty($this->post->password))
				throw new Exception('Password is missing');
			if (!isset($this->post->passphrase))
				throw new Exception('Passphrase is missing or incorrect');
			$fields = array('mail', 'password');
			$values = array(
				$this->post->mail,
				$this->helper->string->salted_hash($this->post->password),
			);

			$this->model->user->set_user_param($fields, $values);
			$result = $this->model->user->save_user();
			if ($result < 0)
				throw new Exception(\DB\DB::instance()->last_error());
			elseif ($result == 0)
				throw new Exception('User already registered');
			$sendMail = new System\SendMail;
			$sendMail->from("noreply@secure-cloud.com")
					 ->to($this->post->mail)
					 ->subject("Регистрация на secure cloud")
					 ->message("Thanks for registration. Your passphrase is: ".$this->post->passphrase)
					 ->send();
			$this->view->json()->render('', array('status'=>'ok'));

		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}
	}

	public function delete_action(){
		try{
			if (!isset($this->post->mail) ||
				!$this->helper->validator->email($this->post->mail))
				throw new Exception('Mail is missing or incorrect');
			if(!$this->model->user->delete_by('mail', $this->post->mail))
				throw new Exception("Can't delete user");
			$this->view->json()->render('', array('status'=>'ok'));
		}
		catch(Exception $e){
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}
	}

	public function restore_password_action() {
		try {
			if (!isset($this->post->mail) ||
				!$this->helper->validator->email($this->post->mail))
				throw new Exception('Mail is missing or incorrect.');
			$user = $this->model->user->get_user_by('mail', $this->post->mail);
			if ($user === NULL)
				throw new Exception('User not found. Sadness.');
			if (isset($this->post->check) && $this->post->check) {
				if (!$this->model->user->key)
					throw new Exception('Key does not exist');
				$key = $this->model->user->key;
				$this->model->user->set_user_param('key', '')->save_user();
				return $this->view->render('', array('key'=>$key));
			}
			$key = $this->helper->api->unique_key($this->post->mail);
			$result = $this->model->user
				->set_user_param('key', $key)
				->save_user();
			if ($result < 1)
				throw new Exception('Nothing was changed');
			$this->view->render('', array('key'=>$key));
		} catch (Exception $e) {
			$this->view->render('', array('error'=>$e->getMessage()));
		}
	}

	public function auth_action() {
		try {
			if (!isset($this->post->mail) ||
				!$this->helper->validator->email($this->post->mail))
				throw new Exception('Mail is missing or incorrect');
			if (!isset($this->post->password))
				throw new Exception('Password is not specified');

			$result = $this->model->user->get_user_by('mail', $this->post->mail);

			if ($result === NULL)
				throw new Exception('User not found. Sadness.');

			if ($this->model->user->password != $this->helper->string->salted_hash($this->post->password))
				throw new Exception('Password is wrong');
			$this->view->json()->render('', array('status'=>'ok','id'=>$this->model->user->id));
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}
	}

	public function info_action() {
		try {
			$params = $this->fields();
			$user = $params['user'];
			$fields = $params['fields'];
			unset($params);
			$result = array();
			foreach ($fields as $field) {
				$result[$field] = $user->$field;
			}
			$this->view->json()->render('', $result);
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}
	}

	public function edit_action() {
		try {
			$params = $this->fields();
			$user = $params['user'];
			$fields = $params['fields'];
			unset($params);
			if (isset($fields['password']))
				$fields['password'] = $this->helper->string->salted_hash($fields['password']);
			$user->set_user_param(array_keys($fields), array_values($fields));
			$result = $user->save_user();
			if ($result < 0)
				throw new Exception(\DB\DB::instance()->last_error());
			elseif ($result == 0)
				throw new Exception('Nothing was changed');
			$this->view->json()->render('', array('status'=>'ok'));
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}
	}

	public function _pre_action() {
		try {
//			if (!isset($this->post->check_sum)) //ToDo РАскомментировать. Установлено только на время тестирования системы
//				throw new Exception('Checksum is missing');
//			$checkresult = $this->helper->api->check_sum(
//				(array)$this->post
//			);
			$checkresult = true; //ToDo убрать строчку. Установлена только на время тестирования системы
			if ($checkresult === false)
				throw new Exception('Checksum is corrupted');
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
			exit;
		}
	}

	private function fields() {
		if (!isset($this->post->id) ||
			!is_numeric($this->post->id))
			throw new Exception('User id is missing or incorrect');
		if (!isset($this->post->fields))
			throw new Exception('User fields are not specified');
		$user = $this->model->user->get_user_by('id', $this->post->id);
		if ($user === NULL)
			throw new Exception('User not found. Sadness.');
		$fields = json_decode($this->post->fields, true);
		if ($fields === NULL)
			throw new Exception('User fields are incorrect. I need json!');
		return array(
			'fields' => $fields,
			'user' => $user
		);
	}
}