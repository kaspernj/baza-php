<?php

namespace Baza\Driver\Mysqli;

class MysqliDbs implements \Baza\Interfaces\DbsInterface{
  private $baza;
  
  function __construct(\Baza $baza){
    $this->baza = $baza;
  }
  
  function getDBs(){
    $return = array();
    $f_gdbs = $this->baza->query("SHOW DATABASES");
    while($d_gdbs = $f_gdbs->fetch()){
      if ($d_gdbs["Database"] != "mysql" && $d_gdbs["Database"] != "information_schema"){
        $return[] = new Db(array(
            "name" => $d_gdbs["Database"]
          )
        );
      }
    }
    
    return $return;
  }
  
  function getDB($name){
    foreach($this->getDBs() AS $db){
      if ($db->getName() == $name){
        return $db;
      }
    }
    
    throw new Exception("Could not find the database.");
  }
  
  function chooseDB(Db $db){
    $this->baza->query("USE " . $db->getName());
  }
  
  function createDB($data){
    if (!$data["name"]){
      throw new Exception("No name given.");
    }
    
    $this->baza->query("CREATE DATABASE " . $data["name"]);
  }
}
