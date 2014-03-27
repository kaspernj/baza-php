<?php

namespace Baza\Driver\Sqlite3;

class Sqlite3Tables implements \TablesInterface{
  public $baza;
  public $tables = array();
  private $tables_changed = true;
  
  function __construct(\Baza $baza){
    $this->baza = $baza;
  }
  
  function getTables(){
    $return = array();
    $f_gt = $this->baza->select("sqlite_master", array("type" => "table"), array("orderby" => "name"));
    while($d_gt = $f_gt->fetch()){
      if ($d_gt["name"] != "sqlite_sequence" and !array_key_exists($d_gt["name"], $this->tables)){
        $this->tables[$d_gt["name"]] = new \Table($this->baza, array(
            "name" => $d_gt["name"],
            "engine" => "sqlite3",
            "collation" => "sqlite3"
          )
        );
      }
    }
    
    return $this->tables;;
  }
  
  function createTable($tablename, $columns, $args = null){
    $sql = "CREATE TABLE " . $this->baza->conn->sep_table . $tablename . $this->baza->conn->sep_table . " (";
    
    $first = true;
    $primary = false;
    foreach($columns AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      /** NOTE: This fixes the limit of SQLite3 is only able to have one primary key - only the first primary key will be marked. */
      if ($primary == true && array_key_exists("primarykey", $column) && $column["primarykey"] == true){
        $column["primarykey"] = false;
      }elseif(array_key_exists("primarykey", $column) && $column["primarykey"] == true){
        $primary = true;
      }
      
      /** NOTE: If "not null" is set - then set default value to nothing. */
      if (array_key_exists("notnull", $column) && $column["notnull"] == true){
        $column["default_set"] = true;
      }
      
      $sql .= $this->baza->columns()->getColumnSQL($column);
    }
    
    $sql .= ")";
    
    if ($args["returnsql"]){
      return $sql;
    }
    
    $this->baza->query($sql);
  }
  
  function dropTable(Table $table){
    unset($this->tables[$table->get("name")]);
    $this->baza->query("DROP TABLE " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table);
  }
  
  function renameTable(Table $table, $newtable){
    $oldname = $table->get("name");
    $this->baza->query("ALTER TABLE " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table . " RENAME TO " . $this->baza->conn->sep_table . $newtable . $this->baza->conn->sep_table);
    $table->data["name"] = $newtable;
    $this->tables[$newtable] = $table;
    unset($this->tables[$oldname]);
  }
  
  function truncateTable(Table $table){
    $this->baza->query("DELETE FROM " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table);
  }
  
  function optimizeTable(Table $table){
    $this->baza->query("VACUUM"); //vacuum the entire database.
  }
}
