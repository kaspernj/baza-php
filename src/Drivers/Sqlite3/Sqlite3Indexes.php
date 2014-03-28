<?php

namespace Baza\Driver\Sqlite3;
\Baza::requireLocal("Interfaces/IndexesInterface.php");

class Sqlite3Indexes implements \Baza\Interfaces\IndexesInterface{
  private $baza;
  
  function __construct(\Baza $baza){
    $this->baza = $baza;
  }
  
  function addIndex(\Baza\Table $table, $cols, $name = null, $args = null){
    if (!$name){
      $name = "index";
      foreach($cols AS $col){
        $name .= "_" . $col->getName();
      }
    }
    
    $sql = "CREATE INDEX " . $this->baza->conn->sep_col . $name . $this->baza->conn->sep_col . " ON " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table . " (";
      
    $first = true;
    foreach($cols AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      if (is_a($column, "Baza\Column")){
        $name = $column->getName();
      }elseif(gettype($column) == "string"){
        $name = $column;
      }else{
        throw new \Exception("Didn't know how to get name from column-object: " . gettype($column));
      }
      
      $sql .= $this->baza->conn->sep_col . $name . $this->baza->conn->sep_col;
    }
    
    $sql .= ")";
    
    if (array_key_exists("return_sql", $args) && $args["return_sql"]){
      return $sql;
    }
    
    $this->baza->query($sql);
    $table->indexes_changed = true;
  }
  
  function getIndexSQL(\Baza\Index $index){
    $sql = "CREATE INDEX " . $this->baza->conn->sep_col . $index->getName() . $this->baza->conn->sep_col . " ON " . $this->baza->conn->sep_table . $index->table->getName() . $this->baza->conn->sep_table . " (";
      
    $first = true;
    foreach($index->getColumns() AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->conn->sep_col . $column->getName() . $this->baza->conn->sep_col;
    }
    
    $sql .= ")";
    
    return $sql;
  }
  
  function getIndexes(\Baza\Table $table){
    $indexes = array();
    $f_gi = $this->baza->query("PRAGMA index_list(" . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table . ")");
    while($d_gi = $f_gi->fetch()){
      if (strpos($d_gi["name"], "sqlite") !== false && strpos($d_gi["name"], "autoindex") !== false)
        continue;
      
      $index = array();
      $index["name"] = $d_gi["name"];
      
      $first = true;
      $columns_text = "";
      $f_gid = $this->baza->query("PRAGMA index_info('" . $d_gi["name"] . "')");
      while($d_gid = $f_gid->fetch()){
        if ($first == true){
          $first = false;
        }else{
          $columns_text .= ", ";
        }
        
        $index["column_names"][] = $d_gid["name"];
      }
      
      $indexes[$index["name"]] = new \Baza\Index($this->baza, $table->getName(), $index);
    }
    
    return $indexes;
  }
  
  function removeIndex(\Baza\Table $table, \Baza\Index $index){
    $sql = "DROP INDEX " . $this->baza->conn->sep_index . $index->getName() . $this->baza->conn->sep_index;
    $this->baza->query($sql);
    $this->baza->query("VACUUM " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table);
    unset($table->indexes[$index->getName()]);
  }
}

