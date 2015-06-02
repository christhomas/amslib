<?php
class Amslib_Database_Statement extends PDOStatement {
    public $dbh;
    
    protected function __construct($dbh)
    {
    	Amslib_Debug::log(__CLASS__.", created");
    	$this->dbh = $dbh;
    }
}