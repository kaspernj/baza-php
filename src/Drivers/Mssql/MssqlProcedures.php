<?php
  class MssqlProcedures implements ProceduresInterface{
    private $baza;
    public $procedures = array();
    
    function __construct(\Baza $baza){
      $this->baza = $baza;
    }
    
    function getProcedures(){
      $f_gp = $this->baza->query("SELECT * FROM sysobjects WHERE type = 'P' AND category = 0");
      while($d_gp = $f_gp->fetch()){
        $this->procedures = new Procedure($this->baza, array(
            "name" => $d_gp["name"]
          )
        );
      }
      
      return $this->procedures;
    }
    
    function getProcedure($name){
      if (count($this->procedures) <= 0){
        $this->getProcedures(); //creates cache.
      }
      
      return $this->procedures[$name];
    }
  }

