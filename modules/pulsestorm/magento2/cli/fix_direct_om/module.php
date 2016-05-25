<?php
namespace Pulsestorm\Magento2\Cli\Fix_Direct_Om;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');
pestle_import('Pulsestorm\PhpDotNet\glob_recursive');

function getFiles($folder, $extension_string)
{
    if(file_exists($folder) && !is_dir($folder))
    {    
        return [$folder];
    }
    $extensions = array_filter(explode(',',$extension_string));
    
    $files = [];
    foreach($extensions as $extension)
    {
        $files = array_merge($files, glob_recursive($folder . '/*.' . $extension));
    }
    return $files;
}

/**
* Test Command
* argument foobar @callback exampleOfACallback
* @command testbed
* @argument folder Folder to scan
* @argument extensions File extensions? [php, phtml]
*/
function pestle_cli($arguments, $options)
{
    extract($arguments);
    $files = getFiles($folder, $extensions);   
    foreach($files as $file)
    {
        output('.');
        $first_find = true;
        $tokens_all = pestle_token_get_all(file_get_contents($file));
        $tokens = array_filter($tokens_all, function($token){
            return $token->token_name !== 'T_WHITESPACE';
        });
        $tokens = array_values($tokens); //reindexes
        $c=0;
        $previous_token = new \stdClass;
        $previous_token->token_value = '';
        foreach($tokens as $token)
        {
            if($c > 0)
            {
                $previous_token = $tokens[$c-1];
            }
            if($token->token_name === 'T_OBJECT_OPERATOR' && $previous_token->token_value == '_objectManager')
            {
                if($first_find)
                {
                    output("In $file");            
                    $first_find = false;
                }
                output("    Found {$previous_token->token_value} on line {$previous_token->token_line}");
                reportOnObjectManagerCall($tokens, $c);
            }
        
            $c++;
        }
    }
    output("Hello World");
}
