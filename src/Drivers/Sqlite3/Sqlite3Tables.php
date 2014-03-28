<?php

namespace Baza\Driver\Sqlite3;

class Sqlite3Tables implements \Baza\Interfaces\TablesInterface{
  public $baza;
  
  function __construct(\Baza $baza){
    $this->baza = $baza;
  }
  
  private function spawnTableFromData($data){
    return new \Baza\Table($this->baza, array(
      "name" => $data["name"],
      "engine" => "sqlite3",
      "collation" => "sqlite3"
    ));
  }
  
  function getTable($name){
    $data = $this->baza->select("sqlite_master", array("type" => "table"), array("orderby" => "name", "limit" => 1))->fetch();
    if (!$data)
      throw new \Baza\Errors\TableNotFound($name);
    
    return $this->spawnTableFromData($data);
  }
  
  function getTables(){
    $return = array();
    $f_gt = $this->baza->select("sqlite_master", array("type" => "table"), array("orderby" => "name"));
    while($d_gt = $f_gt->fetch()){
      if ($d_gt["name"] == "sqlite_sequence") continue;
      $return[$d_gt["name"]] = $this->spawnTableFromData($d_gt);
    }
    
    return $return;
  }
  
  function createTable($tablename, $columns, $args = null){
    $sql = "CREATE TABLE " . $this->baza->conn->sep_table . $tablename . $this->baza->conn->sep_table . " (";
    
    $first = true;
    $primary = false;
    foreach($columns AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      /** NOTE: This fixes the limit of SQLite3 is only able to have one primary key - only the first primary key will be marked. */
      if ($primary == true && array_key_exists("primarykey", $column) && $column["primarykey"] == true){
        $column["primarykey"] = false;
      }elseif(array_key_exists("primarykey", $column) && $column["primarykey"] == true){
        $primary = true;
      }
      
      /** NOTE: If "not null" is set - then set default value to nothing. */
      if (array_key_exists("notnull", $column) && $column["notnull"] == true){
        $column["default_set"] = true;
      }
      
      $sql .= $this->baza->columns()->getColumnSQL($column);
    }
    
    $sql .= ")";
    
    if ($args["returnsql"])
      return $sql;
    
    $this->baza->query($sql);
  }
  
  function dropTable(\Baza\Table $table){
    $this->baza->query("DROP TABLE " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table);
  }
  
  function renameTable(\Baza\Table $table, $newtable){
    $this->baza->query("ALTER TABLE " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table . " RENAME TO " . $this->baza->conn->sep_table . $newtable . $this->baza->conn->sep_table);
  }
  
  function truncateTable(\Baza\Table $table){
    $this->baza->query("DELETE FROM " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table);
  }
  
  function optimizeTable(\Baza\Table $table){
    $this->baza->query("VACUUM"); //vacuum the entire database.
  }
}
