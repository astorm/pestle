<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Menu;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\input');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Menu\choseMenuFromTop');
pestle_import('Pulsestorm\Magento2\Cli\Generate\Menu\exported_pestle_cli');

function selectParentMenu($arguments, $index)
{
    if(array_key_exists($index, $arguments))
    {
        return $arguments[$index];
    }
        
    $parent     = '';
    $continue   = input('Is this a new top level menu? (Y/N)','N');
    if(strToLower($continue) === 'n')
    {
        $parent = choseMenuFromTop();
    }
    return $parent;
}

/**
* Generates configuration for Magento Adminhtml menu.xml files
*
* @command magento2:generate:menu
* @argument module_name Module Name? [Pulsestorm_HelloGenerate]
* @argument parent @callback selectParentMenu
* @argument id Menu Link ID [<$module_name$>::unique_identifier]
* @argument resource ACL Resource [<$id$>]
* @argument title Link Title [My Link Title]
* @argument action Three Segment Action [frontname/index/index]
* @argument sortOrder Sort Order? [10]
*/

function pestle_cli($argv)
{
    // output("Hi");
    return exported_pestle_cli($argv);
}
