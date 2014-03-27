<?php

interface ColumnsInterface{
  function getColumnSQL($column);
  function getColumns(Table $table);
  function removeColumn(Table $table, Column $column);
  function addColumns(Table $table, $columns);
  function editColumn(Column $col, $newdata);
}
