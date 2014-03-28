<?php

namespace Baza;

class Db{
  private $baza, $data;
  
  function __construct(\Baza $baza, $data){
    $this->baza = $baza;
    $this->data = $data;
  }
  
  function getName(){
    return $this->data["name"];
  }
  
  function getTables(){
    return $this->baza->dbs()->getTablesForDB($this);
  }
  
  function optimize(){
    foreach($this->getTables() AS $table){
      $table->optimize();
    }
  }
}
