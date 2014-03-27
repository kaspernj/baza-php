<?php

namespace Baza\Driver\Mysqli;

class MysqliColumns implements \ColumnsInterface{
  private $driver;
  
  function __construct($baza){
    $this->baza = $baza;
    $this->driver = $baza->conn;
  }
  
  function getColumnSQL($column, $args = null){
    if (!is_array($column)){
      throw new exception("Column is not an array: " . gettype($column));
    }
    
    if (!$column["name"]){
      throw new Exception("Invalid name: " . $column["name"]);
    }
    
    $sql = $this->driver->sep_col . $column['name'] . $this->driver->sep_col . " ";
    
    if ($column["type"] == "counter"){
      //This is an Access-primarykey-autoincr-column - convert it!
      $column["type"] = "int";
      $column["primarykey"] = true;
      $column["autoincr"] = true;
    }elseif($column["type"] == "bit"){
      $column["type"] = "tinyint";
    }elseif($column["type"] == "image"){
      $column["type"] = "blob";
      $column["maxlength"] = "";
    }elseif($column["type"] == "uniqueidentifier"){
      $column["type"] = "int";
      $column["autoincr"] = true;
      $column["primarykey"] = true;
      $column["default"] = "";
    }
    
    //Fix MSSQL-datetime-default-crash.
    if ($column["type"] == "datetime" and $column["default"] == "getdate()"){
      $column["default"] = "";
    }
    
    if ($column["type"] == "varchar" and !intval($column["maxlength"])){
      $column["maxlength"] = 255;
    }
    
    $sql .= $column['type'];
    
    //Defindes maxlength (and checks if maxlength is allowed on the current database-type).
    if ($column["type"] == "datetime" || $column["type"] == "date" || $column["type"] == "tinytext" || $column["type"] == "text"){
      //maxlength is not allowed in MySQL. So nothing goes here (Access can actually have a maxlength on a datetime).
    }elseif(array_key_exists("maxlength", $column)){
      $sql .= "(" . $column['maxlength'] . ")";
    }
    
    //Defines some extras (like primary key, null and default).
    if ($column["primarykey"] == true && !$args["skip_primary"]){
      $sql .= " PRIMARY KEY";
    }
    
    if ($column["autoincr"] == true){
      $sql .= " AUTO_INCREMENT";
    }
    
    if ($column["notnull"] == true){
      $sql .= " NOT NULL";
    }
    
    if (strlen($column["default"]) > 0 && $column["autoincr"] != true){
      $sql .= " DEFAULT " . $this->driver->sep_val . $this->baza->sql($column["default"]) . $this->driver->sep_val;
    }
    
    return $sql . $ekstra;
  }
  
  function getColumns(\Table $table){
    $columns = array();
    $f_gc = $this->baza->query("SHOW FULL COLUMNS FROM " . $this->driver->sep_table . $table->get("name") . $this->driver->sep_table);
    while($d_gc = $f_gc->fetch()){
      $value = "";
      $unsigned = false;
      
      if ($d_gc["Null"] == "YES"){
        $notnull = false;
      }else{
        $notnull = true;
      }
      
      if ($d_gc["Key"] == "PRI"){
        $primarykey = true;
      }else{
        $primarykey = false;
      }
      
      if ($d_gc["Extra"] == "auto_increment"){
        $autoincr = true;
      }else{
        $autoincr = false;
      }
      
      if ($d_gc['Type'] == "tinytext"){
        $maxlength = 255;
      }else{
        $maxlength = "";
      }
      
      if (preg_match("/([a-zA-Z]+)\((.+)\)(.*)$/", $d_gc["Type"], $match)){
        $type = strtolower($match[1]);
        $maxlength = $match[2];
        
        if (strpos($match[3], "unsigned") !== false) $unsigned = true;
      }else{
        $type = strtolower($d_gc["Type"]);
      }
      
      $columns[$d_gc["Field"]] = new \Column($table, array(
        "name" => $d_gc["Field"],
        "notnull" => $notnull,
        "type" => $type,
        "maxlength" => $maxlength,
        "default" => $d_gc["Default"],
        "primarykey" => $primarykey,
        "value" => $value,
        "input_type" => "mysql",
        "autoincr" => $autoincr,
        "comment" => $d_gc["Comment"],
        "unsigned" => $unsigned
      ));
    }
    
    return $columns;
  }
  
  function addColumns(Table $table, $columns){
    if (!is_array($columns)){
      throw new exception("Second argument wasnt an array of columns.");
    }
    
    foreach($columns AS $column){
      $this->baza->query("ALTER TABLE " . $this->driver->sep_table . $table->get("name") . $this->driver->sep_table . " ADD COLUMN " . $this->baza->columns()->getColumnSQL($column) . ";");
      $table->columns_changed = true;
    }
  }
  
  function removeColumn(Table $table, Column $column){
    $sql = "ALTER TABLE " . $this->driver->sep_table . $table->get("name") . $this->driver->sep_table . " DROP COLUMN " . $this->driver->sep_col . $column->get("name") . $this->driver->sep_col;
    $this->baza->query($sql);
    unset($table->columns[$column->get("name")]);
  }
  
  function editColumn(Column $col, $newdata){
    $table = $col->getTable();
    $sql = "ALTER TABLE " . $this->driver->sep_table . $table->get("name") . $this->driver->sep_table;
    
    if ($col->get("name") != $newdata["name"]){
      $sql .= " CHANGE " . $this->driver->sep_col . $col->get("name") . $this->driver->sep_col . " " . $this->getColumnSQL($newdata, array("skip_primary" => true));
    }else{
      $sql .= " MODIFY " . $this->getColumnSQL($newdata, array("skip_primary" => true));
    }
    
    $this->baza->query($sql);
    
    if ($col->get("name") != $newdata["name"]){
      unset($col->getTable()->columns[$col->get("name")]);
      $col->getTable()->columns[$newdata["name"]] = $col;
    }
  }
}

