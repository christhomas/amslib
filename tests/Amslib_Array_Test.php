<?php
class Amslib_Array_Test extends PHPUnit_Framework_TestCase
{
  public function testValidArray()
  {
    $this->assertEquals([],Amslib_Array::valid([]));
  }
}
