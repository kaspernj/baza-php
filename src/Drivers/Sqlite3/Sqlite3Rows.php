<?php
  Baza::requireLocal("Interfaces/RowsInterface.php");
  
  class Sqlite3Rows implements \Baza\Interfaces\RowsInterface{
    function __construct(\Baza $baza){
      $this->baza = $baza;
      $this->driver = $this->baza->conn;
    }
    
    function getObInsertSQL(Row $row){
      $data = $row->getAsArray();
      $table = $row->getTable();
      
      return $this->getArrInsertSQL($table->getName(), $data);
    }
    
    function getArrInsertSQL($tablename, $data){
      if (!is_array($data)){
        throw new Exception("This function only accepts an array.");
      }
      
      $sql = "INSERT INTO " . $this->driver->sep_table . $tablename . $this->driver->sep_table . " (";
      
      $count = 0;
      foreach($data AS $key => $value){
        if ($count > 0){
          $sql .= ", ";
          $sql_vals .= ", ";
        }
        
        $sql .= $this->driver->sep_col . $key . $this->driver->sep_col;
        $sql_vals .= $this->driver->sep_val . $this->baza->sql($value) . $this->driver->sep_val;
        
        $count++;
      }
      
      $sql .= ") VALUES (" . $sql_vals . ");";
      
      return $sql;
    }
  }

