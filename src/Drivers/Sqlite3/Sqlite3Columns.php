<?php

namespace Baza\Driver\Sqlite3;

class Sqlite3Columns implements \ColumnsInterface{
  private $baza;
  
  function __construct(\Baza $baza){
    $this->baza = $baza;
  }
  
  function getColumnSQL($column){
    $sql = $this->baza->conn->sep_col . $column["name"] . $this->baza->conn->sep_col . " ";
    
    $type = $column["type"];
    
    if (array_key_exists("maxlength", $column)){
      $maxlength = $column["maxlength"];
    }else{
      $maxlength = null;
    }
    
    if (array_key_exists("primarykey", $column)){
      $primarykey = $column["primarykey"];
    }else{
      $primarykey = false;
    }
    
    if (array_key_exists("autoincr", $column)){
      $autoincr = $column["autoincr"];
    }else{
      $autoincr = false;
    }
    
    if ($type == "tinyint" || $type == "mediumint"){
      $type = "int";
    }elseif($type == "enum"){
      $type = "varchar";
      $maxlength = "";
    }elseif($type == "tinytext"){
      $type = "varchar";
      $maxlength = "255";
    }elseif($type == "counter"){
      //This is an Access-primarykey-autoincr-column - convert it!
      $sql .= "int";
      $type = "int";
      
      $primarykey = true;
      $autoincr = true;
    }
    
    if ($autoincr == true && $type != "int"){
      $column["type"] = "int";
    }
    
    if ($type == "enum"){
      $type = "varchar";
      $maxlength = "255";
    }
    
    if ($type == "int"){
      $sql .= "integer";
    }elseif($type == "decimal"){
      $sql .= "varchar";
    }else{
      $sql .= $type;
    }
    
    //Defindes maxlength (and checks if maxlength is allowed on the current database-type).
    if ($type == "int" && $primarykey == true && $autoincr == true){
      //maxlength is not allowed when autoincr is true in SQLite (or else the column wont be auto incr).
    }elseif($maxlength){
      $sql .= "(" . $maxlength . ")";
    }
    
    //Defines some extras (like primary key, null and default).
    if ($primarykey == true){
      $sql .= " PRIMARY KEY";
    }
    
    if (array_key_exists("notnull", $column) && $column["notnull"] == true){
      $sql .= " NOT NULL";
    }
    
    if ((array_key_exists("default", $column) && strlen($column["default"]) > 0) || (array_key_exists("default_set", $column) && $column["default_set"] == true)){
      $sql .= " DEFAULT " . $this->baza->conn->sep_val . $this->baza->sql($column["default"]) . $this->baza->conn->sep_val;
    }
    
    return $sql;
  }
  
  function getColumns(\Table $table){
    if ($table->columns_changed){
      $f_gc = $this->baza->query("PRAGMA table_info(" . $table->get("name") . ")");
      while($d_gc = $f_gc->fetch()){
        if (!array_key_exists($d_gc["name"], $table->columns)){
          if (!$d_gc['notnull']){
            $notnull = false;
          }else{
            $notnull = true;
          }
          
          if ($d_gc['pk'] == "1"){
            $primarykey = true;
          }else{
            $primarykey = false;
          }
          
          $maxlength = "";
          if (preg_match("/([a-zA-Z]+)\(([0-9]+)\)/", $d_gc["type"], $match)){
            $type = strtolower($match[1]);
            $maxlength = $match[2];
          }else{
            $type = strtolower($d_gc["type"]);
          }
          
          if ($type == "integer"){
            $type = "int";
          }
          
          $default = substr($d_gc["dflt_value"], 1, -1); //strip slashes.
          
          $table->columns[$d_gc["name"]] = new \Column($table, array(
            "name" => $d_gc["name"],
            "notnull" => $notnull,
            "type" => $type,
            "maxlength" => $maxlength,
            "default" => $default,
            "primarykey" => $primarykey,
            "input_type" => "sqlite3",
            "autoincr" => ""
          ));
        }
      }
      
      $table->columns_changed = false;
    }
    
    return $table->columns;
  }
  
  function addColumns(Table $table, $columns){
    foreach($columns AS $column){
      if ($column["notnull"] == true){
        $column["default_set"] = true;
      }
      
      $sql = "ALTER TABLE " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table . " ADD COLUMN " . $this->baza->columns()->getColumnSQL($column) . ";";
      $this->baza->query($sql);
      $table->columns_changed = true;
    }
  }
  
  function removeColumn(Table $table, Column $column_remove){
    $tablename = $table->get("name");
    
    //Again... SQLite has no "ALTER TABLE".
    $columns = $table->getColumns();
    $indexes = $table->getIndexes();
    $tempname = $tablename . "_temp";
    $table->rename($tempname);
    
    
    //Removing the specific column from the array.
    $cols = array();
    foreach($columns AS $key => $column){
      if ($column->get("name") != $column_remove->get("name")){
        $cols[] = $column->data;
      }
    }
    
    $this->baza->tables()->createTable($tablename, $cols);
    $newtable = $this->baza->getTable($tablename);
    
    
    $sql_insert = "INSERT INTO " . $this->baza->conn->sep_table . $tablename . $this->baza->conn->sep_table . " SELECT ";
    $first = true;
    foreach($columns AS $column){
      if ($column->get("name") != $column_remove->get("name")){
        if ($first == true){
          $first = false;
        }else{
          $sql_insert .= ", ";
        }
        
        $sql_insert .= $this->baza->conn->sep_col . $column->get("name") . $this->baza->conn->sep_col;
        $newcolumns[] = $value;
      }
    }
    
    $sql_insert .= " FROM " . $this->baza->conn->sep_table . $tempname . $this->baza->conn->sep_table;
    $this->baza->query($sql_insert);
    
    
    //Creating indexes again from the array, that we saved at the beginning. In short terms this will rename the columns which have indexes to the new names, so that they wont be removed.
    foreach($indexes AS $index){
      $cols = array();
      foreach($index->getColumns() AS $column){
        $cols[] = $newtable->getColumn($column->get("name"));
      }
    }
    
    
    //Drop the temp-table.
    $table->drop();
    unset($this->columns[$column_remove->get("name")]);
  }
  
  function editColumn(Column $col, $newdata){
    $table = $col->getTable();
    $table_name = $table->get("name");
    $tempname = $table->get("name") . "_temp";
    $indexes = $this->baza->indexes()->getIndexes($table);
    $table->rename($tempname);
    
    $newcolumns = array();
    foreach($table->getColumns() AS $column){
      if ($column->get("name") == $col->get("name")){
        $newcolumns[] = $newdata;
      }else{
        $newcolumns[] = $column->data;
      }
    }
    
    //Makinig SQL for creating the new table with updated columns and executes it.
    $this->baza->tables()->createTable($table_name, $newcolumns);
    $table_new = $this->baza->getTable($table_name);
    
    //Making SQL for inserting into it from the temp-table.
    $sql_insert = "INSERT INTO '" . $table_name . "' (";
    $sql_select = "SELECT ";
    
    $count = 0;   //FIX: Used to determine, where we are in the $newcolumns-array.
    $first = true;
    foreach($table->getColumns() AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql_insert .= ", ";
        $sql_select .= ", ";
      }
      
      $sql_insert .= $newcolumns[$count]["name"];
      $sql_select .= $column->get("name") . " AS " . $column->get("name");
      
      $count++;
    }
    
    $sql_select .= " FROM " . $tempname;
    $sql_insert .= ") " . $sql_select;
    
    $this->baza->query($sql_insert);
    $table->drop(); //drop old table which has been renamed.
    
    //Creating indexes again from the array, that we saved at the beginning. In short terms this will 
    //rename the columns which have indexes to the new names, so that they wont be removed.
    $newindexes = array();
    if ($indexes){
      foreach($indexes AS $index_key => $index){
        foreach($index->getColumns() AS $column_key => $column){
          if ($column->get("name") == $col->get("name")){
            $newindexes[$index_key][] = $table_new->getColumn($newdata["name"]);
          }else{
            $newindexes[$index_key][] = $table_new->getColumn($column->get("name"));
          }
        }
      }
    }
    
    foreach($newindexes AS $key => $cols){
      $table_new->addIndex($cols);
    }
    
    $table->data = $table_new->data; //if not it will bug up, if some other code has cached this object.
    if ($col->get("name") != $newdata["name"]){
      unset($this->columns[$col->get("name")]);
      $this->columns[$newdata["name"]] = $col;
    }
  }
}
