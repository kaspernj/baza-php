<?php
  class MssqlColumns implements ColumnsInterface{
    private $baza;
    
    function __construct(\Baza $baza){
      $this->baza = $baza;
    }
    
    function getColumnSQL($column){
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
      
      if ($column["type"] == "varchar" && ($column["maxlength"] <= 0 || !$maxlength)){
        $column["maxlength"] = 255;
      }
      
      $sql .= $column['type'];
      
      //Defindes maxlength (and checks if maxlength is allowed on the current database-type).
      if ($column["type"] == "datetime" || $column["type"] == "date" || $column["type"] == "tinytext" || $column["type"] == "text"){
        //maxlength is not allowed in MySQL. So nothing goes here (Access can actually have a maxlength on a datetime).
      }elseif($column["maxlength"] > 0){
        $sql .= "(" . $column["maxlength"] . ")";
      }
      
      //Defines some extras (like primary key, null and default).
      if ($column["primarykey"] && !$args["skip_primary"]){
        $sql .= " PRIMARY KEY";
      }
      
      if ($column["autoincr"]){
        $sql .= " AUTO_INCREMENT";
      }
      
      if ($column["notnull"]){
        $sql .= " NOT NULL";
      }
      
      if (strlen($column["default"]) > 0 && $column["autoincr"] != true){
        $sql .= " DEFAULT " . $this->driver->sep_val . $this->baza->sql($column["default"]) . $this->driver->sep_val;
      }
      
      return $sql . $ekstra;
    }
    
    function getColumns(Table $table){
      if ($table->columns_changed){
        $f_gc = $this->baza->query("SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_NAME = '" . $this->baza->sql($table->getName()) . "' ORDER BY ORDINAL_POSITION");
        while($d_gc = $f_gc->fetch()){
          if (!$table->columns[$d_gc["COLUMN_NAME"]]){
            if ($d_gc["IS_NULLABLE"] == "NO"){
              $notnull = true;
            }else{
              $notnull = false;
            }
            
            $default = $d_gc["COLUMN_DEFAULT"];
            if (substr($default, 0, 2) == "('"){
              $default = substr($default, 2, -2);
            }elseif(substr($default, 0, 1) == "("){
              $default = substr($default, 1, -1);
            }
            
            if (substr($default, 0, 1) == "("){ //Fix two times!
              $default = substr($default, 1, -1);
            }
            
            $primarykey = false;
            $autoincr = false;
            $type = $d_gc["DATA_TYPE"];
            
            $table->columns[$d_gc["COLUMN_NAME"]] = new Column($table, array(
                "name" => $d_gc["COLUMN_NAME"],
                "type" => $type,
                "maxlength" => $d_gc["CHARACTER_MAXIMUM_LENGTH"],
                "notnull" => $notnull,
                "default" => $default,
                "primarykey" => $primarykey,
                "autoincr" =>   $autoincr
              )
            );
          }
        }
        
        $table->columns_changed = false;
      }
      
      return $table->columns;
    }
    
    function addColumns(Table $table, $columns){
      throw new Exception("Not supported.");
      $table->columns_changed = true;
    }
    
    function removeColumn(Table $table, Column $col){
      throw new Exception("Not supported.");
      unset($table->columns[$column->getName()]);
    }
    
    function editColumn(Column $col, $newdata){
      throw new Exception("Not supported.");
    }
  }

