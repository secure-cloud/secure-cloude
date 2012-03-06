<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pavel
 * Date: 27.02.12
 * Time: 10:02
 *
 */
class DirectoryController extends \Abstracts\Controller{
	public function  getcontent_action(){
		if (!isset($this->post->userid) ||
			!is_numeric($this->post->userid))
			throw new Exception('Incorrect user ID');
		if (!isset($this->post->path))
			throw new Exception("Please set user's file Path");
		$dircontent = $this->model->directory->get_content($this->post->userid,
														   $this->post->path);
		$this->view->json()->render('', array('status'=>'ok','dircontent'=>$dircontent));
	}
	public function  make_action(){
		if (!isset($this->post->userid) ||
			!is_numeric($this->post->userid))
			throw new Exception('Incorrect user ID');
		if (!isset($this->post->path))
			throw new Exception("Please set user's file Path");
		$dircontent = $this->model->directory->save_path($this->post->userid,
			$this->post->path);
		$this->view->json()->render('', array('status'=>'ok','dircontent'=>$dircontent));
	}
}
