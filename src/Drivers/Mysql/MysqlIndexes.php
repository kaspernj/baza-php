<?php

Baza::requireLocal("Interfaces/IndexesInterface.php");

class MysqlIndexes implements IndexesInterface{
  public $baza;
  
  function __construct($baza){
    $this->baza = $baza;
  }
  
  function getIndexSQL(Index $index){
    $sql = "CREATE INDEX " . $this->baza->connob->sep_col . $index->get("name") . $this->baza->connob->sep_col . " ON " . $this->baza->connob->sep_table . $index->getTable()->get("name") . $this->baza->connob->sep_table . " (";
    $first = true;
    foreach($index->getColumns() AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->connob->sep_col . $column->get("name") . $this->baza->connob->sep_col;
    }
    $sql .= ");\n";
    
    return $sql;
  }
  
  function addIndex(Table $table, $cols, $name = null, $args = null){
    if (!$name){
      $name = "index";
      foreach($cols AS $col){
        $name .= "_" . $col->get("name");
      }
    }
    
    $sql = "CREATE INDEX " . $this->baza->connob->sep_table . $name . $this->baza->connob->sep_table . " ON " . $this->baza->connob->sep_table . $table->get("name") . $this->baza->connob->sep_table . " (";
    
    $first = true;
    foreach($cols AS $column){
      if ($first == true){
        $first = false;
      }else{
        $sql .= ", ";
      }
      
      $sql .= $this->baza->connob->sep_column . $column->get("name") . $this->baza->connob->sep_column;
    }
    
    $sql .= ")";
    
    if ($args["returnsql"]){
      return $sql;
    }
    
    $this->baza->query($sql);
    $table->indexes_changed = true;
  }
  
  function removeIndex(Table $table, Index $index){
    $sql = "DROP INDEX " . $this->baza->conn->sep_index . $index->get("name") . $this->baza->conn->sep_index . " ON " . $this->baza->conn->sep_table . $table->get("name") . $this->baza->conn->sep_table;
    $this->baza->query($sql);
    unset($table->indexes[$index->get("name")]);
  }
  
  function getIndexes(Table $table){
    if ($table->indexes_changed){
      $f_gi = $this->baza->query("SHOW INDEX FROM " . $this->baza->connob->sep_table . $table->get("name") . $this->baza->connob->sep_table);
      while($d_gi = $f_gi->fetch()){
        if ($d_gi["Key_name"] != "PRIMARY"){
          $key = $d_gi["Key_name"];
          $index[$key]["name"] = $d_gi["Key_name"];
          $index[$key]["columns"][] = $table->getColumn($d_gi["Column_name"]);
        }
      }
      
      //Making keys to numbers (as in SQLite).
      $return = array();
      if ($index){
        foreach($index AS $name => $value){
          if (!$this->indexes[$name]){
            $table->indexes[$name] = new Index($table, $value);
          }
        }
      }
      
      $table->indexes_changed = false;
    }
    
    return $table->indexes;
  }
}

