<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use jugger\criteria\MysqlCriteriaBuilder;
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
        return new MysqlCriteriaBuilder($driver);
    }

    public function testGeneral()
    {
		$criteria = new LogicCriteria("or");
        $criteria->add([
            new LogicCriteria("and", [
                new EqualCriteria('col1', 1),
                new LikeCriteria('col2', '%2%'),
            ]),
            new LogicCriteria("and", [
                new RegexpCriteria('col3', '(\d+)'),
                new CompareCriteria('col4', '<', 4),
                new BetweenCriteria('col5', 123, 456),
            ])
        ]);
        $builder = $this->createBuilder();
        $sql = $builder->build($criteria);
        $this->assertEquals(
            $sql,
            "((`col1` = '1') AND (`col2` LIKE '%2%')) OR ((`col3` REGEXP '(\\\\d+)') AND (`col4` < '4') AND (`col5` BETWEEN '123' AND '456'))"
        );
    }

    public function testLike()
    {
        $crit = new LikeCriteria("col", "%value%");
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "`col` LIKE '%value%'"
        );
        $this->assertEquals(
            $builder->buildLike($crit),
            $builder->build($crit)
        );
    }

    public function testEqual()
    {
        $crit = new EqualCriteria("col", "%value%");
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "`col` = '%value%'"
        );
        $this->assertEquals(
            $builder->buildEqual($crit),
            $builder->build($crit)
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

        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "(`col` LIKE '') AND (`col` = '')"
        );
        $this->assertEquals(
            $builder->buildLogic($crit),
            $builder->build($crit)
        );
    }

    public function testCompare()
    {
        $crit = new CompareCriteria("col", ">", 1);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "`col` > '1'"
        );
        $this->assertEquals(
            $builder->buildCompare($crit),
            $builder->build($crit)
        );
    }

    public function testRegexp()
    {
        $crit = new RegexpCriteria("col", "/(\\d+)/");
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "`col` REGEXP '/(\\\\d+)/'"
        );
        $this->assertEquals(
            $builder->buildRegexp($crit),
            $builder->build($crit)
        );
    }

    public function testBetween()
    {
        $crit = new BetweenCriteria("col", 10, 20);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "`col` BETWEEN '10' AND '20'"
        );
        $this->assertEquals(
            $builder->buildBetween($crit),
            $builder->build($crit)
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
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "(`column1` = 'value') AND ((`column2` LIKE 'value') OR (`column3` IN (1, 2, 3)))"
        );
    }

    public function testIn()
    {
        $crit = new InCriteria("column", [1,2,3]);
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "`column` IN (1, 2, 3)"
        );
        $this->assertEquals(
            $builder->buildIn($crit),
            $builder->build($crit)
        );

        $crit = new InCriteria("column", "string");
        $builder = $this->createBuilder();
        $this->assertEquals(
            $builder->build($crit),
            "`column` IN (string)"
        );
        $this->assertEquals(
            $builder->buildIn($crit),
            $builder->build($crit)
        );
    }
}
