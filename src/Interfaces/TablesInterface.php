<?php
  interface TablesInterface{
    function __construct(\Baza $baza);
    function getTables();
    function createTable($name, $cols, $args = null);
    function renameTable(Table $table, $newname);
    function dropTable(Table $table);
    function truncateTable(Table $table);
  }

