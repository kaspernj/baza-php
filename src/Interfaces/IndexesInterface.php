<?php

namespace Baza\Interfaces;

interface IndexesInterface{
  function addIndex(\Baza\Table $table, $columns, $name = null, $args = null);
  function getIndexSQL(\Baza\Index $index);
  function getIndexes(\Baza\Table $table);
  function removeIndex(\Baza\Table $table, \Baza\Index $index);
}
