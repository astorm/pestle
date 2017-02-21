<?php
namespace Pulsestorm\Magento2\Cli\Check_Acl;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\PhpDotNet\glob_recursive');
pestle_import('Pulsestorm\Cli\Token_Parse\getFunctionFromCode');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');
function traverseXmlFilesForNodeAndExtractUniqueValues($dir, $file, $node_name, $callback=false)
{
    $values = [];
    $files  = glob_recursive($dir . '/' . $file);
    foreach($files as $file)
    {
        $xml               = simplexml_load_file($file);
        $nodes             = $xml->xpath('//' . $node_name);
        $traverse_callback = function($node) use ($callback){
            if($callback)
            {
                return call_user_func($callback, $node);
            }
            return (string) $node;
        };        
        $values = array_merge($values, array_map($traverse_callback, $nodes));
    }
    $values = array_filter($values, function($value)
    {
        return $value !== 'Magento_Backend::admin';
    });
    return array_values(array_unique($values));
}

function getDefinedRuleIdsFromAclFiles($dir)
{
    return traverseXmlFilesForNodeAndExtractUniqueValues(
        $dir, 'acl.xml', 'resource', function($node){
            return (string) $node['id'];
        });
}

define('STATE_ACLRULE_START',           0);
define('STATE_ACLRULE_FOUND_ISALLOWED', 1);
function getAclRulesFromIsAllowedFunction($string)
{
    $tokens = pestle_token_get_all(
        '<' . '?' . 'php ' . "\n" . $string);
    $state = STATE_ACLRULE_START;            
    foreach($tokens as $token)
    {
        if($state === STATE_ACLRULE_START)
        {
            
            if($token->token_name === 'T_STRING' && $token->token_value === 'isAllowed')
            {
                $state = STATE_ACLRULE_FOUND_ISALLOWED;
            }
            continue;
        }

        if($state === STATE_ACLRULE_FOUND_ISALLOWED)
        {        
            if( $token->token_name === 'T_STRING' ||
                $token->token_name === 'T_CONSTANT_ENCAPSED_STRING')
            {
                $string = $token->token_value;
                return trim($string, "'\"");
            }
        }
    }
    return null;
}

function getUsedAclRuleIdsFromSystemXmlFiles($dir)
{
    return traverseXmlFilesForNodeAndExtractUniqueValues(
        $dir, 'system.xml', 'resource');
}

function getUsedAclRuleIdsFromMenuXmlFiles($dir)
{
    return traverseXmlFilesForNodeAndExtractUniqueValues(
        $dir, 'menu.xml', 'add', function($node){
            return (string) $node['id'];
        });

}

function getUsedAclRuleIdsFromControllerFiles($dir)
{
    $files = glob_recursive($dir . '/*/Controller/*.php');
    $code  = array_map(function($file){
        $function = getFunctionFromCode(file_get_contents($file), '_isAllowed');
        if(strpos($function,'_isAllowed'))
        {
            return getAclRulesFromIsAllowedFunction($function);
        }
        return false;
    }, $files);
    $code   = array_filter($code);
    return $code;
}

/**
* Scans modules for ACL rule ids, makes sure they'll all used/defined
*
* @command magento2:scan:acl_used
* @argument dir Which Directory?
*/
function pestle_cli($argv)
{
    $dir = $argv['dir'];
    $defined_rule_ids = getDefinedRuleIdsFromAclFiles($dir);
    
    $used_rule_ids = [];
    $used_rule_ids = array_merge($used_rule_ids, 
        getUsedAclRuleIdsFromSystemXmlFiles($dir));

    $used_rule_ids = array_merge($used_rule_ids, 
        getUsedAclRuleIdsFromMenuXmlFiles($dir));

    $used_rule_ids = array_merge($used_rule_ids, 
        getUsedAclRuleIdsFromControllerFiles($dir));

    $used_rule_ids = array_unique($used_rule_ids);
    
    sort($defined_rule_ids);
    sort($used_rule_ids);   
                         
    output("Checking that all used IDs are defined:");    
    foreach($used_rule_ids as $id)
    {
        $result = 'ERROR -- not defined';
        if(in_array($id, $defined_rule_ids))
        {
            $result = 'OK                  ';
        }
        output("  $result : $id");
    }

    output('');
        
    output("Checking that all defined IDs are used:");            
    foreach($defined_rule_ids as $id)
    {
        $result = 'ERROR -- not used';    
        if(in_array($id, $used_rule_ids))
        {
            $result = 'OK               ';
        }    
        output("  $result : $id");
    }
    
    output('');
    output('An unused ID may indicate an error, or may indicate a valid parent rule');
    output("Done");
}
