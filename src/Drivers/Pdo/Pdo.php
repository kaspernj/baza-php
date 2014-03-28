<?php

namespace Baza\Driver;

class Pdo{
  private $args, $baza, $tables_driver, $columns_driver, $indexes_driver;
  public $sep_col = "`";
  public $sep_val = "'";
  public $sep_table = "`";
  public $sep_index = "`";
  
  function __construct(\Baza $baza, $args){
    $this->args = $args;
    $this->baza = $baza;
    
    require_once("knj/exts.php");
    knj_dl("pdo");
    
    if ($args["dbtype"] == "Sqlite3"){
      knj_dl("pdo_sqlite");
    }elseif($args["dbtype"] == "Mysql"){
      knj_dl("pdo_mysql");
    }elseif(!array_key_exists("pdostring", $this->args)){
      throw new \Exception("No valid db-type given and no pdostring given.");
    }
  }
  
  function tables(){
    if (!$this->tables_driver){
      \Baza::requireLocal("Drivers/" . $this->args["dbtype"] . "/" . $this->args["dbtype"] . "Tables.php");
      $obname = "\\Baza\\Driver\\" . $this->args["dbtype"] . "\\" . $this->args["dbtype"] . "Tables";
      $this->tables_driver = new $obname($this->baza);
    }
    
    return $this->tables_driver;
  }
  
  function columns(){
    if (!$this->columns_driver){
      \Baza::requireLocal("Drivers/" . $this->args["dbtype"] . "/" . $this->args["dbtype"] . "Columns.php");
      $obname = "\\Baza\\Driver\\" . $this->args["dbtype"] . "\\" . $this->args["dbtype"] . "Columns";
      $this->columns_driver = new $obname($this->baza);
    }
    
    return $this->columns_driver;
  }
  
  function indexes(){
    if (!$this->indexes_driver){
      \Baza::requireLocal("Drivers/" . $this->args["dbtype"] . "/" . $this->args["dbtype"] . "Indexes.php");
      $obname = "\\Baza\\Driver\\" . $this->args["dbtype"] . "\\" . $this->args["dbtype"] . "Indexes";
      $this->indexes_driver = new $obname($this->baza);
    }
    
    return $this->indexes_driver;
  }
  
  function connect(){
    if (array_key_exists("pdostring", $this->args) and $this->args["pdostring"]){
      $pdostring = $this->args["pdostring"];
    }elseif($this->args["dbtype"] == "Sqlite3"){
      $pdostring = "sqlite:" . $this->args["path"];
    }else{
      throw new \Exception("Invalid DB-type: \"" . $this->args["dbtype"] . "\".");
    }
    
    $this->conn = new \PDO($pdostring);
  }
  
  function close(){
    unset($this->conn);
  }
  
  function query($sql){
    while(true){
      $res = $this->conn->query($sql);
      if ($res){
        break;
      }
      
      $err = $this->error();
      if ($err == "database schema has changed"){
        $this->connect();
        continue;
      }else{
        throw new \Exception("Query error: " . $err);
      }
    }
    
    return new \Baza\Result($this->baza, $this, $res);
  }
  
  function fetch($res){
    return $res->fetch(\PDO::FETCH_ASSOC);
  }
  
  function error(){
    $error = $this->conn->errorInfo();
    $error = $error[2];
    if (!$error && $this->lastsqlite3_error){
      $error = $this->lastsqlite3_error;
      unset($this->lastsqlite3_error);
    }
    
    return $error;
  }
  
  function getLastID(){
    return $this->conn->lastInsertID();
  }
  
  function sql($string){
    return substr($this->conn->quote($string), 1, -1);
  }
  
  function beginTransaction(){
    $this->conn->beginTransaction();
  }
  
  function endTransaction(){
    $this->conn->commit();
  }
  
  function rollBackTransaction(){
    $this->conn->rollback();
  }
  
  function insert($table, $arr, $args = null){
    $sql = "INSERT INTO " . $this->sep_table . $table . $this->sep_table . " (";
    
    $first = true;
    foreach($arr AS $key => $value){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->sep_col . $key . $this->sep_col;
    }
    
    $sql .= ") VALUES (";
    $first = true;
    foreach($arr AS $key => $value){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->sep_val . $this->sql($value) . $this->sep_val;
    }
    $sql .= ")";
    
    if ($args && array_key_exists("return_sql", $args) && $args["return_sql"])
      return $sql;
    
    $this->query($sql);
  }
  
  function select($table, $where = array(), $args = array()){
    $sql = "SELECT * FROM " . $this->sep_table . $table . $this->sep_table;
      
    if ($where){
      $sql .= " WHERE " . $this->makeWhere($where);
    }
    
    if ($args and array_key_exists("orderby", $args) and $args["orderby"]){
      $sql .= " ORDER BY " . $args["orderby"];
    }
    
    if ($args and array_key_exists("limit", $args) and $args["limit"]){
      $sql .= " LIMIT " . $args["limit"];
    }
    
    return $this->query($sql);
  }
  
  function delete($table, $where = null){
    $sql = "DELETE FROM " . $this->sep_table . $table . $this->sep_table;
      
    if ($where){
      $sql .= " WHERE " . $this->makeWhere($where);
    }
    
    return $this->query($sql);
  }
  
  function update($table, $data, $where = null){
    $sql = "UPDATE " . $this->sep_table . $table . $this->sep_table . " SET ";
    
    $first = true;
    foreach($data AS $key => $value){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->sep_col . $key . $this->sep_col . " = " . $this->sep_val . $this->sql($value) . $this->sep_val;
    }
    
    if ($where){
      $sql .= " WHERE " . $this->makeWhere($where);
    }
    
    return $this->query($sql);
  }
  
  function makeWhere($where){
    $first = true;
    $sql = "";
    
    foreach($where AS $key => $value){
      if ($first == true){
        $first = false;
      }else{
        $sql .= " AND ";
      }
      
      $sql .= $this->sep_col . $key . $this->sep_col . " = " . $this->sep_val . $this->sql($value) . $this->sep_val;
    }
    
    return $sql;
  }
}
