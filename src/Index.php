<?php

namespace Baza;

class Index{
  private $baza;
  private $tableName;
  private $data;
  
  function __construct($baza, $tableName, $data){
    if (!array_key_exists("column_names", $data))
      throw new \Exception("'column_names' was not given.");
    
    $this->baza = $baza;
    $this->tableName = $tableName;
    $this->data = $data;
  }
  
  function getTable(){
    return $this->baza->tables()->getTable($this->tableName);
  }
  
  /** Returns a key from the row. */
  function get($key){
    if (!array_key_exists($key, $this->data))
      throw new \Exception("The key does not exist: \"" . $key . "\".");
    
    return $this->data[$key];
  }
  
  function getName(){
    return $this->data["name"];
  }
  
  function getColumns(){
    $table = $this->getTable();
    
    $columns = array();
    foreach($this->data["column_names"] as $column_name){
      $columns[] = $table->getColumn($column_name);
    }
    
    return $columns;
  }
  
  function getColumnNames(){
    return $this->data["column_names"];
  }
  
  function getUnique(){
    if (!array_key_exists("unique", $this->data))
      throw new \Exception("Unique was not given by driver.");
    
    return $this->data["unique"];
  }
  
  function getColText(){
    $text = "";
    foreach($this->getColumns() AS $column){
      if (strlen($text) > 0){
        $text .= ", ";
      }
      
      $text .= $column->getName();
    }
    
    return $text;
  }
}
