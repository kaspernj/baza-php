<?php
  class MssqlTables implements TablesInterface{
    public $baza;
    public $tables = array();
    private $tables_changed = true;
    
    function __construct(\Baza $baza){
      $this->baza = $baza;
    }
    
    function getTables(){
      if ($this->tables_changed){
        $f_gt = $this->baza->query("SELECT * FROM INFORMATION_SCHEMA.Tables WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
        while($d_gt = $f_gt->fetch()){
          $this->tables[$d_gt["TABLE_NAME"]] = new \Baza\Table($this->baza, array(
              "name" => $d_gt["TABLE_NAME"]
            )
          );
        }
        
        $this->tables_changed = false;
      }
      
      return $this->tables;
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
    
    function renameTable(Table $table, $newname){
      throw new Exception("Not supported.");
    }
    
    function dropTable(Table $table){
      throw new Exception("Not supported.");
    }
    
    function truncateTable(Table $table){
      throw new Exception("Not supported.");
    }
  }

