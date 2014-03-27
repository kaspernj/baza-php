<?php

Baza::requireLocal("Interfaces/IndexesInterface.php");

class Sqlite3Indexes implements IndexesInterface{
  private $baza;
  
  function __construct(\Baza $baza){
    $this->baza = $baza;
  }
  
  function addIndex(Table $table, $cols, $name = null, $args = null){
    if (!$name){
      $name = "index";
      foreach($cols AS $col){
        $name .= "_" . $col->get("name");
      }
    }
    
    $sql = "CREATE INDEX " . $this->baza->conn->sep_col . $name . $this->baza->conn->sep_col . " ON " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table . " (";
      
    $first = true;
    foreach($cols AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->conn->sep_col . $column->get("name") . $this->baza->conn->sep_col;
    }
    
    $sql .= ")";
    
    if ($args["returnsql"]){
      return $sql;
    }
    
    $this->baza->query($sql);
    $table->indexes_changed = true;
  }
  
  function getIndexSQL(Index $index){
    $sql = "CREATE INDEX " . $this->baza->conn->sep_col . $index->get("name") . $this->baza->conn->sep_col . " ON " . $this->baza->conn->sep_table . $index->table->get("name") . $this->baza->conn->sep_table . " (";
      
    $first = true;
    foreach($index->getColumns() AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->conn->sep_col . $column->get("name") . $this->baza->conn->sep_col;
    }
    
    $sql .= ")";
    
    return $sql;
  }
  
  function getIndexes(Table $table){
    if ($table->indexes_changed){
      $f_gi = $this->baza->query("PRAGMA index_list(" . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table . ")");
      while($d_gi = $f_gi->fetch()){
        if (strpos($d_gi["name"], "sqlite") !== false && strpos($d_gi["name"], "autoindex") !== false){
          //This is a SQLite-auto-index - do not show or add.
        }elseif(!array_key_exists($d_gi["name"], $table->indexes) or !$table->indexes[$d_gi["name"]]){
          $index = array();
          $index["name"] = $d_gi["name"];
          
          $first = true;
          $columns_text = "";
          $f_gid = $this->baza->query("PRAGMA index_info('" . $d_gi["name"] . "')");
          while($d_gid = $f_gid->fetch()){
            if ($first == true){
              $first = false;
            }else{
              $columns_text .= ", ";
            }
            
            $index["columns"][] = $table->getColumn($d_gid["name"]);
          }
          
          $table->indexes[$index["name"]] = new Index($table, $index);
        }
      }
      
      $table->indexes_changed = false;
    }
    
    return $table->indexes;
  }
  
  function removeIndex(Table $table, Index $index){
    $sql = "DROP INDEX " . $this->baza->conn->sep_index . $index->get("name") . $this->baza->conn->sep_index;
    $this->baza->query($sql);
    $this->baza->query("VACUUM " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table);
    unset($table->indexes[$index->get("name")]);
  }
}

