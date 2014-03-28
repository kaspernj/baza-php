<?php

namespace Baza;

class Table{
  private $baza;
  private $data;
  
  function __construct($baza, $data){
    $this->baza = $baza;
    $this->data = $data;
  }
  
  function getBaza(){ return $this->baza; }
  function getData(){ return $this->data; }
  function getName(){ return $this->data["name"]; }
  
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
      if ($index->getName() == $name){
        return $index;
      }
    }
    
    throw new Exception("Index not found: \"" . $name . "\" on table \"" . $this->getName() . "\".");
  }
  
  /** Count the rows for a table. */
  function countRows(){
    $d_c = $this->baza->query("SELECT COUNT(*) AS count FROM " . $this->baza->conn->sep_table . $this->getName() . $this->baza->conn->sep_table)->fetch();
    return $d_c["count"];
  }
  
  function addColumns($columns){
    $this->baza->columns()->addColumns($this, $columns);
  }
  
  function addIndex($cols, $name = null, $args = array()){
    if (!is_array($cols))
      throw new \Exception("Columns was not given as the first argument in an array: " . gettype($cols));
    
    if (!$name){
      $name = "table_" . $this->getName() . "_cols";
      
      foreach($cols as $col){
        $name .= "_" . $col->getName();
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
    $cols = $this->getColumns();
    foreach($cols as $col){
      if ($col->getName() == $name) return $col;
    }
    
    throw new \Baza\Errors\ColumnNotFound($name);
  }
  
  function columnExists($name){
    return $this->baza->columns()->columnExists($this, $name);
  }
  
  function getIndexes(){
    $indexes = $this->baza->indexes()->getIndexes($this);
    if (!is_array($indexes))
      throw new \Exception("Result by driver was not an array.");
    
    return $indexes;
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
  
  function reload(){
    $table = $this->baza->tables()->getTable($this->getName());
    $this->data = $table->getData();
  }
}

