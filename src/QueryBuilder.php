<?php

namespace jugger\query;

interface QueryBuilder
{
    public function build(Query $query);
}
