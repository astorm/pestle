<?php
namespace Pulsestorm\Cli\Format_Php;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');

function tokenIsSemiColonAndNextTokenIsNotTCloseTag($tokens, $key)
{
    $current_token = $tokens[$key];
    $next_token    = false;
    if(array_key_exists($key+1, $tokens))
    {
        $next_token = $tokens[$key+1];
    }
    if(!$next_token)
    {
        return false;
    }
    
    if($current_token->token_value === ';' && $next_token->token_name !== 'T_CLOSE_TAG')
    {
        return true;
    }

    return false;
}

/**
* ALPHA: Experiments with a PHP formatter.
*
* @command format_php
* @argument file Which file?
*/
function pestle_cli($argv)
{
    define('START', 0);
    define('PARSE_IF', 1);
    define('INSIDE_IF_BLOCK', 2);
    
    $file = $argv['file'];
    $tokens = pestle_token_get_all(file_get_contents($file));    
    
    //remove whitespace tokens
    $tokens = array_filter($tokens, function($token){
        return $token->token_name !== 'T_WHITESPACE';
    });
    $tokens = array_values($tokens);

    $state        = 0;
    $indent_level = 0;
    foreach($tokens as $key=>$token)
    {
        $before = '';
        $after  = '';
        
        //state switching
        if($token->token_name == 'T_IF')
        {
            $state = PARSE_IF;
        }
        
        if($state == PARSE_IF && $token->token_value === ':')
        {
            $indent_level++;
            $state = INSIDE_IF_BLOCK;
        }
        
        if($state == INSIDE_IF_BLOCK && $token->token_name === 'T_ENDIF')
        {
            $state = START;
            $indent_level--;
        }
                        
        //manipuate extra output tokens
        if($token->token_value === '{')
        {
            $indent_level++;
            $after = "\n" . str_repeat("    ", $indent_level);
        }
        
        if($token->token_value === '}')
        {
            $indent_level--;        
            $after = "\n" . str_repeat("    ", $indent_level);
        }        
        
        if($token->token_name === 'T_CLOSE_TAG')
        {
            $after = "\n" . str_repeat("    ", $indent_level);       
        }
        
        if(tokenIsSemiColonAndNextTokenIsNotTCloseTag($tokens, $key))
        {
            $after = "\n" . str_repeat("    ", $indent_level);       
        }
        
        if($token->token_name === 'T_INLINE_HTML' && !trim($token->token_value))
        {
            continue;
        }
        //do output
        echo $before;
        echo $token->token_value;
        echo $after;        
    }
}
