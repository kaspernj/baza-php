<?php

namespace Baza\Driver\Mysqli;

\Baza::requireLocal("Interfaces/IndexesInterface.php");

class MysqliIndexes implements \Baza\Interfaces\IndexesInterface{
  public $baza;
  
  function __construct($baza){
    $this->baza = $baza;
    $this->driver = $this->baza->getDriver();
  }
  
  function getIndexSQL(\Baza\Index $index){
    $sql = "CREATE INDEX " . $this->driver->sep_col . $index->getName() . $this->driver->sep_col . " ON " . $this->driver->sep_table . $index->getTable()->getName() . $this->driver->sep_table . " (";
    $first = true;
    foreach($index->getColumns() AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->driver->sep_col . $column->getName() . $this->driver->sep_col;
    }
    $sql .= ");\n";
    
    return $sql;
  }
  
  function addIndex(\Baza\Table $table, $cols, $name = null, $args = null){
    if (!$name){
      $name = "index";
      foreach($cols AS $col){
        $name .= "_" . $col->getName();
      }
    }
    
    $sql = "CREATE";
    
    if (array_key_exists("unique", $args) && $args["unique"]){
      $sql .= " UNIQUE";
    }
    
    $sql .= " INDEX " . $this->driver->sep_table . $name . $this->driver->sep_table . " ON " . $this->driver->sep_table . $table->getName() . $this->driver->sep_table . " (";
    
    $first = true;
    foreach($cols AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      if (gettype($column) == "string"){
        $name = $column;
      }elseif(is_a($column, "\Baza\Column")){
        $name = $column->getName();
      }
      
      $sql .= $this->driver->sep_col . $name . $this->driver->sep_col;
    }
    
    $sql .= ")";
    
    if ($args && array_key_exists("return_sql", $args) && $args["return_sql"]){
      return $sql;
    }
    
    $this->baza->query($sql);
    $table->indexes_changed = true;
  }
  
  function removeIndex(\Baza\Table $table, \Baza\Index $index){
    $sql = "DROP INDEX " . $this->driver->sep_index . $index->getName() . $this->driver->sep_index . " ON " . $this->driver->sep_table . $table->getName() . $this->driver->sep_table;
    $this->baza->query($sql);
    unset($table->indexes[$index->getName()]);
  }
  
  function getIndexes(\Baza\Table $table){
    $indexes = array();
    
    $f_gi = $this->baza->query("SHOW INDEX FROM " . $this->driver->sep_table . $table->getName() . $this->driver->sep_table);
    while($d_gi = $f_gi->fetch()){
      if ($d_gi["Key_name"] == "PRIMARY") continue;
      
      $key = $d_gi["Key_name"];
      if (!array_key_exists($key, $indexes)){
        if ($d_gi["Index_type"] == "UNIQUE"){
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
      $return[$name] = new \Baza\Index($table, $value);
    }
    
    return $return;
  }
}
