<?php

namespace tests\framework;

/**
 * Description of ControllerTest
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ConnectionTest extends \tests\TestCase
{

    public function testBuildConndition()
    {
        $db = new \dee\base\Connection();
        $db->dsn = 'sqlite:@tests/data/dee-test.db';
        $where = [
            'kolom1' => 'value1',
            'kolom2' => [1, 2, 3],
        ];

        $params = [];
        $conditions = $db->buildCondition($where, $params);

        $this->assertEquals('(kolom1 = :p0) AND (kolom2 IN(:p1, :p2, :p3))', $conditions);
        $this->assertEquals([':p0' => 'value1', ':p1' => 1, ':p2' => 2, ':p3' => 3], $params);
    }

    public function testRawSql()
    {
        
    }
}
