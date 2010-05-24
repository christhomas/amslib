<?php 
class Amslib_Translator_Database extends Amslib_Translator
{
	protected $database;

	public function __construct()
	{
		parent::__construct();

		$this->database = NULL;
	}

	public function open($database,$readAll=false)
	{
		$this->database = $database;
	}

	public function close()
	{
		$this->database = NULL;
	}

	public function sync()
	{
		$this->__syncMode = $this->__SYNC;
	}

	public function async()
	{
		$this->__syncMode = $this->__ASYNC;
	}

	public function listAll($language)
	{
		if(!$this->database) return false;
		
		return $this->database->listAll($language);
	}

	public function t($input,$language=NULL)
	{
		if(!$this->database) return false;
		
		return $this->database->t($input,$language);
	}

	public function l($input,$translation,$database=NULL)
	{
		if(!$this->database) return false;
		
		$this->database->l($input,$translation,$database);
	}

	public function f($input,$database=NULL)
	{
		if(!$this->database) return false;
		
		$this->database->f($input,$database);
	}

	public function getMissing()
	{
		if(!$this->database) return false;
		
		$this->database->getMissing();
	}

	public function updateKey($old,$new,$deleteOld=true)
	{
		//	TODO: What does this do???
	}
}