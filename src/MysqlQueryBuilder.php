<?php

namespace jugger\query;

use jugger\criteria\Criteria;
use jugger\criteria\InCriteria;
use jugger\criteria\LikeCriteria;
use jugger\criteria\EqualCriteria;
use jugger\criteria\LogicCriteria;
use jugger\criteria\RegexpCriteria;
use jugger\criteria\BetweenCriteria;
use jugger\criteria\CompareCriteria;

class MysqlQueryBuilder implements QueryBuilder
{
    protected $driver;

    public function __construct(\mysqli $driver)
    {
        $this->driver = $driver;
    }

    public function build(Query $query): string
    {
        $sql  = "SELECT "
            .$this->buildDistinct($query->distinct)
            .$this->buildSelect($query->select);
        $sql .= " FROM ". $this->buildFrom($query->from);
        if ($query->joins) {
            foreach ($query->joins as $join) {
                $sql .= $this->buildJoin($join);
            }
        }
        if ($query->where) {
            $sql .= " WHERE ". $this->buildWhere($query->where);
        }
        if ($query->groupBy) {
            $sql .= " GROUP BY ". $this->buildGroupBy($query->groupBy);
        }
        if ($query->having) {
            $sql .= " HAVING ". $this->buildWhere($query->groupBy);
        }
        if ($query->orderBy) {
            $sql .= " ORDER BY ". $this->buildOrderBy($query->orderBy);
        }
        if ($query->limit) {
            $sql .= " LIMIT ". $this->buildLimit($query->limit, $query->offset);
        }
        return $sql;
    }

    public function buildDistinct(?bool $distinct)
    {
        if ($distinct) {
            return "DISTINCT ";
        }
        return "";
    }

    public function buildSelect($select)
    {
        if (empty($select)) {
            return "*";
        }
        else if (is_string($select)) {
            return $select;
        }
        else if (is_array($select)) {
            $ret = [];
            foreach ($select as $key => $value) {
                if ($value instanceof Query) {
                    $value = "({$this->build($value)})";
                }
                else {
                    $value = $this->quote($value);
                }

                if (is_integer($key)) {
                    $ret[] = $value;
                }
                else  {
                    $key = $this->quote($key);
                    $ret[] = "{$value} AS {$key}";
                }
            }
            return join($ret, ", ");
        }
        else {
            throw new \InvalidArgumentException("Invalide type value, must be 'array' or 'string'");
        }
    }

    public function buildFrom($from)
    {
        if (is_string($from)) {
            return $from;
        }
        else if (is_array($from)) {
            $ret = [];
            foreach ($from as $key => $value) {
                if ($value instanceof Query) {
                    $value = "({$this->build($value)})";
                }
                else {
                    $value = $this->quote($value);
                }

                if (is_integer($key)) {
                    $ret[] = $value;
                }
                else  {
                    $key = $this->quote($key);
                    $ret[] = "{$value} AS {$key}";
                }
            }
            return join($ret, ", ");
        }
        else {
            throw new \InvalidArgumentException("Invalide type value, must be 'array' or 'string'");
        }
    }

    public function buildJoin(array $join)
    {
        list($type, $table, $on) = $join;
        $type = strtoupper($type);
        $table = $this->quote($table);
        return " {$type} JOIN {$table} ON {$on}";
    }

    public function buildGroupBy(string $value)
    {
        return $value;
    }

    public function buildOrderBy(string $value)
    {
        return $value;
    }

    public function buildLimit(int $limit, ?int $offset = null)
    {
        if ($offset) {
            return "{$offset}, {$limit}";
        }
        else {
            return $limit;
        }
    }

    public function buildWhere(Criteria $criteria)
    {
        if ($criteria instanceof BetweenCriteria) {
            return $this->buildBetween($criteria);
        }
        else if ($criteria instanceof CompareCriteria) {
            return $this->buildCompare($criteria);
        }
        else if ($criteria instanceof EqualCriteria) {
            return $this->buildEqual($criteria);
        }
        else if ($criteria instanceof LikeCriteria) {
            return $this->buildLike($criteria);
        }
        else if ($criteria instanceof LogicCriteria) {
            return $this->buildLogic($criteria);
        }
        else if ($criteria instanceof InCriteria) {
            return $this->buildIn($criteria);
        }
        else if ($criteria instanceof RegexpCriteria) {
            return $this->buildRegexp($criteria);
        }
        else {
            $criteriaClass = get_class($criteria);
            throw new \Exception("Not found class of criteria as '{$criteriaClass}'");
        }
    }

    public function quote(string $name)
    {
        return "`{$name}`";
    }

    public function escape(string $value)
    {
        return $this->driver->real_escape_string($value);
    }

    public function buildLogic(LogicCriteria $criteria)
    {
        $operands = [];
        $operator = strtoupper($criteria->getOperator());
        foreach ($criteria->getValue() as $item) {
            $sql = $this->buildWhere($item);
            $operands[] = "({$sql})";
        }
        return join($operands, " {$operator} ");
    }

    public function buildBetween(BetweenCriteria $criteria)
    {
        $column = $this->quote(
            $criteria->getColumn()
        );
        $min = (float) $criteria->getMin();
        $max = (float) $criteria->getMax();

        return "{$column} BETWEEN '{$min}' AND '{$max}'";
    }

    public function buildCompare(CompareCriteria $criteria)
    {
        return $this->buildWithOperator(
            $criteria->getColumn(),
            $criteria->getOperator(),
            $criteria->getValue()
        );
    }

    public function buildEqual(EqualCriteria $criteria)
    {
        return $this->buildWithOperator(
            $criteria->getColumn(),
            "=",
            $criteria->getValue()
        );
    }

    public function buildLike(LikeCriteria $criteria)
    {
        return $this->buildWithOperator(
            $criteria->getColumn(),
            "LIKE",
            $criteria->getValue()
        );
    }

    public function buildRegexp(RegexpCriteria $criteria)
    {
        return $this->buildWithOperator(
            $criteria->getColumn(),
            "REGEXP",
            $criteria->getValue()
        );
    }

    public function buildIn(InCriteria $criteria)
    {
        $column = $this->quote(
            $criteria->getColumn()
        );
        $value = $criteria->getValue();
        if (is_array($value)) {
            $value = join(
                ", ",
                array_map([$this, 'escape'], $value)
            );
        }
        else {
            $value = $this->escape($value);
        }
        return "{$column} IN ({$value})";
    }

    protected function buildWithOperator($column, $operator, $value)
    {
        $value = $this->escape($value);
        $column = $this->quote($column);
        return "{$column} {$operator} '{$value}'";
    }
}
