<?php
namespace Pulsestorm\Cli\Token_Parse;
use function token_get_all as php_token_get_all;

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
