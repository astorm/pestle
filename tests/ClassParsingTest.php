<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Cli\Token_Parse\extractClassInformationFromClassContents');


class ClassParsingTest extends PestleBaseTest
{
    public function testOne()
    {
        $information = extractClassInformationFromClassContents('<' . '?php' . "\n" .'
namespace Foo\Bar\Baz;

use Baz\Bing\Boo;
use Baz\Bing\Magic;
class Test extends \Science\Fair{
}');
        $this->assertEquals($information['full-class'], 'Foo\Bar\Baz\Test');
        $this->assertEquals($information['full-extends'], 'Science\Fair');        
    }
    
    public function testTwo()
    {
        $information = extractClassInformationFromClassContents('<' . '?php' . "\n" .'
namespace Foo\Bar;

use Baz\Bing\Boo;
use Baz\Bing\Magic;
class Test extends Boo{
}');
        $this->assertEquals($information['full-class'], 'Foo\Bar\Test');
        $this->assertEquals($information['full-extends'], 'Baz\Bing\Boo');        
    }

    public function testThree()
    {
        $information = extractClassInformationFromClassContents('<' . '?php' . "\n" .'
namespace Foo\Bar;

use Baz\Bing\Boo;
use Baz\Bing\Magic;
class Test extends Bar\Fee{
}');
        $this->assertEquals($information['full-class'], 'Foo\Bar\Test');
        $this->assertEquals($information['full-extends'], 'Foo\Bar\Fee');        
    }

    public function testFour()
    {
        $information = extractClassInformationFromClassContents('<' . '?php' . "\n" .'
namespace Foo\Bar;

use Baz\Bing\Boo;
use Baz\Bing\Magic;

class Test extends Magic\Fi{
}');
        $this->assertEquals($information['full-class'], 'Foo\Bar\Test');
        $this->assertEquals($information['full-extends'], 'Baz\Bing\Magic\Fi');        
    }
    
}