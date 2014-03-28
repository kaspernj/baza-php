<?php

require_once dirname(__FILE__) . "/../TestCase.php";

class TestSqlite3 extends TestCase{
  public $db;
  
  function setUp(){
    parent::setUp();
    
    $path = "/tmp/baza.sqlite3";
    if (file_exists($path)) unlink($path) or die("Could not delete existing database.");
    
    $this->db = new Baza(array(
      "type" => "Sqlite3",
      "path" => $path
    ));
  }
  
  function tearDown(){
    parent::tearDown();
    $this->db->close();
  }
}
