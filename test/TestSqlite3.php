<?php

require_once dirname(__FILE__) . "/../src/Baza.php";

class TestSqlite3 extends PHPUnit_Framework_TestCase {
  function testGeneral(){
    $path = "/tmp/baza.sqlite3";
    
    if (file_exists($path)) unlink($path) or die("Could not delete existing database.");
    
    $db = new Baza(array(
      "type" => "Sqlite3",
      "path" => $path
    ));
    $db->tables()->createTable("test", array(
      array("name" => "id", "type" => "int", "autoincr" => true, "primarykey" => true),
      array("name" => "name", "type" => "varchar", "maxlength" => 200)
    ));
    
    $table = $db->getTable("test");
    $columns = $table->getColumns();
    
    $this->assertEquals(2, count($columns));
    
    $this->assertTrue($columns["id"]->getPrimaryKey());
    $this->assertFalse($columns["name"]->getPrimaryKey());
    
    $this->assertEquals("id", $columns["id"]->getName());
    
    $this->assertEquals(200, $columns["name"]->getMaxLength());
  }
}
