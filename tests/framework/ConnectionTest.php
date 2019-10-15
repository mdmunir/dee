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
        $db = new \dee\base\Conncetion();
        $db->dsn = '@tests/data/dee-test.db';
        
    }
    
    public function testRawSql()
    {
        
    }
}
