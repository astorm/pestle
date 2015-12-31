<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';

class FirstTest extends PestleBaseTest
{
    public function testSetup()
    {
        $this->assertEquals(-1, -1);
    }
}