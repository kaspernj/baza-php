<?php

namespace Baza\Driver\Mysqli;

\Baza::requireLocal("Interfaces/IndexesInterface.php");

class MysqliIndexes implements \IndexesInterface{
  public $baza;
  private $indexes = array();
  
  function __construct($baza){
    $this->baza = $baza;
  }
  
  function getIndexSQL(Index $index){
    $sql = "CREATE INDEX " . $this->baza->connob->sep_col . $index->get("name") . $this->baza->connob->sep_col . " ON " . $this->baza->connob->sep_table . $index->getTable()->get("name") . $this->baza->connob->sep_table . " (";
    $first = true;
    foreach($index->getColumns() AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->connob->sep_col . $column->get("name") . $this->baza->connob->sep_col;
    }
    $sql .= ");\n";
    
    return $sql;
  }
  
  function addIndex(Table $table, $cols, $name = null, $args = null){
    if (!$name){
      $name = "index";
      foreach($cols AS $col){
        $name .= "_" . $col->get("name");
      }
    }
    
    $sql = "CREATE";
    
    if ($args["unique"]){
      $sql .= " UNIQUE";
    }
    
    $sql .= " INDEX " . $this->baza->connob->sep_table . $name . $this->baza->connob->sep_table . " ON " . $this->baza->connob->sep_table . $table->get("name") . $this->baza->connob->sep_table . " (";
    
    $first = true;
    foreach($cols AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->connob->sep_column . $column->getName() . $this->baza->connob->sep_column;
    }
    
    $sql .= ")";
    
    if ($args["returnsql"]){
      return $sql;
    }
    
    $this->baza->query($sql);
    $table->indexes_changed = true;
  }
  
  function removeIndex(\Table $table, \Index $index){
    $sql = "DROP INDEX " . $this->baza->conn->sep_index . $index->getName() . $this->baza->conn->sep_index . " ON " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table;
    $this->baza->query($sql);
    unset($table->indexes[$index->get("name")]);
  }
  
  function getIndexes(\Table $table){
    $indexes = array();
    
    $f_gi = $this->baza->query("SHOW INDEX FROM " . $this->baza->conn->sep_table . $table->getName() . $this->baza->conn->sep_table);
    while($d_gi = $f_gi->fetch()){
      if ($d_gi["Key_name"] == "PRIMARY") continue;
      
      $key = $d_gi["Key_name"];
      if (!array_key_exists($key, $indexes)){
        if ($d_gi["Index_type"] == "UNIQUE" || $d_gi["Non_unique"] == 1){
          $unique = true;
        }else{
          $unique = false;
        }
        
        $indexes[$key] = array("name" => $key, "unique" => $unique, "column_names" => array());
      }
      
      $indexes[$key]["column_names"][] = $d_gi["Column_name"];
    }
    
    //Making keys to numbers (as in SQLite).
    $return = array();
    foreach($indexes AS $name => $value){
      if (!array_key_exists($name, $this->indexes)){
        $return[$name] = new \Index($table, $value);
      }
    }
    
    return $return;
  }
}
