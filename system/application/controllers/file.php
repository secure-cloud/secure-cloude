<?php
	/**
	 * Контроллер файлов.
	 * @author Константин Макарычева
	 * @modify Vishin Pavel
	 */
class FileController extends \Abstracts\Controller{
	public function savefile_action(){
		if (!isset($this->post->userid) ||
			!is_numeric($this->post->userid))
			throw new Exception('Incorrect user ID');
		if (!isset($this->post->filepath))
			throw new Exception("Please set file's Path");
		if (!isset($this->post->filename))
			throw new Exception("Please set file's name");
		$this->model->file->save_file($this->post->userid,
									  $this->post->filepath,
									  $_FILES['file']['tmp_name'],
									  2);
	}
	public function loadfile_action(){
		if (!isset($this->post->userid) ||
			!is_numeric($this->post->userid))
			throw new Exception('Incorrect user ID');
		if (!isset($this->post->filepath))
			throw new Exception("Please set user's file Path");
		if (!isset($this->post->filename))
			throw new Exception("Please set file's name");

	}


}
