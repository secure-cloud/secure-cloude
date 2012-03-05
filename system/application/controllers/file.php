<?php
	/**
	 * Контроллер файлов.
	 * @author Константин Макарычева
	 * @modify Vishin Pavel
	 */
class FileController extends \Abstracts\Controller{
	public function save_action(){
		try{
			if (!isset($this->post->userid) ||
				!is_numeric($this->post->userid))
				throw new Exception('Incorrect user ID');
			if (!isset($this->post->filepath))
				throw new Exception("Please set file's Path");
			$isSave = $this -> model -> file->save_file($this->post->userid,
											  $this->post->filepath,
											  $_FILES['file']['name'],
											  $_FILES['file']['tmp_name'],
											  $_FILES['file']['size'],
												$this->post->hash,
												$this->post->timestamp,
											  2);
			$this->view->json()->render('', array('status'=>'ok'));
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}
	}
	public function stream_start_action(){
		try{
			if (!isset($this->post->userid) ||
				!is_numeric($this->post->userid))
				throw new Exception('Incorrect user ID');
			$this->model->user->get_user_by('id','4');
			$this->model->user->set_user_param('rootdir', '0')->save_user();
			$file = file_get_contents('file1');
			$this->view->json()->render('', array('status'=>'ok','file'=>$file,'EOF'=>false));
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}

	}
	public function stream_next_action(){
		try{
			if (!isset($this->post->userid) ||
				!is_numeric($this->post->userid))
				throw new Exception('Incorrect user ID');

			$this->model->user->get_user_by('id','4');
			switch($this->model->user->rootdir){
				case 0:
					$this->model->user->set_user_param('rootdir', '1')->save_user();
					$file = file_get_contents('file2');
					$this->view->json()->render('', array('status'=>'ok','file'=>$file,'EOF'=>false));
						break;
				case 1:
					$this->model->user->set_user_param('rootdir', '2')->save_user();
					$file = file_get_contents('file3');
					$this->view->json()->render('', array('status'=>'ok','file'=>$file,'EOF'=>false));
					break;
				case 2:
					$file = file_get_contents('file4');
					$this->view->json()->render('', array('status'=>'ok','file'=>$file,'EOF'=>true));
					break;
				default: break;
			}
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}
	}
	public function stream_current_action(){
		try{
			if (!isset($this->post->userid) ||
				!is_numeric($this->post->userid))
				throw new Exception('Incorrect user ID');
			if (!isset($this->post->filepath))
			$this->model->file->send_file($this->post->userid);
			$this->view->json()->render('', array('status'=>'ok'));
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}

	}



}
