<?php

Baza::requireLocal("Result.php");
Baza::requireLocal("Errors.php");

class Baza{
  static function requireLocal($path){
    require_once dirname(__FILE__) . "/" . $path;
  }
  
  public $conn, $rows;
  public $args = array(
    "col_id" => "id",
    "autoconnect" => true
  );
  private $drivers = array();
  public $insert_autocommit, $insert_countcommit; //variables used by the transaction-autocommit-feature.
  
  /** The constructor. */
  function __construct($args = null){
    $this->rows = array();
    
    if ($args){
      $this->setOpts($args);
    }
  }
  
  /** Returns the current type (mysql, sqlite2, pdo...). */
  function getType(){
    return $this->args["type"];
  }
  
  function getDriver(){
    return $this->conn;
  }
  
  /** Connects to a database. */
  function connect(){
    Baza::requireLocal("Drivers/" . $this->args["type"] . "/" . $this->args["type"] . ".php");
    $obname = "Baza\\Driver\\" . $this->args["type"];
    $this->conn = new $obname($this, $this->args);
    $this->conn->connect();
  }
  
  /** Reconnects to the database. */
  function reconnect(){
    $this->connect();
  }
  
  function module($module){
    if ($module == "Indexes"){
      $short = "Index";
    }else{
      $short = substr($module, 0, -1);
    }
    
    if (!array_key_exists($module, $this->drivers)){
      Baza::requireLocal("Interfaces/" . $module . "Interface.php");
      require_once($short . ".php");
      
      if (method_exists($this->conn, $module)){
        $this->drivers[$module] = $this->conn->$module();
      }else{
        $obname = $this->args["type"] . $module;
        require_once("Drivers/" . $this->args["type"] . "/" . $obname . ".php");
        
        $full_class = "Baza\\Driver\\" . $this->args["type"] . "\\" . $obname;
        $this->drivers[$module] = new $full_class($this);
      }
    }
    
    return $this->drivers[$module];
  }
  
  function rows(){
    return $this->module("Rows");
  }
  
  function dbs(){
    return $this->module("Dbs");
  }
  
  /** Returns the tables-module. */
  function tables(){
    return $this->module("Tables");
  }
  
  /** Returns the columns-module. */
  function columns(){
    return $this->module("Columns");
  }
  
  /** Returns the indexes-module. */
  function indexes(){
    return $this->module("Indexes");
  }
  
  function procedures(){
    return $this->module("Procedures");
  }
  
  /** Returns a row by its ID and table. */
  function getRow($id, $table, $data = null){
    if (is_array($id)){
      $data = $id;
      $id = $id[$this->args["col_id"]];
    }
    
    if (!is_numeric($id) || $id < 0){
      throw new Exception("ID was not valid \"" . $id . "\".");
    }
    
    if (!array_key_exists($table, $this->rows)){
      $this->rows[$table] = array();
    }
    
    if (!array_key_exists($id, $this->rows[$table])){
      $this->rows[$table][$id] = new Row($this, $table, $id, $data, array("col_id" => $this->args["col_id"]));
    }
    
    return $this->rows[$table][$id];
  }
  
  /** Closes the connection to the database. */
  function close(){
    $this->conn->close();
    unset($this->conn);
  }
  
  /** Returns the last inserted ID. */
  function getLastID(){
    return $this->conn->getLastID();
  }
  
  function last_id(){
    return $this->getLastID();
  }
  
  /** Only has the function because it should be compatible with the DBConn-framework. */
  function getLastInsertedID(){
    return $this->getLastID();
  }
  
  /** Sets the options of the object. */
  function setOpts($args){
    foreach($args AS $key => $value){
      $this->args[$key] = $value;
    }
    
    if ($this->args["autoconnect"] and !$this->conn){
      $this->connect();
    }
  }
  
  function cloneself(){
    return new Baza($this->args);
  }
  
  /** Performs a query. */
  function query($sql){
    return $this->conn->query($sql);
  }
  
  function queryUbuf($sql){
    return $this->conn->queryUbuf($sql);
  }
  
  /** Fetches a result. */
  function fetch($ident){
    if (is_object($ident) && get_class($ident) == "Result"){
      $ident = $ident->result;
    }
    
    return $this->conn->fetch($ident);
  }
  
  /** Returns an escape string (mysql_escape_string etc) for the current database-driver. */
  function sql($string){
    return $this->conn->sql($string);
  }
  
  function escape_table($string){
    return $this->conn->escape_table($string);
  }
  
  function escape_column($string){
    if ($this->conn->sep_col and strpos($string, $this->conn->sep_col) !== false){
      throw new exception("Possible trying to hack the database!");
    }
    
    if (is_object($string)){
      throw new exception("Does not support objects.");
    }
    
    return strval($string);
  }
  
  /** Used with transactions. */
  function insert_autocommit($value){
    if (is_numeric($value)){
      $this->trans_begin();
      $this->insert_autocommit = $value;
      $this->insert_countcommit = 0;
    }elseif($value == false){
      if ($this->insert_countcommit > 0){
        $this->trans_commit();
      }
      
      unset($this->insert_autocommit);
      unset($this->insert_countcommit);
    }else{
      throw new Exception("Invalid argument (" . $value . ".");
    }
  }
  
  /** A quick way to do a simple select and fetch the result.. */
  function selectfetch($table, $where = null, $args = null){
    $result = $this->select($table, $where, $args);
    $results = array();
    while($data = $result->fetch($result)){
      if (array_key_exists("return", $args) and $args["return"] == "array"){
        $results[] = $data;
      }else{
        $results[] = $this->getRow($data, $table);
      }
    }
    
    return $results;
  }
  
  /** Selects a single row and returns it. */
  function single($table, $where, $args = array()){
    $args["limit"] = "1";
    return $this->select($table, $where, $args)->fetch();
  }
  
  /** A quick way to do a simple select. */
  function select($table, $where = null, $args = null){
    return $this->conn->select($table, $where, $args);
  }
  
  /** A quick way to insert a new row into the database. */
  function insert($table, $arr, $args = array()){
    return $this->conn->insert($table, $arr, $args);
  }
  
  function insertMulti($table, $rows){
    if (method_exists($this->conn, "insertMulti")){
      $this->conn->insertMulti($table, $rows);
    }else{
      foreach($rows AS $row){
        $this->conn->insert($table, $row, array());
      }
    }
  }
  
  /** A quick way to do a simple update. */
  function update($table, $data, $where = null){
    $this->conn->update($table, $data, $where);
  }
  
  /** A quick way to do a simple delete. */
  function delete($table, $where = null){
    if (!is_null($where) && !is_array($where)){
      throw new exception("The where-parameter was not an array or null.");
    }
    
    $this->conn->delete($table, $where);
  }
  
  function optimize($tables){
    $this->conn->optimize($tables);
  }
  
  function countRows($res){
    return $this->conn->countRows($res->result);
  }
  
  /** Returns the SQL for the query based on an array. */
  function makeWhere($where){
    return $this->conn->makeWhere($where);
  }
  
  function transaction($func){
    if (!method_exists($this->conn, "beginTransaction"))
      throw new \Exception("Driver does not support transactions.");
    
    $this->conn->beginTransaction();
    $this->doingTransaction = true;
    
    try{
      $func();
    }catch(exception $e){
      $this->conn->rollBackTransaction();
      throw $e;
    }
    
    $this->conn->endTransaction();
  }
  
  function trans_begin(){
    if (method_exists($this->conn, "endTransaction")){
      $this->conn->trans_begin();
    }
  }
  
  function trans_commit(){
    if (method_exists($this->conn, "trans_commit")){
      $this->conn->trans_commit();
    }
  }
  
  function date_format($unixt, $args = array()){
    return $this->conn->date_format($unixt, $args);
  }
  
  function date_in($str){
    return $this->conn->date_in($str);
  }
}

