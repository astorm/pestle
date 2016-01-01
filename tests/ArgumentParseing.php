<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\parseArgvIntoCommandAndArgumentsAndOptions');
pestle_import('Pulsestorm\Pestle\Library\parseDocBlockIntoParts');

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

    public function testEmpty()
    {
        $args = [
            'fake-script.php',
            ];  
        $results = parseArgvIntoCommandAndArgumentsAndOptions($args);        
            
        $this->assertEquals($results['command'], '');
        $this->assertEquals($results['arguments'], []);
        $this->assertEquals($results['options'], []);                  
    }
    
    public function testDocBlockParsingIncomplete()
    {

        $fixture = '/**
        * @command foo
        */';
        
        $parts = parseDocBlockIntoParts($fixture);
        $this->assertEquals($parts['one-line']    , '');
        $this->assertEquals($parts['description'] , '');
        $this->assertEquals($parts['command']      , ['foo']);        
        
    }
    
    public function testDocBlockParsing()
    {
        $fixture = '/**
        * One Line Description
        *
        * Multi Line Description
        * With Mint Frosting
        * @author Alan Storm <alan.storm@example.com>
        * @package ScienceSays
        * @var type $varName
        * @param type $var_name
        * @var type $foo        
        * @return type $var_name
        */';
        
        $parts = parseDocBlockIntoParts($fixture);
        $this->assertEquals($parts['one-line']    , 'One Line Description');
        $this->assertEquals($parts['description'] , 'Multi Line Description With Mint Frosting');
        $this->assertEquals($parts['author']      , ['Alan Storm <alan.storm@example.com>']);        
        $this->assertEquals($parts['package']     , ['ScienceSays']);                
        $this->assertEquals($parts['var']         , ['type $varName','type $foo']);                        
        $this->assertEquals($parts['return']      , ['type $var_name']);         
    }
    
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