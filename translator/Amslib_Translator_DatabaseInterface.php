<?php 
class Amslib_Translator_DatabaseInterface extends Amslib_Translator_BaseAccessLayer
{
	var $database;

	function Amslib_Translator_DatabaseInterface()
	{
		parent::Amslib_Translator_BaseAccessLayer();

		$this->database = NULL;
	}

	function open($database,$readAll=false)
	{
		$this->database = $database;
	}

	function close()
	{
		$this->database = NULL;
	}

	function sync()
	{
		$this->__syncMode = $this->__SYNC;
	}

	function async()
	{
		$this->__syncMode = $this->__ASYNC;
	}

	function listAll($language)
	{
		$list = $this->database->listAll($language);
		return $list;
	}

	function t($input,$language=NULL)
	{
		return $this->database->t($input,$language);
	}

	function l($input,$translation,$database=NULL)
	{
		$this->database->l($input,$translation,$database);
	}

	function f($input,$database=NULL)
	{
		$this->database->f($input,$database);
	}

	function getMissing()
	{
		$this->database->getMissing();
	}

	function updateKey($old,$new,$deleteOld=true)
	{
		//	TODO: What does this do???
	}
}