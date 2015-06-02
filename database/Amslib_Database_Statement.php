<?php
class Amslib_Database_Statement extends PDOStatement {
    public $dbh;
    
    protected function __construct($dbh)
    {
    	$this->dbh = $dbh;
    }
}