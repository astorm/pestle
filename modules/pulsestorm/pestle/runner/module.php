<?php
namespace Pulsestorm\Pestle\Runner;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ReflectionFunction;
use Phar;
use Exception;
use function Pulsestorm\Pestle\Importer\pestle_import;

function isPhar()
{
    $running = Phar::running();
    return (boolean) $running;
}

function getBaseProjectDir()
{
    if(isPhar())
    {
        return 'phar://pestle.phar';
    }    
    return __DIR__ . '/../../../..';
}
require getBaseProjectDir()  . '/vendor/autoload.php';
require getBaseProjectDir()  . '/modules/pulsestorm/pestle/importer/module.php';

function output($string)
{
    echo $string,"\n";
}

function getNamespacesAndFunctions()
{
    $functions = get_defined_functions();
    $return    = [];
    foreach($functions['user'] as $function)
    {
        $parts      = explode('\\', $function);
        $function   = array_pop($parts);
        $namespace  = implode('\\', $parts);
        
        $return[$namespace][] = $function;
    }
    return $return;

}

function getNamespaces()
{
    $namespaces = getNamespacesAndFunctions();
    return array_keys($namespaces);
}

function stripDocComment($comment)
{
    $comment = preg_replace(['%^\*/%m', '%^/\*\*%m','%^\* %m','%^\*%m'], '', $comment);
    $parts   = explode('@', $comment);
    return trim($parts[0]);
    // return (string) $comment;
}

function parseDocCommentAts($r)
{
    $comment = $r->getDocComment();
    $comment = preg_replace(['%^\*/%m', '%^/\*\*%m','%^\* %m','%^\*%m'], '', $comment);    
    $parts   = explode('@', $comment);
    array_shift($parts);
    $parsed  = [];
    foreach($parts as $part)
    {
        $part = trim($part);
        $parts2 = preg_split('%\s%', $part);
        $name   = array_shift($parts2);
        $parsed[$name] = implode('',$parts2);
    }
    $parsed = array_map(function($thing){
        return trim($thing);
    }, $parsed);
    
    return $parsed;
}


function getAtCommandFromDocComment($r)
{
    $props = parseDocCommentAts($r);
    if(array_key_exists('command', $props))
    {
        return $props['command'];
    }
    return null;
}

function loadSerializedCommandListFromCache()
{
    $path = getCacheDir() . '/command-list.ser';   
    if(!file_exists($path))
    {
        return [];
    } 
    $command_list = unserialize(
        file_get_contents($path)
    );
    return $command_list;
}

function includeLibraryForCommand($command, $try_again=true)
{    
    $command_list = loadSerializedCommandListFromCache();
    if(!array_key_exists($command, $command_list) && $try_again)
    {
        // pestle_import('Pulsestorm\Cli\Build_Command_List');
        require_once getBaseProjectDir() . '/modules/pulsestorm/cli/build_command_list/module.php';
        buildCommandList();
        return includeLibraryForCommand($command, false);
    }    
    
    if(!array_key_exists($command, $command_list))
    {    
        output("Can't find [$command] in " . __FUNCTION__);
        exit;    
    }
    
    require_once $command_list[$command];
}

function getListOfDefinedCliFunctions()
{
    $namespaces_and_functions = getNamespacesAndFunctions();
    $namespaces = getNamespaces();
    $commands = [];
    $current_namespace = strToLower(__NAMESPACE__);
    foreach($namespaces as $namespace)
    {            
        if(in_array($namespace, ['',$current_namespace,'composer\autoload']))        
        {
            //skip self
            continue;
        }
        $main = $namespace . '\\pestle_cli'; 
        if(!function_exists($main)) { 
            output("Skippng $main -- no such function");
            continue;
        }
        $r = new ReflectionFunction($main);
        $command = getAtCommandFromDocComment($r);
        if($command)
        {
            $commands[$command] = $r;        
        }
        else
        {
            output("Skippng $main -- no @command");
        }
        //output("Importing " . $main);
    }
    return $commands;
}

function doVersionCheck()
{
    $version = phpversion();    
    if(version_compare($version, '5.6.0') === -1)
    {
        output("We're sorry, pestle requires PHP 5.6 or greater. It looks like " . 
            "you're running " . $version . '.');
        exit;    
    }
}

function doPestleImports()
{
    pestle_import('Pulsestorm\Pestle\Library\parseArgvIntoCommandAndArgumentsAndOptions');
    pestle_import('Pulsestorm\Pestle\Importer\getCacheDir');
    pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionFromCode');
    pestle_import('Pulsestorm\Cli\Build_Command_List\buildCommandList');   
    pestle_import('Pulsestorm\Pestle\Library\parseDocBlockIntoParts');     
    pestle_import('Pulsestorm\Pestle\Library\inputOrIndex');         
}

function getReflectedCommand($command_name)
{
    $reflected_commands = getListOfDefinedCliFunctions();        

    if(!array_key_exists($command_name, $reflected_commands))
    {
        output("No such command $command_name");
        exit;
    }
    $reflected_command = $reflected_commands[$command_name];  
    return $reflected_command;
}

function applyCommandNameAlias($command_name)
{
    //hardcoded for now, home of future aliasing logic
    switch($command_name)
    {
        case 'list':
            return 'list_commands';
        default:
            return $command_name;
    }
    
    throw new Exception('How did you get here?');
}

function getCommandNameFromParsedArgv($parsed_argv)
{
    $command_name   = $parsed_argv['command'];
    $command_name   = $command_name ? $command_name : 'help'; 
    $command_name   = applyCommandNameAlias($command_name);
    return $command_name;
}

function parseQuestionAndDefaultFromText($text, $new_arguments=[])
{
    $return = [
        'question'=>$text,
        'default'=>''
    ];

    if(strpos($text, '[') === false)
    {
        return $return;
    }
    
    list($return['question'], $return['default']) = explode('[',$text,2);
    $return['question'] = trim($return['question']);
    
    $return['default'] = trim($return['default']);
    $return['default'] = trim($return['default'],']');
    
    foreach($new_arguments as $key=>$value)
    {    
        //temporary hack to get the casing we want -- need general approach
        switch($return['question'])
        {
            case 'Observer Name?':
                $value = strToLower($value);
                break;
            case 'Class Name?':
                $value = str_replace('_', '\\', $value);
                break;
            case 'What\'s your plugin class name?':
                $value = str_replace('_','\\', $value);
                break;
            case 'Which Handle?':
                $value = strToLower($value);
                break;
            default:
            
        }  

        $return['default'] = str_replace('<$'.$key.'$>',$value,$return['default']);
    }
    
    return $return;
}
function limitArgumentsIfPresentInDocBlock($arguments, $parsed_doc_block, $reflected_command)
{
    if(!array_key_exists('argument', $parsed_doc_block))
    {
        return $arguments;
    }
    
    $new_arguments=[];
    $c=0;
    foreach($parsed_doc_block['argument'] as $argument)
    {
        $parts          = explode(' ', $argument,2);
        if(count($parts) !== 2)
        {
            throw new Exception("@argument $argument invalid -- needs argument name AND description");
        }
        $argument_name  = $parts[0];
        $text           = $parts[1];
        $text_parts = parseQuestionAndDefaultFromText($text, $new_arguments);
        $question = $text_parts['question'];
        $default  = trim($text_parts['default']);

        if(strpos($question, '@callback ') === 0)
        {
            $parts = explode('@callback ', $question);
            $callback = $reflected_command->getNamespaceName() . '\\' . 
                array_pop($parts);
            $r = new ReflectionFunction($callback);                
            $new_arguments[$argument_name] = $r->invokeArgs([$arguments, $c]);
        }
        else
        {
            $new_arguments[$argument_name] = inputOrIndex(
                $question,$default,$arguments,$c);
        }
        $c++;
    }
    return $new_arguments;
}

function limitOptionsIfPresentInDocBlock($options, $parsed_doc_block, $reflected_command)
{
    if(!array_key_exists('option', $parsed_doc_block))
    {
        return $options;
    }
    
    $final_options = [];
    foreach($parsed_doc_block['option'] as $option)
    {        
        list($option_name, $text)       = explode(' ', $option);        
        $final_options[$option_name]    = null;        
        if(array_key_exists($option_name, $options))
        {
            $final_options[$option_name] = $options[$option_name];
        }
    }

    return $final_options;
}

function getArgumentsAndOptionsFromParsedArgvAndDocComment($parsed_argv, $doc_block, $reflected_command)
{
    $arguments      = limitArgumentsIfPresentInDocBlock(
        $parsed_argv['arguments'], $doc_block, $reflected_command);
                        
    $options        = limitOptionsIfPresentInDocBlock(
        $parsed_argv['options'], $doc_block, $reflected_command);
        
    return [
        $arguments,
        $options
    ];            
}

function bootstrapPhp()
{
    error_reporting(E_ALL);
}

/**
* Main entry point
*/
function main($argv)
{
    bootstrapPhp();
    doVersionCheck();
    doPestleImports();

    $parsed_argv    = parseArgvIntoCommandAndArgumentsAndOptions($argv);
    $command_name   = getCommandNameFromParsedArgv($parsed_argv);        
    
    if(preg_match('%\.php%', $command_name) && file_exists($command_name))
    {        
        $parsed_argv['arguments'][] = $command_name;
        $command_name               = 'pestle_run_file';        
        $parsed_argv['command']     = $command_name;        
    }        
    //include in the library for this command
    includeLibraryForCommand($command_name);            
    $command        = getReflectedCommand($command_name);
    $doc_block      = parseDocBlockIntoParts($command->getDocComment());

    list($arguments, $options) = 
        getArgumentsAndOptionsFromParsedArgvAndDocComment($parsed_argv, $doc_block, $command);
    
    $command->invokeArgs([$arguments, $options]);
}