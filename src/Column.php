<?php

class Column{
  private $baza;
  private $table;
  private $data;
  
  function __construct(Table $table, $data){
    $this->baza = $table->baza;
    $this->table = $table;
    $this->data = $data;
  }
  
  function getBaza(){
    return $this->baza;
  }
  
  function getTable(){
    return $this->table;
  }
  
  function setData($arr){
    $changed = false;
    foreach($this->data AS $key => $value){
      if ($key != "input_type" && $arr[$key] != $value){
        $changed = true;
        break;
      }
    }
    
    if (!$changed){
      return null; //abort if the data is the same.
    }
    
    $this->baza->columns()->editColumn($this, $arr);
    foreach($arr AS $key => $value){
      $this->data[$key] = $value;
    }
  }
  
  function getPrimaryKey(){
    return $this->data["primarykey"];
  }
  
  function getName(){
    return $this->data["name"];
  }
  
  function getMaxLength(){
    return $this->data["maxlength"];
  }
  
  function getType(){
    return $this->data["type"];
  }
  
  function getUnsigned(){
    return $this->data["unsigned"];
  }
  
  function getNull(){
    if ($this->data["notnull"]){
      return false;
    }else{
      return true;
    }
  }
  
  function getNotNull(){
    return !$this->getNull();
  }
  
  /** Returns a key from the row. */
  function get($key){
    if (!array_key_exists($key, $this->data)){
      throw new Exception("The key does not exist: \"" . $key . "\".");
    }
    
    return $this->data[$key];
  }
}
