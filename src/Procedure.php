<?php
  class Procedure{
    private $baza;
    private $data;
    
    function __construct(\Baza $baza, $data){
      $this->baza = $baza;
      $this->data = $data;
    }
    
    function get($key){
      if (!array_key_exists($key, $this->data)){
        throw new Exception("Key does not exist.");
      }
      
      return $this->data[$key];
    }
  }

