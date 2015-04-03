<?php
require_once('simpletest/autorun.php');
require_once('../api/vendor/amstudios/amslib/file/Amslib_File.php');
require_once('../api/vendor/amstudios/amslib/Amslib_String.php');
require_once('../api/vendor/amstudios/amslib/Amslib_Website.php');


class TestOfAmslibFile extends UnitTestCase
{
    public $drive = 'C:';

    function caseTitle($string){
        echo '<h3>Test Of '.$string.'</h3>';
    }

    function log($value){
        echo '<p style="margin:1px 0;">'.$value.'</p>';
    }



    function TestOfRemoveWindowsDrive(){
        $this->caseTitle('removeWindowsDrive');

        $documentRoot = $_SERVER["DOCUMENT_ROOT"];

        $this->log($documentRoot);

        $this->assertEqual( strpos( $documentRoot, $this->drive ), 0 );

        $documentRoot = Amslib_File::removeWindowsDrive( $_SERVER["DOCUMENT_ROOT"] );
        $this->log($documentRoot);

        $this->assertFalse( strpos( $documentRoot, $this->drive ) );

        $documentRoot = $_SERVER["SCRIPT_FILENAME"];
        $this->log($documentRoot);

        $this->assertEqual( strpos( $documentRoot, $this->drive ), 0 );

        $documentRoot = Amslib_File::removeWindowsDrive( $_SERVER["DOCUMENT_ROOT"] );
        $this->log($documentRoot);

        $this->assertFalse( strpos( $documentRoot, $this->drive ) );

        $documentRoot = 'E:\Dir1\Dir2';
        $this->log($documentRoot);

        $documentRoot = Amslib_File::removeWindowsDrive($documentRoot);
        $this->log($documentRoot);

        $this->assertFalse( strpos( $documentRoot, 'E:' ) );
    }


    function TestOfReduceSlashes(){
        $this->caseTitle('reduceSlashes');
        $document = 'http://www.crm-test.local/';
        $this->log($document);

        $this->assertEqual( strpos( $document, 'http://' ), 0 );

        $result = Amslib_File::reduceSlashes($document);
        $this->log($result);

        $this->assertFalse( strpos( $document, 'http://' ) );
    }


    function TestOfDocumentRoot(){
        $this->caseTitle('documentRoot');

        $this->log($_SERVER["DOCUMENT_ROOT"]);
        $result = Amslib_File::documentRoot() ;

        $this->log($result);

        $this->assertEqual( strpos( $result, '/Sites/crm-test' ), 0 );

        $test = 'E:\Dir1\Dir2';
        $this->log($test);

        $result = Amslib_File::documentRoot($test) ;
        $this->log($result);

        $this->assertEqual( strpos( $result, '/Dir1/Dir2' ), 0 );

        $this->log($_SERVER['PHP_SELF']);

        $test = 'E:\Dir1\Dir2\index.php';
        $this->log($test);

        $result = Amslib_File::documentRoot($test) ;
        $this->log($result);

        $this->assertTrue( strpos( $result, '/Sites/crm-test' ) === 0 );
    }

    function TestOfdirname()
    {
        $this->caseTitle('dirname');

        $test = 'C:\Dir1\Dir2\index.php';
        $this->log($test);

        $result = Amslib_File::dirname($test) ;
        $this->log($result);

        $this->assertTrue( strpos( $result, '/Dir1/Dir2' ) === 0 );

        $test = 'C:\Dir1\index.php';
        $this->log($test);

        $result = Amslib_File::dirname($test) ;
        $this->log($result);

        $this->assertTrue( strpos( $result, '/Dir1' ) === 0 );

        $test = 'C:\index.php';
        $this->log($test);

        $result = Amslib_File::dirname($test) ;
        $this->log($result);

        $this->assertTrue( strpos( $result, '/' ) === 0 );
    }


    function TestOfResolvePath(){
        $this->caseTitle('resolvePath');

        $test = 'domain.com/dir_1/dir_2/dir_3/./../../../';
        $this->log($test);
        $result = Amslib_File::resolvePath($test);
        $this->log($result);

        $this->assertEqual( $result, 'domain.com/' );

        $test = 'domain.com/dir_1/dir_2/dir_3/./../../../test/././../new_dir/';
        $this->log($test);
        $result = Amslib_File::resolvePath($test) ;
        $this->log($result);

        $this->assertEqual( $result, 'domain.com/new_dir/' );

        $test = 'domain.com/dir_1/dir_2/dir_3/./../../../test/dir4/../final';
        $this->log($test);
        $result = Amslib_File::resolvePath($test) ;
        $this->log($result);

        $this->assertEqual( $result, 'domain.com/test/final' );
    }

    function TestOfAmslibStringLchop(){
        $this->caseTitle('Amslib_String::lchop');

        $test = 'C:/Sites/crm-test/vendor/index.php';
        $this->log($test);
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $result = Amslib_String::lchop($test, $documentRoot);
        $this->log($result);

        $this->assertFalse( strpos( $result, $documentRoot ) === 0 );

        $test = "/Sites/crm-test/vendor/index.php";
        $this->log($test);
        $documentRoot = Amslib_File::documentRoot($test);
        $result = Amslib_String::lchop($test, Amslib_File::documentRoot($test));
        $this->log($result);

        $this->assertFalse( strpos( $result, $documentRoot ) === 0 );
    }


    function TestOfabsolute(){
        $this->caseTitle('absolute');

        $test = 'http://www.crm-test.local/vendor/amstudios/amslib/file/index.php';
        $this->log($test);

        $result = Amslib_File::absolute($test) ;
        $this->log($result);

        $this->assertTrue( strpos( $result, 'http://www.crm-test.local' ) === 0 );

        $test = 'C:\Sites\crm-test\vendor\amstudios\amslib\file\index.php';
        $this->log($test);
        $result = Amslib_File::absolute($test) ;
        $this->log($result);

        $this->assertTrue( strpos( $result, '/Sites/crm-test' ) === 0 );

        $test = 'vendor\amstudios\amslib\file\index.php';
        $this->log($test);
        $result = Amslib_File::absolute($test) ;
        $this->log($result);

        $this->assertEqual( $result, '/Sites/crm-test/vendor/amstudios/amslib/file/index.php' );

    }
}