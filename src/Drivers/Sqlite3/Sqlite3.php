<?php

namespace Baza\Driver;

class Sqlite3{
  private $args;
  private $baza;
  public $tables = array();
  public $sep_col = "`";
  public $sep_val = "'";
  public $sep_table = "`";
  public $sep_index = "`";
  
  function __construct(\Baza $baza, $args){
    $this->args = $args;
    $this->baza = $baza;
    
    require_once("knj/exts.php");
    knj_dl("sqlite3");
  }
  
  function connect(){
    $this->conn = new \SQLite3($this->args["path"]);
  }
  
  function close(){
    unset($this->conn);
  }
  
  function query($sql){
    $res = @$this->conn->query($sql);
    if (!$res)
      throw new \Exception("Could not execute query: " . $this->error() . " for SQL: " . $sql);
    
    return new \Baza\Result($this->baza, $this, $res);
  }
  
  function fetch($res){
    return $res->fetchArray();
  }
  
  function error(){
    return $this->conn->lastErrorMsg();
  }
  
  function getLastID(){
    return $this->conn->lastInsertRowID();
  }
  
  function sql($string){
    return $this->conn->escapeString($string);
  }
  
  function insert($table, $arr, $args = array()){
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

    if (array_key_exists("return_sql", $args) && $args["return_sql"])
      return $sql;
    
    $this->query($sql);
  }
  
  function select($table, $where = null, $args = null){
    $sql = "SELECT";
    
    $sql .= " * FROM " . $this->sep_table . $table . $this->sep_table;
     
    if ($where){
      $sql .= " WHERE " . $this->makeWhere($where);
    }
    
    if ($args["orderby"]){
      $sql .= $this->makeOrderby($args["orderby"]);
    }
    
    if (array_key_exists("limit", $args)){
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
    $sql .= "UPDATE " . $this->sep_table . $table . $this->sep_table . " SET ";
    
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
  
  function countRows($res){
    return mysql_num_rows($res);
  }
  
  function makeWhere($where){
    $sql = "";
    $first = true;
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
  
  function makeOrderby($orderby){
    if (is_string($orderby)){
      return " ORDER BY " . $orderby;
    }elseif(is_array($orderby)){
      $sql = " ORDER BY ";
      
      $first = true;
      foreach($orderby AS $col_name){
        if ($first == true){
          $first = false;
        }else{
          $sql .= ", ";
        }
        
        $sql .= $this->sep_col . $col_name . $this->sep_col;
      }
      
      return $sql;
    }
  }
  
  function beginTransaction(){
    $this->baza->query("BEGIN TRANSACTION");
  }
  
  function endTransaction(){
    $this->baza->query("COMMIT");
  }
  
  function rollBackTransaction(){
    $this->baza->query("ROLLBACK");
  }
}
