<?php

namespace tests\framework;

use dee\base\Connection;
use tests\TestCase;

/**
 * Description of ControllerTest
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ConnectionTest extends TestCase
{
    public $db;

    protected function setUp()
    {
        parent::setUp();
        $this->db = new Connection();
        $this->db->dsn = 'sqlite::memory:';

        $sql = <<<SQL
DROP TABLE IF EXISTS users;
SQL;
        $this->db->execute($sql);
        $sql = <<<SQL
CREATE TABLE users(
    id INTEGER PRIMARY KEY,
    name VARCHAR(64)
)
SQL;
        $this->db->execute($sql);
    }

    public function testBuildConndition()
    {
        $where = [
            'kolom1' => 'value1',
            'kolom2' => [1, 2, 3],
        ];

        $params = [];
        $conditions = $this->db->buildCondition($where, $params);

        $this->assertEquals('(kolom1 = :p0) AND (kolom2 IN(:p1, :p2, :p3))', $conditions);
        $this->assertEquals([':p0' => 'value1', ':p1' => 1, ':p2' => 2, ':p3' => 3], $params);
    }

    public function testCrud()
    {
        $rows = [
            ['id' => 1, 'name' => 'satu'],
            ['id' => 2, 'name' => 'dua'],
            ['id' => 3, 'name' => 'tiga'],
        ];
        foreach ($rows as $row) {
            $this->db->insert('users', $row);
            $sql = "INSERT INTO users(id, name) VALUES({$row['id']}, '{$row['name']}')";
            $this->assertEquals($sql, $this->db->rawSql);
        }

        $data = $this->db->queryAll("SELECT * FROM users");
        $this->assertEquals(count($data), count($rows));

        $name = $this->db->queryScalar("SELECT name FROM users WHERE id=2");
        $this->assertEquals('dua', $name);

        $this->db->update('users', ['name' => 'telu'], ['id' => 3]);
        $sql = "UPDATE users SET name = 'telu' WHERE (id = 3)";
        $this->assertEquals($sql, $this->db->rawSql);

        $name = $this->db->queryScalar("SELECT name FROM users WHERE id=3");
        $this->assertEquals('telu', $name);

        $this->db->delete('users', ['id' => 3]);
        $sql = "DELETE FROM users WHERE (id = 3)";
        $this->assertEquals($sql, $this->db->rawSql);
        $data = $this->db->queryAll("SELECT * FROM users");
        $this->assertEquals(2, count($data));
    }
}
