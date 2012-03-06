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
	public function dir_remove_action(){
		try{
			if (!isset($this->post->userid) ||
				!is_numeric($this->post->userid))
				throw new Exception('Incorrect user ID');
			if (!isset($this->post->dirpath))
				throw new Exception("Please set file's Path");
			if (!isset($this->post->direname))
				throw new Exception("Please set name of directory");

			$this->model->directory->dir_remove($this->post->userid, $this->post->dirpath, $this->post->direname);
			$this->view->json()->render('', array('status'=>'ok'));
		} catch (Exception $e) {
			$this->view->json()->render('', array('error'=>$e->getMessage()));
		}
	}
}
