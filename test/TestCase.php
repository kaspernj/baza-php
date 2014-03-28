<?php

require_once dirname(__FILE__) . "/../src/Baza.php";

class TestCase extends PHPUnit_Framework_TestCase{
  private function stdTable(){
    $this->db->tables()->createTable("test", array(
      array("name" => "id", "type" => "int", "autoincr" => true, "primarykey" => true),
      array("name" => "name", "type" => "varchar", "maxlength" => 200)
    ));
    
    $table = $this->db->tables()->getTable("test");
    $table->addIndex(array("name"), "name");
    
    return array(
      "table" => $table
    );
  }
  
  function testCreate(){
    $data = $this->stdTable();
    $table = $data["table"];
    
    $columns = $table->getColumns();
    
    $this->assertEquals(2, count($columns));
    $this->assertTrue($columns["id"]->getPrimaryKey());
    $this->assertFalse($columns["name"]->getPrimaryKey());
    $this->assertEquals("id", $columns["id"]->getName());
    $this->assertEquals(200, $columns["name"]->getMaxLength());
    $table->drop();
  }
  
  function testRenameColumn(){
    $data = $this->stdTable();
    $data["table"]->getColumn("name")->rename("name2");
    
    $this->assertTrue($data["table"]->columnExists("name2"));
    $this->assertFalse($data["table"]->columnExists("name"));
    $this->assertTrue($data["table"]->columnExists("id"));
  }
  
  function testDropColumn(){
    $data = $this->stdTable();
    $data["table"]->getColumn("name")->drop();
    $this->assertEquals(1, count($data["table"]->getColumns()));
  }
  
  function testReturnInsertSql(){
    $data = $this->stdTable();
    $sql = $this->db->insert("test", array("name" => "Kasper"), array("return_sql" => true));
    
    $this->assertEquals("string", gettype($sql));
  }
  
  function testRenameTable(){
    $data = $this->stdTable();
    $data["table"]->rename("test2");
    $this->assertEquals("test2", $data["table"]->getName());
    $this->assertEquals("test2", $this->db->tables()->getTable("test2")->getName());
  }
  
  /**
   * @expectedException \Baza\Errors\TableNotFound
  */
  function testDropTable(){
    $data = $this->stdTable();
    $data["table"]->drop();
    $this->db->tables()->getTable("test");
  }
  
  function testTruncateTable(){
    $data = $this->stdTable();
    $this->db->insertMulti("test", array(
      array("name" => "Kasper"),
      array("name" => "Christina")
    ));
    $this->assertEquals(2, $data["table"]->countRows());
    
    $first_row = $this->db->single("test", null, array("orderby" => "id"));
    $this->assertEquals(1, $first_row["id"]);
    
    $data["table"]->truncate();
    $this->assertEquals(0, $data["table"]->countRows());
    $this->db->insert("test", array("name" => "Kasper"));
    
    $first_row = $this->db->single("test", null, array("orderby" => "id"));
    $this->assertEquals(1, $first_row["id"]);
  }
  
  function testTransactions(){
    $data = $this->stdTable();
    
    $this->db->transaction(function(){
      $this->db->insertMulti("test", array(
        array("name" => "Kasper"),
        array("name" => "Christina")
      ));
    });
    
    $this->assertEquals(2, $data["table"]->countRows());
  }
  
  function testFailedTransaction(){
    $data = $this->stdTable();
    
    try{
      $this->db->transaction(function(){
        $this->db->insertMulti("test", array(
          array("name" => "Kasper"),
          array("name" => "Christina")
        ));
        
        throw new \Exception("test");
      });
    }catch(\Exception $e){
      $this->assertEquals("test", $e->getMessage());
    }
    
    $this->assertEquals(0, $data["table"]->countRows());
  }
}
