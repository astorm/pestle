<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionNamesFromCode');

class FunctionNameParsingTest extends PestleBaseTest
{
    protected function loadFixture($method)
    {
        return file_get_contents(__DIR__ . '/fixtures/' . 
            preg_replace('%^.*?::test%','',$method) . '.php' );
    }
    
    protected function assertSameValues($array1, $array2)
    {
        sort($array1);
        sort($array2);    
        $this->assertEquals($array1, $array2);
    }
    
    public function testApplication()
    {
        $code = $this->loadFixture(__METHOD__);
        $names = getFunctionNamesFromCode($code);
        $names = array_map(function($token){
            return $token->token_value;
        }, $names);                
        
        $fixture = [
            '__construct',
            'run',
            'phpBinary',
            'artisanBinary',
            'formatCommandString',
            'starting',
            'bootstrap',
            'forgetBootstrappers',
            'call',
            'output',
            'add',
            'addToParent',
            'resolve',
            'resolveCommands',
            'getDefaultInputDefinition',
            '(',
            'getLaravel',                    
            'getEnvironmentOption',            
        ];

        $this->assertSameValues($names, $fixture);
    }
    
    public function testCommand()
    {
        $code = $this->loadFixture(__METHOD__);
        $names = getFunctionNamesFromCode($code);
        $names = array_map(function($token){
            return $token->token_value;
        }, $names);                
        
        $fixture = [
            '(',
            '__construct',
            'alert',
            'anticipate',
            'argument',
            'arguments',
            'ask',
            'askWithCompletion',
            'call',
            'callSilent',
            'choice',
            'comment',
            'configureUsingFluentDefinition',
            'confirm',
            'createInputFromArguments',
            'error',
            'execute',
            'getArguments',
            'getLaravel',
            'getOptions',
            'getOutput',
            'hasArgument',
            'hasOption',
            'info',
            'line',
            'option',
            'options',
            'parseVerbosity',
            'question',
            'run',
            'secret',
            'setLaravel',
            'setVerbosity',
            'specifyParameters',
            'table',
            'warn',          
        ];

        $this->assertSameValues($names, $fixture);
    }    
    
    public function testConfirmableTrait()
    {
        $code = $this->loadFixture(__METHOD__);
        $names = getFunctionNamesFromCode($code);
        $names = array_map(function($token){
            return $token->token_value;
        }, $names);                
        
        $fixture = [
            '(',
            'confirmToProceed',
            'getDefaultConfirmCallback',         
        ];

        $this->assertSameValues($names, $fixture);
    }    
    
    public function testDetectsApplicationNamespace()
    {
        $code = $this->loadFixture(__METHOD__);
        $names = getFunctionNamesFromCode($code);
        $names = array_map(function($token){
            return $token->token_value;
        }, $names);                
        
        $fixture = [
            'getAppNamespace',         
        ];

        $this->assertSameValues($names, $fixture);
    }    
    
    public function testGeneratorCommand()
    {
        $code = $this->loadFixture(__METHOD__);
        $names = getFunctionNamesFromCode($code);
        $names = array_map(function($token){
            return $token->token_value;
        }, $names);                
        
        $fixture = [
            '__construct',
            'alreadyExists',
            'buildClass',
            'getArguments',
            'getDefaultNamespace',
            'getNameInput',
            'getNamespace',
            'getPath',
            'getStub',
            'handle',
            'makeDirectory',
            'qualifyClass',
            'replaceClass',
            'replaceNamespace',
            'rootNamespace',         
        ];

        $this->assertSameValues($names, $fixture);
    }    
    
    public function testOutputStyle()
    {
        $code = $this->loadFixture(__METHOD__);
        $names = getFunctionNamesFromCode($code);
        $names = array_map(function($token){
            return $token->token_value;
        }, $names);                
        
        $fixture = [
            '__construct',
            'isDebug',
            'isQuiet',
            'isVerbose',
            'isVeryVerbose',         
        ];

        $this->assertSameValues($names, $fixture);
    }    
    
    public function testParser()
    {
        $code = $this->loadFixture(__METHOD__);
        $names = getFunctionNamesFromCode($code);
        $names = array_map(function($token){
            return $token->token_value;
        }, $names);                
        
        $fixture = [
            'extractDescription',
            'name',
            'parameters',
            'parse',
            'parseArgument',
            'parseOption',         
        ];

        $this->assertSameValues($names, $fixture);
    }                        
}