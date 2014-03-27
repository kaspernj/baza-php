<?php
  interface IndexesInterface{
    function addIndex(Table $table, $columns, $name = null, $args = null);
    function getIndexSQL(Index $index);
    function getIndexes(Table $table);
    function removeIndex(Table $table, Index $index);
  }

