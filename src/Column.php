<?php

namespace Baza;

class Column{
  private $baza;
  private $tableName;
  private $data;
  
  function __construct(\Baza $baza, $tableName, $data){
    if (!is_string($tableName))
      throw new \Exception("Table name wasn't a string: " . gettype($tableName));
    
    $this->baza = $baza;
    $this->tableName = $tableName;
    $this->data = $data;
  }
  
  function getBaza(){
    return $this->baza;
  }
  
  function getTable(){
    return $this->baza->tables()->getTable($this->tableName);
  }
  
  function getTableName(){
    return $this->tableName;
  }
  
  function getData(){
    return $this->data;
  }
  
  function setData($arr){
    $changed = false;
    foreach($this->data AS $key => $value){
      if ($key != "input_type" && $arr[$key] != $value){
        $changed = true;
        break;
      }
    }
    
    if (!$changed) return;
    
    $this->baza->columns()->editColumn($this, $arr);
    $this->reload();
  }
  
  function getPrimaryKey(){
    return $this->data["primarykey"];
  }
  
  function getAutoIncrement(){
    return $this->data["autoincr"];
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
    if (!array_key_exists("unsigned", $this->data))
      throw new \Exception("Unsigned was not given by driver.");
    
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
  
  function rename($newname){
    $data = $this->data;
    $data["name"] = $newname;
    
    $this->getBaza()->columns()->editColumn($this, $data);
    $this->data["name"] = $newname;
    $this->reload();
  }
  
  function reload(){
    $column = $this->getTable()->getColumn($this->getName());
    $this->data = $column->getData();
  }
  
  function drop(){
    $this->baza->columns()->removeColumn($this->getTable(), $this);
  }
}
