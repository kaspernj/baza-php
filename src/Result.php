<?php

namespace Baza;

class Result{
  public $baza;
  public $driver;
  public $result;
  
  function __construct(\Baza $baza, $driver, $result){
    $this->baza = $baza;
    $this->driver = $driver;
    $this->result = $result;
  }
  
  function fetch(){
    return $this->driver->fetch($this->result);
  }
  
  function free(){
    return $this->driver->free($this->result);
  }
  
  function each($func){
    while($data = $this->fetch()){
      $func($data);
    }
    
    $this->free();
  }
}

