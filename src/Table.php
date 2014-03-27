<?php

class Table{
  public $baza;
  public $data;
  
  public $columns = array();
  public $columns_changed = true;
  
  public $indexes = array();
  public $indexes_changed = true;
  
  function __construct($baza, $data){
    $this->baza = $baza;
    $this->data = $data;
  }
  
  function rename($newname){
    $this->baza->tables()->renameTable($this, $newname);
    $this->data["name"] = $newname;
  }
  
  /** Returns a key from the row. */
  function get($key){
    if (!array_key_exists($key, $this->data)){
      throw new Exception("The key does not exist: \"" . $key . "\".");
    }
    
    return $this->data[$key];
  }
  
  function getIndexByName($name){
    if ($this->indexes[$name]){
      return $this->indexes[$name];
    }
    
    foreach($this->getIndexes() AS $index){
      if ($index->get("name") == $name){
        return $index;
      }
    }
    
    throw new Exception("Index not found: \"" . $name . "\" on table \"" . $this->get("name") . "\".");
  }
  
  /** Count the rows for a table. */
  function countRows(){
    $d_c = $this->baza->query("SELECT COUNT(*) AS count FROM " . $this->baza->conn->sep_table . $this->get("name") . $this->baza->conn->sep_table)->fetch();
    return $d_c["count"];
  }
  
  function addColumns($columns){
    $this->baza->columns()->addColumns($this, $columns);
  }
  
  function addIndex($cols, $name = null, $args = array()){
    if (!$name){
      $name = "table_" . $this->get("name") . "_cols";
      
      foreach($cols AS $col){
        $name .= "_" . $col->get("name");
      }
    }
    
    $this->baza->indexes()->addIndex($this, $cols, $name, $args);
  }
  
  function removeIndex(Index $index){
    $this->baza->indexes()->removeIndex($this, $index);
  }
  
  function removeColumn(Column $col){
    $this->baza->columns()->removeColumn($this, $col);
  }
  
  function getColumns(){
    return $this->baza->columns()->getColumns($this);
  }
  
  function getColumn($name){
    if ($this->columns[$name]){
      return $this->columns[$name];
    }
    
    $cols = $this->getColumns();
    foreach($cols AS $col){
      if ($col->get("name") == $name){
        return $col;
      }
    }
    
    throw new Exception("The column was not found (" . $name . ").");
  }
  
  function getIndexes(){
    return $this->baza->indexes()->getIndexes($this);
  }
  
  function drop(){
    $this->baza->tables()->dropTable($this);
    unset($this->columns);
    unset($this->columns_changed);
  }
  
  function truncate(){
    $this->baza->tables()->truncateTable($this);
  }
  
  function optimize(){
    $this->baza->tables()->optimizeTable($this);
  }
  
  function getName(){
    return $this->data["name"];
  }
}

