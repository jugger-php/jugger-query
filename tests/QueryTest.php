<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use jugger\query\Query;
use jugger\query\MysqlQueryBuilder;

class QueryTest extends TestCase
{
    public function createQuery()
    {
        return (new Query)->from('table');
    }

    public function buildQuery(Query $query)
    {
        $driver = new \mysqli('localhost', 'root', '');
        return (new MysqlQueryBuilder($driver))->build($query);
    }

    public function testFrom()
    {
        $query = (new Query)->from('table');
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT * FROM table"
        );

        $query = (new Query)->from([
            'table1',
            'alias3' => 'table2',
            'table4' => (new Query)->from('table'),
        ]);
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT * FROM `table1`, `table2` AS `alias3`, (SELECT * FROM table) AS `table4`"
        );
    }

    public function testSelect()
    {
        $query = $this->createQuery();
        $query->select('col1, `col2` as alias3');
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT col1, `col2` as alias3 FROM table"
        );

        $query = $this->createQuery();
        $query->select([
            'col1',
            'alias3' => 'col2',
            'col4' => (new Query)->select('id')->from('table'),
        ]);
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT `col1`, `col2` AS `alias3`, (SELECT id FROM table) AS `col4` FROM table"
        );
    }

    public function testDistinct()
    {
        $query = $this->createQuery();
        $query->distinct();
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT DISTINCT * FROM table"
        );

        $query = $this->createQuery();
        $query->distinct(false);
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT * FROM table"
        );
    }

    public function testJoin()
    {
        $on = "table.id = table2.id";
        $query = $this->createQuery();
        $query->join("INNER", "table2", $on);
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT * FROM table INNER JOIN `table2` ON table.id = table2.id"
        );

        $query1 = $this->createQuery()->join("INNER", "table2", $on);
        $query2 = $this->createQuery()->innerJoin("table2", $on);
        $this->assertEquals(
            $this->buildQuery($query1),
            $this->buildQuery($query2)
        );

        $query1 = $this->createQuery()->join("LEFT", "table2", $on);
        $query2 = $this->createQuery()->leftJoin("table2", $on);
        $this->assertEquals(
            $this->buildQuery($query1),
            $this->buildQuery($query2)
        );

        $query1 = $this->createQuery()->join("RIGHT", "table2", $on);
        $query2 = $this->createQuery()->rightJoin("table2", $on);
        $this->assertEquals(
            $this->buildQuery($query1),
            $this->buildQuery($query2)
        );
    }

    public function testOrderBy()
    {
        $query = $this->createQuery();
        $query->orderBy("id ASC, name DESC");
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT * FROM table ORDER BY id ASC, name DESC"
        );
    }

    public function testGroupBy()
    {
        $query = $this->createQuery();
        $query->groupBy("id, name");
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT * FROM table GROUP BY id, name"
        );
    }

    public function testOffset()
    {
        $query = $this->createQuery();
        $query->limit(10);
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT * FROM table LIMIT 10"
        );

        $query = $this->createQuery();
        $query->limit(10, 5);
        $this->assertEquals(
            $this->buildQuery($query),
            "SELECT * FROM table LIMIT 5, 10"
        );
    }
}
