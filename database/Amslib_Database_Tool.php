<?php
/**
 * A tool class to group together common functionality which is not core database functions, but tools to use in special situations
 *
 * User: Chris Thomas
 * Date: 22/09/2015
 * Time: 11:36
 */
class Amslib_Database_Tool
{
    protected $database;

    public function __construct($database)
    {
        if(!$database instanceof Amslib_Database){
            throw new Exception(__CLASS__.", can only be constructed with a Amslib_Database object as it's parameter");
        }

        $this->database = $database;
    }
    /**
     * 	method:	fixColumnEncoding
     *
     * 	todo: write documentation
     *
     * 	note: this method isn't really mysql specific, apart from calling select, escape and update, these could be API specific but they are abstracted anyway
     */
    public function fixColumnEncoding($src,$dst,$table,$primaryKey,$column)
    {
        $encoding_map = array(
            "latin1"	=>	"latin1",
            "utf8"		=>	"utf-8"
        );

        if(!isset($encoding_map[$src])) return false;
        if(!isset($encoding_map[$dst])) return false;

        $this->database->setEncoding($src);
        $data = $this->database->select("$primaryKey,$column from $table");

        if(!empty($data)){
            $this->database->setEncoding($dst);

            $ic_src = $encoding_map[$src];
            $ic_dst = $encoding_map[$dst];

            foreach($data as $c){
                $string = iconv($ic_src,$ic_dst,$c[$column]);

                if($string && is_string($string) && strlen($string)){
                    $string = $this->database->escape($string);

                    $this->database->update("$table set $column='$string' where $primaryKey='{$c[$primaryKey]}'");
                }
            }

            return true;
        }
    }
}