<?php

class Index{
  public $baza;
  public $table;
  public $data;
  
  function __construct($table, $data){
    $this->baza = $table->baza;
    $this->table = $table;
    $this->data = $data;
  }
  
  /** Returns a key from the row. */
  function get($key){
    if (!array_key_exists($key, $this->data)){
      throw new Exception("The key does not exist: \"" . $key . "\".");
    }
    
    return $this->data[$key];
  }
  
  function getName(){
    return $this->data["name"];
  }
  
  function getColumns(){
    $columns = array();
    foreach($this->data["column_names"] as $column_name){
      $columns[] = $this->table->getColumn($column_name);
    }
    
    return $columns;
  }
  
  function getColumnNames(){
    return $this->data["column_names"];
  }
  
  function getUnique(){
    return $this->data["unique"];
  }
  
  function getColText(){
    $text = "";
    foreach($this->getColumns() AS $column){
      if (strlen($text) > 0){
        $text .= ", ";
      }
      
      $text .= $column->get("name");
    }
    
    return $text;
  }
}
