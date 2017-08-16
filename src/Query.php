<?php

namespace jugger\query;

use jugger\criteria\Criteria;
use jugger\criteria\LogicCriteria;

class Query
{
    public $select = "*";
    public $distinct;
    public $from;
    public $joins;
    public $where;
    public $orderBy;
    public $having;
    public $groupBy;
    public $limit;
    public $offset;

    public function __construct()
    {
        $this->joins = [];
    }

    public function select($value): self
    {
        if (is_array($value) || is_string($value)) {
            $this->select = $value;
            return $this;
        }
        else {
            throw new \InvalidArgumentException("Invalide type value, must be 'array' or 'string'");
        }
    }

    public function distinct(bool $value = true): self
    {
        $this->distinct = $value;
        return $this;
    }

    public function from($value): self
    {
        if (is_array($value) || is_string($value)) {
            $this->from = $value;
            return $this;
        }
        else {
            throw new \InvalidArgumentException("Invalide type value, must be 'array' or 'string'");
        }
    }

    public function join(string $type, string $table, string $on): self
    {
        $this->joins[] = [$type, $table, $on];
        return $this;
    }

    public function innerJoin(string $table, string $on): self
    {
        return $this->join('INNER', $table, $on);
    }

    public function leftJoin(string $table, string $on): self
    {
        return $this->join('LEFT', $table, $on);
    }

    public function rightJoin(string $table, string $on): self
    {
        return $this->join('RIGHT', $table, $on);
    }

    public function where(Criteria $criteria): self
    {
        $this->where = $criteria;
        return $this;
    }

    public function andWhere(Criteria $criteria): self
    {
        if ($this->where) {
            $this->where = new LogicCriteria("and", [
                $this->where,
                $criteria
            ]);
        }
        else {
            $this->where = $criteria;
        }
        return $this;
    }

    public function orWhere(Criteria $criteria): self
    {
        if ($this->where) {
            $this->where = new LogicCriteria("or", [
                $this->where,
                $criteria
            ]);
        }
        else {
            $this->where = $criteria;
        }
        return $this;
    }

    public function having(Criteria $criteria): self
    {
        $this->having = $criteria;
        return $this;
    }

    public function andHaving(Criteria $criteria): self
    {
        if ($this->having) {
            $this->having = new LogicCriteria("and", [
                $this->having,
                $criteria
            ]);
        }
        else {
            $this->having = $criteria;
        }
        return $this;
    }

    public function orHaving(Criteria $criteria): self
    {
        if ($this->having) {
            $this->having = new LogicCriteria("or", [
                $this->having,
                $criteria
            ]);
        }
        else {
            $this->having = $criteria;
        }
        return $this;
    }

    public function orderBy(string $value): self
    {
        $this->orderBy = $value;
        return $this;
    }

    public function groupBy(string $value): self
    {
        $this->groupBy = $value;
        return $this;
    }

    public function limit(int $limit, int $offset = null): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }
}
