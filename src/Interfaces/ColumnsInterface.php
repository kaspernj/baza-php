<?php

namespace Baza\Interfaces;

interface ColumnsInterface{
  function getColumnSQL($column);
  function getColumns(\Baza\Table $table);
  function removeColumn(\Baza\Table $table, \Baza\Column $column);
  function addColumns(\Baza\Table $table, $columns);
  function editColumn(\Baza\Column $col, $newdata);
}
