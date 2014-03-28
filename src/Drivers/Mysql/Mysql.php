<?php

namespace Baza\Driver;
\Baza::requireLocal("Drivers/Mysqli/Mysqli.php");

class Mysql extends \Baza\Driver\Mysqli{
  private $args;
  private $baza;
  public $tables = array();
  public $sep_col = "`";
  public $sep_val = "'";
  public $sep_table = "`";
  public $sep_index = "`";
  
  function __construct(\Baza $baza, &$args){
    $this->args = $args;
    $this->baza = $baza;
    
    require_once("knj/functions_knj_extensions.php");
    knj_dl("mysql");
  }
  
  function connect(){
    if (array_key_exists("pass", $this->args)){
      $password = $this->args["pass"];
    }else{
      $password = null;
    }
    
    if (array_key_exists("port", $this->args) and $this->args["port"] and $this->args["port"] != 3306){
      $this->conn = mysql_connect($this->args["host"] . ":" . $this->args["port"], $this->args["user"], $password, true);
    }else{
      $this->conn = mysql_connect($this->args["host"], $this->args["user"], $password, true);
    }
    
    if (!$this->conn){
      throw new \Exception("Could not connect: " . mysql_error()); //use mysql_error() here since $this->conn has not been set.
    }
    
    if ($this->args["db"]){
      if (!mysql_select_db($this->args["db"], $this->conn)){
        throw new \Exception("Could not select database: " . $this->error());
      }
    }
  }
  
  function close(){
    mysql_close($this->conn);
    unset($this->conn);
  }
  
  function query($query){
    $res = mysql_query($query, $this->conn);
    if (!$res){
      throw new \Exception("Query error: " . $this->error() . "\n\nSQL: " . $query);
    }
    
    return new \Baza\Result($this->baza, $this, $res);
  }
  
  function queryUbuf($query){
    $res = mysql_unbuffered_query($query, $this->conn);
    if (!$res)
      throw new \Exception("Query error: " . $this->error() . "\n\nSQL: " . $query);
    
    return new \Baza\Result($this->baza, $this, $res);
  }
  
  function fetch($res){
    return mysql_fetch_assoc($res);
  }
  
  function error(){
    return mysql_error($this->conn);
  }
  
  function free($res){
    return mysql_free_result($res);
  }
  
  function getLastID(){
    return mysql_insert_id($this->conn);
  }
  
  function sql($string){
    if (is_object($string) or is_array($string)) throw new exception("Given argument was a valid string.");
    return mysql_real_escape_string($string);
  }
  
  function beginTransaction(){
    $this->query("BEGIN");
  }
  
  function endTransaction(){
    $this->query("COMMIT");
  }
  
  function rollBackTransaction(){
    $this->query("ROLLBACK");
  }
  
  function countRows($res){
    return mysql_num_rows($res);
  }
}
