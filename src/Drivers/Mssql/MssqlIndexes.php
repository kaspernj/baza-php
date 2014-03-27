<?php
  class MssqlIndexes implements IndexesInterface{
    private $baza;
    
    function __construct(\Baza $baza){
      $this->baza = $baza;
    }
    
    function addIndex(Table $table, $cols, $name = null, $args = null){
      throw new Exception("Not supported.");
    }
    
    function getIndexSQL(Index $index){
      throw new Exception("Not supported.");
    }
    
    function getIndexes(Table $table){
      if ($table->indexes_changed){
        $f_gi = $this->baza->query("
          SELECT
            id,
            name,
            indid,
            OBJECT_NAME(id) AS TableName
          
          FROM
            sysindexes
          
          WHERE
            OBJECT_NAME(sysindexes.id) = '" . $this->baza->sql($table->get("name")) . "'
        ");
        while($d_gi = $f_gi->fetch()){
          $columns = array();
          $f_gik = $this->baza->query("
            SELECT
              syscolumns.name,
              OBJECT_NAME(syscolumns.id) AS TableName
            
            FROM
              sysindexkeys,
              syscolumns
            
            WHERE
              sysindexkeys.id = '" . $this->baza->sql($d_gi["id"]) . "' AND
              sysindexkeys.indid = '" . $this->baza->sql($d_gi["indid"]) . "' AND
              syscolumns.id = '" . $this->baza->sql($d_gi["id"]) . "' AND
              syscolumns.colid = sysindexkeys.colid
            
            ORDER BY
              syscolumns.name
          ");
          while($d_gik = $f_gik->fetch()){
            $columns[$d_gik["name"]] = $table->getColumn($d_gik["name"]);
          }
          
          if (count($columns) > 0){ //to avoid the actual system indexes with no columns which confuses...
            $table->indexes[$d_gi["name"]] = new Index($table, array(
                "name" => $d_gi["name"],
                "columns" => $columns
              )
            );
          }
        }
        
        $table->indexes_changed = false;
      }
      
      return $table->indexes;
    }
    
    function removeIndex(Table $table, Index $index){
      throw new Exception("Not supported.");
    }
  }

