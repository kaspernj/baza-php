<?php

namespace Baza\Driver\Mysqli;

class MysqliTables implements \Baza\Interfaces\TablesInterface{
  public $baza;
  
  function __construct(\Baza $baza){
    $this->baza = $baza;
  }
  
  private function spawnTableFromData($data){
    return new \Baza\Table($this->baza, array(
      "name" => $data["Name"],
      "engine" => $data["Engine"],
      "collation" => $data["Collation"],
      "rows" => $data["Rows"]
    ));
  }
  
  function getTables(){
    $tables = array();
    $f_gt = $this->baza->query("SHOW TABLE STATUS");
    while($d_gt = $f_gt->fetch()){
      $tables[$d_gt["Name"]] = $this->spawnTableFromData($d_gt);
    }
    
    return $tables;
  }
  
  function getTable($name){
    $data = $this->baza->query("SHOW TABLE STATUS WHERE `Name` = '" . $this->baza->sql($name) . "'")->fetch();
    if (!$data) throw new \Baza\Errors\TableNotFound("No such table: '" . $name . "'.");
    return $this->spawnTableFromData($data);
  }
  
  function renameTable(\Baza\Table $table, $newname){
    $this->baza->query("ALTER TABLE " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table . " RENAME TO " . $this->baza->conn->sep_table . $newname . $this->baza->conn->sep_table);
  }
  
  function createTable($tablename, $cols, $args = null){
    $sql = "CREATE";
    if ($args["temp"]) $sql .= " TEMPORARY";
    
    $sql .= " TABLE " . $this->baza->conn->sep_table . $tablename . $this->baza->conn->sep_table . " (";
    $prim_keys = array();
    
    $first = true;
    foreach($cols AS $col){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->columns()->getColumnSQL($col);
    }
    $sql .= ")";
    
    if ($args["return_sql"]) return $sql;
    
    $this->baza->query($sql);
  }
  
  function dropTable(\Baza\Table $table){
    $this->baza->query("DROP TABLE " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table);
  }
  
  function truncateTable(\Baza\Table $table){
    $this->baza->query("TRUNCATE " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table);
  }
  
  function optimizeTable(\Baza\Table $table){
    $this->baza->query("OPTIMIZE TABLE " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table); //vacuum the entire database.
  }
}
