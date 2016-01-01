<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\parseArgvIntoCommandAndArgumentsAndOptions');

class ArgumentParsingTest extends PestleBaseTest
{
//     public function testParse1()
//     {
//         $args = [
//             'fake-script.php',
//             'command_name',
//             'ahha',
//             '--foo',
//             'science',
//             '--baz',
//             'hello',
//             'there',
//             'again'
//             ];
//             
//         $results = parseArgvIntoCommandAndArgumentsAndOptions($args);            
//         $this->assertResults($results);            
//     }

    public function testParse2()
    {
        $args = [
            'fake-script.php',
            'command_name',
            'ahha',
            '--foo=',
            'science',
            '--baz',
            'hello',
            'there',
            'again'
            ];
        $results = parseArgvIntoCommandAndArgumentsAndOptions($args);        
        $this->assertResults($results);            
    }

    /**
    * --foo =science should be interpreted as passing in '=science',
    * otherwise there's not way TO pass in that value
    */
    public function testParse3()
    {
        $args = [
            'fake-script.php',
            'command_name',
            'ahha',            
            '--foo',
            '=science',
            '--baz',
            'hello',
            'there',
            'again'            
            ];
        $results = parseArgvIntoCommandAndArgumentsAndOptions($args);            
        
        $this->assertEquals($results['command'], 'command_name');
        $this->assertEquals($results['arguments'], ['ahha','there','again']);
        $this->assertEquals($results['options'], [
            'foo'=>'=science',
            'baz'=>'hello']
        );        
    }

    public function testParse4()
    {
        $args = [
            'fake-script.php',
            'command_name',
            'ahha',             
            '--foo=science',
            '--baz',
            'hello',
            'there',
            'again'  
            ];
        $results = parseArgvIntoCommandAndArgumentsAndOptions($args);            
        $this->assertResults($results);            
    }

    public function testParseArgumentOnly()
    {
        $args = [
            'fake-script.php',
            'command_name',
            'ahha',             
            'hello',
            'there',
            'again'  
            ];
            
        $results = parseArgvIntoCommandAndArgumentsAndOptions($args);            
        
        $this->assertEquals($results['command'], 'command_name');
        $this->assertEquals($results['arguments'], 
            ['ahha','hello','there','again']);
        $this->assertEquals($results['options'], 
            []);            
    }
          
    protected function assertResults($results)
    {
        $this->assertEquals($results['command'], 'command_name');
        $this->assertEquals($results['arguments'], ['ahha','there','again']);
        $this->assertEquals($results['options'], [
            'foo'=>'science',
            'baz'=>'hello']
        );    
    }
            
    
}