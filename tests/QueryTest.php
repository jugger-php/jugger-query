<?php

namespace tests;

use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public function testName()
    {
		$q = new Query();
        $q->select("id, name");
        $q->from()
            ->orderBy("id ASC")
            ->limit(10);

        $sql = (new MysqlQueryBuilder())->build($q);
        var_dump($sql);
    }
}
