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
			if (!isset($this->post->filepath))
				throw new Exception("Please set file's Path");
			if (!isset($this->post->filename))
				throw new Exception("Please set name of file");
			$result = $this->model->file->start_stream($this->post->userid, $this->post->filepath, $this->post->filename);
			echo $result['file'];
			//$this->view->json()->render('', array('status'=>'ok','file'=>$result['file'],'EOF'=>$result['EOF']));
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}

	}
	public function stream_next_action(){
		try{
			if (!isset($this->post->userid) ||
				!is_numeric($this->post->userid))
				throw new Exception('Incorrect user ID');
			$result = $this->model->file->next_part($this->post->userid);
			header("Content-Type: application/octet-stream");
			echo $result['file'];
		//	$this->view->json()->render('', array('status'=>'ok','file'=>$result['file'],'EOF'=>$result['EOF']));
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
