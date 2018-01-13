<?php
namespace Pulsestorm\Cli\Token_Parse;
use function token_get_all as php_token_get_all;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

define('STATE_PARSING',                             0);
define('STATE_FOUND_FUNCTION',                      1);
define('STATE_FOUND_SPECIFIC_FUNCTION',             2);
define('STATE_FOUND_FIRST_POST_SPECIFIC_BRACKET',   3);
define('STATE_BRACKET_COUNT_ZEROD_OUT',             4);

/**
* @command library
*/
function pestle_cli()
{
}

function getFunctionFromClass($string, $function_name)
{
    return getFunctionFromCode($string, $function_name);
}

function removeWhitespaceAndReIndex(&$tokens)
{
    $array = array_filter($tokens, function($token){
        return $token->token_name !== 'T_WHITESPACE';
    });    
    return array_values($array);
    
}

function addPhpTagIfNeeded($string)
{
    $string = trim($string);
    if($string[0] !== '<' && $string[1] !== '?')
    {
        $string = '<' . '?php ' . $string;
    }
    return $string;
}

function extractUntilSemiColon(&$tokens, $i, $toSkipValues)
{
    $tokenCount = count($tokens);
    $imports = [];
    for($i;$i<$tokenCount;$i++)
    {
        $token = $tokens[$i];
        //if we've hit a semi-colon, that's the end
        if($token->token_value === ';'){ break; }    

        //skip the stuff we don't need
        if(in_array($token->token_value, $toSkipValues))
        {
            continue;
        }
        
        $imports[] = $token;        
    }
    if(count($imports) > 1)
    {
        var_dump($imports);
        exitWithErrorMessage("Not sure what to do about dynamic pestle_import");
    }
    
    $includeString = $imports[0]->token_value;
    $includeString = preg_replace('%[\'"]%', '', $includeString);
    return $includeString;
}

function getPestleImportsFromCode($string)
{
    $string = addPhpTagIfNeeded($string);
    $tokens = pestle_token_get_all($string);
    $tokens = removeWhitespaceAndReIndex($tokens);  
    $importNames = [];
    $tokenCount = count($tokens);
    for($i=0;$i<$tokenCount;$i++)
    {
        $token = $tokens[$i];
        if($token->token_value == 'pestle_import' && $tokens[$i-1]->token_name !== 'T_NS_SEPARATOR')
        {
            $importNames[] = extractUntilSemiColon($tokens, $i, ['pestle_import','(',')']);            
        }
    }        
    return $importNames;
}

function getFunctionInfoFromCodeWithCallback($string, $callback)
{
    $string = trim($string);
    if($string[0] !== '<' && $string[1] !== '?')
    {
        $string = '<' . '?php ' . $string;
    }

    $tokens = pestle_token_get_all($string);
    $tokens = removeWhitespaceAndReIndex($tokens);    
    $tokenCount = count($tokens);
    $functionNames = [];
    for($i=0;$i<$tokenCount;$i++)
    {
        $token = $tokens[$i];
        if($token->token_name == 'T_FUNCTION' && $tokens[$i-1]->token_name !== 'T_USE')
        {
            $functionNames[] = call_user_func($callback, $tokens, $i);
        }
    }
    return $functionNames;
}

function getParsedFunctionInfoFromCode($codeAsString)
{
    $infos = getFunctionInfoFromCodeWithCallback($codeAsString, function($tokens, $position){
        $importantTokens    = [];
        // $importantTokens[]  = $tokens[$position+1];        
        
        $accessLevels = ['public','private','protected'];
        $thingsWeWant = array_merge(['static'], $accessLevels);
        
        for($i=$position-1;$i>($position-10);$i--)  //ten is arbitrary to 
        {                                           //avoid infinite back
                                                    //since I'm not confident
                                                    //I know all the ways a 
                                                    //method might be declared            
            $token = $tokens[$i];
            if(in_array($token->token_value, $thingsWeWant))
            {
                $importantTokens[] = $token;
            }
            else
            {
                break;
            }
        }
        $info = new \stdClass;
        $info->function_name = $tokens[$position+1]->token_value;
        $info->isStatic      = false;
        $info->accessLevel   = 'none';
        foreach($importantTokens as $token)
        {
            if($token->token_value === 'static')
            {
                $info->isStatic = true;
            }
            else if(in_array($token->token_value, $accessLevels))
            {
                $info->accessLevel = $token->token_value;
            }
        }
        return $info;
    });
    
    //filter out anons for now
    $infos = array_filter($infos, function($info){
        return $info->function_name !== '(';
    });
    
    //array_values to reindex
    return array_values($infos);
}

function getFunctionNamesFromCode($string)
{
    return getFunctionInfoFromCodeWithCallback($string, function($tokens, $position){
        static $anonCount = 0;
        $token = $tokens[$position+1];
        $token->is_anon_function = false;
        if('(' === $token->token_value)
        {   
            $token->is_anon_function = true;
        }
        return $token;
    });
}

function getFunctionFromCode($string, $function)
{
    $string = trim($string);
    if($string[0] !== '<' && $string[1] !== '?')
    {
        $string = '<' . '?php ' . $string;
    }

    $tokens = pestle_token_get_all($string);
    $state                              = 0;    
    $count_bracket                      = 0;
    $new_tokens                         = [];
    foreach($tokens as $token)
    {
        $token_name = $token->token_name;
        $token_value = $token->token_value;
        switch($state)
        {
            case STATE_PARSING:
                if($token_name == 'T_FUNCTION')
                {
                    $state = STATE_FOUND_FUNCTION;
                }                
                break;
            case STATE_FOUND_FUNCTION:
                if($token_name == 'T_STRING' && $token_value == $function)
                {
                    $new_tokens[] = $token;
                    $state = STATE_FOUND_SPECIFIC_FUNCTION;
                }
                if($token_name == 'T_STRING' && $token_value !== $function)
                {
                    $state = STATE_PARSING;
                }
                break;
            case STATE_FOUND_SPECIFIC_FUNCTION:
                $new_tokens[] = $token;
                if($token_name == 'T_SINGLE_CHAR' && $token_value == '{')
                {
                    $state = STATE_FOUND_FIRST_POST_SPECIFIC_BRACKET;
                    $count_bracket++;
                }
                break;
            case STATE_FOUND_FIRST_POST_SPECIFIC_BRACKET:
                $new_tokens[] = $token;
                if($token_name == 'T_SINGLE_CHAR' && $token_value == '{')
                {
                    $count_bracket++;
                }
                if($token_name == 'T_SINGLE_CHAR' && $token_value == '}')
                {
                    $count_bracket--;
                }   
                if($count_bracket === 0)
                {
                    $state = STATE_BRACKET_COUNT_ZEROD_OUT;
                }             
                break;
            case STATE_BRACKET_COUNT_ZEROD_OUT:
            
                $values = array_map(function($token){
                    return $token->token_value;
                }, $new_tokens);
                return 'function ' . implode('',  $values);
                break;
            default:
                throw new \Exception("Unknown State");
        }
    }
    //if } is the last string
    if($count_bracket === 0)
    {
        $values = array_map(function($token){
            return $token->token_value;
        }, $new_tokens);
        if(!$values)
        {
            return false;
        }
        return 'function ' . implode('',  $values);        
    }
    
    throw new \Exception("Parser Bug. Cries.");    
}

function fix_token($token)
{
    if(is_array($token))
    {
        $token['token_name'] = token_name($token[0]);
        $token['token_value'] = $token[1];
        $token['token_line'] = $token[2];
    }    
    else
    {
        $tmp                = array();
        $tmp['token_value'] = $token;
        $tmp['token_name']  = 'T_SINGLE_CHAR';
        $token              = $tmp;
    }
    return (object) $token;
}

function fix_all_tokens(&$tokens)
{
    for($i=0;$i<count($tokens);$i++)
    {
        $tokens[$i] = fix_token($tokens[$i]);
    }
    return $tokens;
}

function outputTokens($tokens, $buffer=false)
{
    if($buffer)
    {
        ob_start();
    }
    foreach($tokens as $token)
    {
        echo $token->token_value;
    }
    if($buffer)
    {
        return ob_get_clean();
    }
}

function pestle_token_get_all($string)
{
    $tokens = php_token_get_all($string);
    return fix_all_tokens($tokens);
}

function token_get_all($string)
{
    $tokens = php_token_get_all($string);
    return fix_all_tokens($tokens);
}

function run($argv)
{
    $file = $argv[1];
    $result = outputChangedFile($file, true);
    echo $result;
}

function outputChangedFile($file, $buffer)
{
    $tokens = pestle_token_get_all(file_get_contents($file));        
    $tokens = fix_all_tokens($tokens);

    $to_replace = array(
        'Mage_Adminhtml_Controller_Action'              => '\Magento\Backend\Controller\Adminhtml\Action',
        'Mage_Core_Block_Template'                      => '\Magento\Core\Block\Template',
        'Mage_Core_Helper_Abstract'                     => '\Magento\Core\Helper\AbstractHelper',
        'Mage_Core_Helper_Data'                         => '\Magento\Core\Helper\Data',
        'Mage_Core_Model_Abstract'                      => '\Magento\Core\Model\AbstractModel',
        'Mage_Core_Model_Session_Abstract'              => '\Magento\Core\Model\Session\AbstractSession',
        'Mage_Core_Model_Event_Invoker_InvokerDefault'  => '\Magento\Event\Invoker\InvokerDefault',
        'Mage_Core_Model_Event_Manager'                 => '\Magento\Event\Manager',
        'Varien_Object'                                 => '\Magento\Object', 
        'Varien_Event_Observer'                         => '\Magento\Event\Observer'
    );
    foreach($tokens as $token)
    { 
        if($token->token_name = 'T_STRING' && in_array($token->token_value, array_keys($to_replace)))
        {
            $token->token_value = $to_replace[$token->token_value];
        }
    }    
    
    
    return outputTokens($tokens, $buffer);
}


function extractClassInformationFromClassContentsDefinition(&$tokens)
{
    $information = [
        'class'=>[],
        'extends'=>[],        
        'implements'=>[],                
    ];
    $step = PARSE_STEP_START;
    foreach($tokens as $token)
    {
        $v = $token->token_value;
        if($step != PARSE_STEP_START && $v === '{')
        {
            $step = PARSE_STEP_DONE;
            break;
        }
        if($step === PARSE_STEP_START && $v === 'class')
        {
            $step = PARSE_STEP_CLASS;
            continue;
        }
        
        if($step != PARSE_STEP_START && $v === 'extends')
        {
            $step = PARSE_STEP_EXTENDS;
            continue;            
        }

        if($step != PARSE_STEP_START && $v === 'implements')
        {
            $step = PARSE_STEP_IMPLEMENTS;
            continue;            
        }
                        
        if($step === PARSE_STEP_CLASS)
        {
            $information['class'][] = $token;
        }

        if($step === PARSE_STEP_EXTENDS)
        {
            $information['extends'][] = $token;
        }        
        
        if($step === PARSE_STEP_IMPLEMENTS)
        {
            $information['implements'][] = $token;
        }        
    }
    $joinCallback = function($token){
        return $token->token_value;
    };
    
    $information['class'] = implode('',array_map($joinCallback, $information['class']));
    $information['extends'] = implode('',array_map($joinCallback, $information['extends']));
    $information['implements'] = implode('',array_map($joinCallback, $information['implements']));
    return $information;
}

define('PARSE_STEP_START',1);
define('PARSE_STEP_CLASS',2);
define('PARSE_STEP_EXTENDS',3);
define('PARSE_STEP_IMPLEMENTS',4);
define('PARSE_STEP_DONE',5);
define('PARSE_STEP_USE',5);

function extractClassInformationFromClassContentsNamespace($tokens)
{
    $array = extractClassInformationFromClassContentsStatementStartsWith($tokens, 'namespace');
    return array_shift($array);
}

function extractClassInformationFromClassContentsUse($tokens)
{
    return extractClassInformationFromClassContentsStatementStartsWith($tokens, 'use');
}

function extractClassInformationFromClassContentsStatementStartsWith($tokens, $startsWith='use')
{
    $step = PARSE_STEP_START;
    $information = [];
    $current = [];
    foreach($tokens as $token)
    {
        $v = $token->token_value;
        if($step === PARSE_STEP_START && $v === $startsWith)
        {
            $step = PARSE_STEP_USE;
            continue;
        }
        
        if($step === PARSE_STEP_USE && $v === ';')
        {
            $step = PARSE_STEP_START;
            $information[] = $current;            
            $current = [];
            continue;
        }
        
        if($step === PARSE_STEP_USE)
        {
            $current[] = $token;
        }        
    }

    $information = array_map(function($tokens){
        $joinCallback = function($token){
            return $token->token_value;
        };
        return implode('',array_map($joinCallback, $tokens));                
    }, $information);  
    return $information;
}

function extractFullClassNameFromClassInformation($information)
{
    return trim($information['namespace']) . '\\' . trim($information['class']);
}

function extractFullExtendsFromClassInformation($information)
{
    $extends = trim($information['extends']);
    if(!$extends)
    {
        return false;
    }

    if($extends[0] === '\\')
    {
        return trim($extends,'\\');
    }
    
    //test use statements
    foreach($information['use'] as $use)
    {
        $use = trim($use);
        $parts = explode('\\', $use);
        $last = array_pop($parts);
        //var_dump("$last === $extends");
        if($last === $extends)
        {
            return implode('\\',$parts) . '\\' . $extends;
        }
    }
    
    //test multi-part use
    foreach($information['use'] as $use)
    {
        $use = trim($use);
        $partsUse = explode('\\', $use);
        $lastUse = array_pop($partsUse);        
        $partsExtends = explode('\\', $extends);
        $firstExtends = array_shift($partsExtends);        
        if($lastUse === $firstExtends)
        {
            return implode('\\',$partsUse) . '\\' . $extends;
        }
    }

    //test namespaces
    $parts = explode('\\', trim($information['namespace']));
    $last  = array_pop($parts);    
    if(strpos($extends, $last) === 0)
    {
        return implode('\\',$parts) . '\\' . $extends;
    }

    return 'IMPLEMENT ME IN extractFullExtendsFromClassInformation';
}

function extractClassInformationFromClassContents($contents)
{
    $tokens = pestle_token_get_all($contents);
    $information = extractClassInformationFromClassContentsDefinition($tokens);
    $information['use'] = extractClassInformationFromClassContentsUse($tokens);
    
    $information['namespace'] = extractClassInformationFromClassContentsNamespace($tokens);
    $information['full-class'] = extractFullClassNameFromClassInformation($information);
    $information['full-extends'] = extractFullExtendsFromClassInformation($information);
    return $information;
}

function extractVariablesFromConstructor($function)
{
    $tokens = pestle_token_get_all('<' . '?php ' . $function);
    $tokens = array_filter($tokens, function($token){
        return $token->token_name === 'T_VARIABLE';
    });
    $variables = array_map(function($token){
        return $token->token_value;
    }, $tokens);
    
    return $variables;
}
