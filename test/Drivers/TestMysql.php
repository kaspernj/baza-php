<?php

require_once dirname(__FILE__) . "/../TestCase.php";

class TestMysql extends TestCase{
  public $db;
  
  function setUp(){
    parent::setUp();
    
    $this->db = new Baza(array(
      "type" => "Mysql",
      "host" => "localhost",
      "user" => "baza-test",
      "db" => "baza-test"
    ));
    
    // Drop all existing tables to start from scratch.
    foreach($this->db->tables()->getTables() as $table){
      $table->drop();
    }
  }
  
  function tearDown(){
    parent::tearDown();
    $this->db->close();
  }
}
