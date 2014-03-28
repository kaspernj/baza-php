<?php

namespace Baza\Interfaces;

interface TablesInterface{
  function __construct(\Baza $baza);
  function getTables();
  function getTable($name);
  function createTable($name, $cols, $args = null);
  function renameTable(\Baza\Table $table, $newname);
  function dropTable(\Baza\Table $table);
  function truncateTable(\Baza\Table $table);
}
