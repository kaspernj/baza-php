<?php
  class MysqlDbs implements DbsInterface{
    private $baza;
    
    function __construct(\Baza $baza){
      $this->baza = $baza;
    }
    
    function getCurrentDB(){
      $db = $this->baza->query("SELECT DATABASE() AS 'database'")->fetch();
      if (!$db["database"]){
        throw new Exception("No database is selected.");
      }
      
      return $this->getDB($db["database"]);
    }
    
    function getDBs(){
      $return = array();
      $f_gdbs = $this->baza->query("SHOW DATABASES");
      while($d_gdbs = $f_gdbs->fetch()){
        if ($d_gdbs["Database"] != "mysql" && $d_gdbs["Database"] != "information_schema"){
          $return[] = new Db($this->baza, array(
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
      
      //reset tables-cache.
      $this->baza->tables()->tables = array();
      $this->baza->tables()->tables_changed = true;
    }
    
    function createDB($data){
      if (!$data["name"]){
        throw new Exception("No name given.");
      }
      
      $this->baza->query("CREATE DATABASE " . $data["name"]);
    }
    
    function getTablesForDB(Db $db){
      /** FIXME: This should only return tables for the current database. */
      return $this->baza->tables()->getTables();
    }
  }

