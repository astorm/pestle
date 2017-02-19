<?php
namespace Pulsestorm\Magento2\Cli\Export_Module;
error_reporting(E_ALL);
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');
pestle_import('Pulsestorm\Pestle\Importer\getPathFromFunctionName');

function getNextTConstantEncapsedStringFromTokenArray($tokens, $index)
{    
    $tokens = array_slice($tokens, $index+1);  
    foreach($tokens as $token)
    {   
        if($token->token_name === 'T_CONSTANT_ENCAPSED_STRING')
        {
            return $token;
        }
    }
}

function isTokenFunction($token, $function, $tokens, $index)
{
    if(!isset($tokens[$index+1])) { return false; }
    if($tokens[$index+1]->token_name === 'T_WHITESPACE')
    {
        $index++;
        return isTokenFunction($tokens[$index], $function, $tokens, $index);
    }

    return  !($token->token_value !== $function || 
            $tokens[$index+1]->token_value !== '(');
}

function isTokenPestleImport($token, $tokens, $index)
{
    return isTokenFunction($token, 'pestle_import', $tokens, $index);
}

function removeWhitespaceFromTokens($tokens)
{
    $tokens = array_filter($tokens, function($token){
        return $token->token_name !== 'T_WHITESPACE';
    });
    return $tokens;
}

function getFunctionNamesFromPestleImports($tokens)
{
    $tokens = removeWhitespaceFromTokens($tokens);
    $tokens = array_values($tokens);
    $imports=[];
    foreach($tokens as $index=>$token)
    {    
        if(!isTokenPestleImport($token, $tokens, $index)){ continue;}
        $imports[] = getNextTConstantEncapsedStringFromTokenArray($tokens, $index);
    }    
    
    $importedNames = array_map(function($token){
        return trim($token->token_value,"'\"");
    }, $imports);
    
    $return = [];
    foreach($importedNames as $name)
    {
        $parts = explode('\\', $name);
        $return[$name] = array_pop($parts);
    }
    
    return $return;
}

function getRealNamespaceFromImportedFunction($function)
{
    $path = getPathFromFunctionName($function);
    $tokens = pestle_token_get_all(file_get_contents($path));
    
    $flag = false;
    $tokensNamespace = [];
    foreach($tokens as $token)
    {
        if($token->token_value === 'namespace')
        {
            $flag = true;
            continue;
        }
        if(!$flag) { continue; }
        if($token->token_value === ';'){break;};
        $tokensNamespace[] = $token;
    }
    $asString = trim(
        implode('',
            array_map(function($token){
                return $token->token_value;
            }, $tokensNamespace)
        )
    );
    return trim($asString,'\\');
}

function replaceFunctionCallWithFunctionCallInTokens($current, $new, $tokens)
{
    foreach($tokens as $index=>$token)
    {
        if($tokens[$index]->token_value !== $current) {continue;}
        if(!isTokenFunction($token, $current, $tokens, $index)){continue;}
        $token->token_value = '\\' . getRealNamespaceFromImportedFunction($new) . '\\' . $current;
        $tokens[$index] = $token;
    }
    
    return $tokens;
}

function changeToBlockedNamespace($string)
{
    $string = preg_replace('%(^.)%m',"\t$1",$string);
    $string = preg_replace('%(namespace.+?);%',"$1{",$string);
    $string = str_replace("\t<?php",'',$string);
    $string = str_replace("\tnamespace",'namespace',$string);
    $string .= "\n" . '}';    
    return $string;
}

function getTokensAsString($tokens)
{
    $values = array_map(function($token){
        return $token->token_value;
    }, $tokens);
    
    $string = implode('',$values);
    // $string = changeToBlockedNamespace($string);

    return $string;
}

function replaceNamespacedFunction($tokens)
{
    $function_names = getFunctionNamesFromPestleImports($tokens);
    foreach($function_names as $full=>$short)
    {
        $tokens = replaceFunctionCallWithFunctionCallInTokens(
            $short, $full, $tokens);
    }
    return $tokens;
}

function removePestleImports($tokens)
{
    $tokensCleaned = [];
    $flag = true;
    foreach($tokens as $index=>$token)
    {
        if(isTokenPestleImport($token, $tokens, $index))
        {
            $flag = false;
        }
        
        if($flag)
        {
            $tokensCleaned[] = $token;
        }
        else
        {
            if($token->token_value === ';')
            {
                $flag = true;
            }
        }
    }
    return $tokensCleaned;
}

function turnIntoBlockedNamespace($tokens)
{
    $flag = false;
    foreach($tokens as $index=>$token)
    {
        if($token->token_value === 'namespace')
        {
            $flag = true;
        }        
        if(!$flag) {continue;}        
        if($token->token_value !== ';'){continue;}
        $token->token_value = '{';
        $flag = false;
    }
    $tokens[] = (object) [
        'token_value'=>'}',
        'token_name'=>'T_SINGLE_CHAR'
    ];
    return $tokens;
}

function removePhpTag($tokens)
{
    $tokens = array_filter($tokens, function($token){
        return $token->token_name !== 'T_OPEN_TAG';
    });
    return array_values($tokens);
}

function getFilesFromArguments($arguments)
{
    global $argv;
    array_shift($argv);
    array_shift($argv);
    if(count($argv) === count($arguments))
    {
        $files = [$arguments['module_file']];
    }
    else
    {
        $files = $argv;
    }
    
    $files = array_filter($files, function($file){
        return 
            strpos($file, 'pulsestorm/pestle/importer/module.php') === false &&
            strpos($file, 'pulsestorm/pestle/runner/module.php') === false ;
    });
    return $files;
}

/**
* ALPHA: Seems to be a start at exporting a pestle module as functions. 
* @command export_module
* @argument module_file Which file?
*/
function pestle_cli($arguments)
{    
    $files = getFilesFromArguments($arguments);
    foreach($files as $file)
    {
        $tokens = pestle_token_get_all(file_get_contents($file));    
        $tokens = replaceNamespacedFunction($tokens);
        $tokens = removePestleImports($tokens);
        $tokens = removePhpTag($tokens);
        $tokens = turnIntoBlockedNamespace($tokens);
        //collect names of all functions    
        $string = getTokensAsString($tokens);    
        // output("##PROCESSING: $file");
        output($string);
        // output("##DONE PROCESSING: $file");        
    }
}