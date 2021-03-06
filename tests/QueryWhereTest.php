<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use jugger\query\Query;
use jugger\query\MysqlQueryBuilder;
use jugger\criteria\SimpleLogicCriteria;
use jugger\criteria\InCriteria;
use jugger\criteria\LikeCriteria;
use jugger\criteria\LogicCriteria;
use jugger\criteria\EqualCriteria;
use jugger\criteria\RegexpCriteria;
use jugger\criteria\CompareCriteria;
use jugger\criteria\BetweenCriteria;

class QueryWhereTest extends TestCase
{
    protected function createBuilder()
    {
        $driver = new \mysqli('localhost', 'root', '');
        return new MysqlQueryBuilder($driver);
    }

    public function testSubQuery()
    {
        $query = (new Query)->from('table');
        $query->where(new SimpleLogicCriteria([
            '@id' => (new Query)->select('id')->from('table2'),
        ]));
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE (`id` IN (SELECT id FROM table2))"
        );
    }

    public function testGeneral()
    {
		$query = (new Query)->from(['tableName']);
        $query->where(
            new LogicCriteria("and", [
                new EqualCriteria('col1', 1),
                new LikeCriteria('col2', '%2%'),
            ]))
            ->orWhere(new LogicCriteria("and", [
                new RegexpCriteria('col3', '(\d+)'),
                new CompareCriteria('col4', '<', 4),
                new BetweenCriteria('col5', 123, 456),
            ]));

        $sql = $this->createBuilder()->build($query);
        $this->assertEquals(
            $sql,
            "SELECT * FROM `tableName` WHERE ((`col1` = '1') AND (`col2` LIKE '%2%')) OR ((`col3` REGEXP '(\\\\d+)') AND (`col4` < '4') AND (`col5` BETWEEN '123' AND '456'))"
        );
    }

    public function testLike()
    {
        $crit = new LikeCriteria("col", "%value%");
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE `col` LIKE '%value%'"
        );
        $this->assertEquals(
            $builder->buildLike($crit),
            $builder->buildWhere($crit)
        );
    }

    public function testEqual()
    {
        $crit = new EqualCriteria("col", "%value%");
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE `col` = '%value%'"
        );
        $this->assertEquals(
            $builder->buildEqual($crit),
            $builder->buildWhere($crit)
        );
    }

    public function testLogic()
    {
        $crit = new LogicCriteria("and");
        $crit->add([
            new LikeCriteria("col", "")
        ]);
        $crit->add([
            new EqualCriteria("col", "")
        ]);
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE (`col` LIKE '') AND (`col` = '')"
        );
        $this->assertEquals(
            $builder->buildLogic($crit),
            $builder->buildWhere($crit)
        );
    }

    public function testCompare()
    {
        $crit = new CompareCriteria("col", ">", 1);
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE `col` > '1'"
        );
        $this->assertEquals(
            $builder->buildCompare($crit),
            $builder->buildWhere($crit)
        );
    }

    public function testRegexp()
    {
        $crit = new RegexpCriteria("col", "/(\\d+)/");
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE `col` REGEXP '/(\\\\d+)/'"
        );
        $this->assertEquals(
            $builder->buildRegexp($crit),
            $builder->buildWhere($crit)
        );
    }

    public function testBetween()
    {
        $crit = new BetweenCriteria("col", 10, 20);
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE `col` BETWEEN '10' AND '20'"
        );
        $this->assertEquals(
            $builder->buildBetween($crit),
            $builder->buildWhere($crit)
        );
    }

    public function testSimpleCriteria()
    {
        $crit = new SimpleLogicCriteria([
            'column1' => 'value',
            [
                'or',
                '%column2' => 'value',
                '@column3' => [1,2,3],
            ]
        ]);
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE (`column1` = 'value') AND ((`column2` LIKE 'value') OR (`column3` IN (1, 2, 3)))"
        );
    }

    public function testIn()
    {
        $crit = new InCriteria("column", [1,2,3]);
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE `column` IN (1, 2, 3)"
        );
        $this->assertEquals(
            $builder->buildIn($crit),
            $builder->buildWhere($crit)
        );

        $crit = new InCriteria("column", "string");
        $query = (new Query)->from('table')->where($crit);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($query),
            "SELECT * FROM table WHERE `column` IN (string)"
        );
        $this->assertEquals(
            $builder->buildIn($crit),
            $builder->buildWhere($crit)
        );
    }
}
