<?php

class MysqlTables implements TablesInterface{
  public $baza;
  public $tables = array();
  public $tables_changed = true;
  
  function __construct(\Baza $baza){
    $this->baza = $baza;
  }
  
  function getTables(){
    if ($this->tables_changed){
      $f_gt = $this->baza->query("SHOW TABLE STATUS");
      while($d_gt = $f_gt->fetch()){
        if (!$this->tables[$d_gt["Name"]]){
          $this->tables[$d_gt["Name"]] = new Table($this->baza, array(
            "name" => $d_gt["Name"],
            "engine" => $d_gt["Engine"],
            "collation" => $d_gt["Collation"],
            "rows" => $d_gt["Rows"]
          ));
        }
      }
      
      $this->tables_changed = false;
    }
    
    return $this->tables;
  }
  
  function renameTable(Table $table, $newname){
    $this->baza->query("ALTER TABLE " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table . " RENAME TO " . $this->baza->conn->sep_table . $newname . $this->baza->conn->sep_table);
    
    unset($this->tables[$table->get("name")]);
    $table->data["name"] = $newname;
    $this->tables[$newname] = $table;
  }
  
  function createTable($tablename, $cols, $args = null){
    $sql = "CREATE TABLE " . $this->baza->conn->sep_table . $tablename . $this->baza->conn->sep_table . " (";
    $prim_keys = array();
    
    $first = true;
    foreach($cols AS $col){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->columns()->getColumnSQL($col);
    }
    $sql .= ")";
    
    if ($args["returnsql"]){
      return $sql;
    }
    
    $this->baza->query($sql);
    $this->tables_changed = true;
  }
  
  function dropTable(Table $table){
    $this->baza->query("DROP TABLE " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table);
    unset($this->tables[$table->get("name")]);
  }
  
  function truncateTable(Table $table){
    $this->baza->query("TRUNCATE " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table);
  }
  
  function optimizeTable(Table $table){
    $this->baza->query("OPTIMIZE TABLE " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table); //vacuum the entire database.
  }
}

