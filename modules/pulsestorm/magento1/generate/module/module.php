<?php
namespace Pulsestorm\Magento1\Generate\Module;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\isAboveRoot');
pestle_import('Pulsestorm\Pestle\Library\getBaseDir');
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');


function getBaseMagentoDir($path=false)
{
    return getBaseDir($path, 'app/Mage.php');
}

function generateConfigXml($fullModuleName) {
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
"<config>
    <modules>
        <$fullModuleName>
            <version>0.1.0</version>
        </$fullModuleName>
    </modules>
</config>";
}

function generateModuleDeclarationFile($fullModuleName, $pool) {
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
"<config>
    <modules>
        <$fullModuleName>
            <active>true</active>
            <codePool>$pool</codePool>
            <depends />
        </$fullModuleName>
    </modules>
</config>";
}

/**
* One Line Description
*
* @command magento1:generate:module
* @argument code_pool Code Pool [community]
* @argument full_module_name Full Module Name [Pulsestorm_Helloworld]
*/
function pestle_cli($argv)
{
    list($package, $module) = explode('_', $argv['full_module_name']);

    $pathEtc    = getBaseMagentoDir() . '/app/etc/modules/' . $argv['full_module_name'] . '.xml';
    $pathModule = getBaseMagentoDir() . '/app/code/' . $argv['code_pool'] . '/' . $package .
        '/' . $module . '/etc/config.xml';

    writeStringToFile($pathEtc, generateModuleDeclarationFile($argv['full_module_name'], $argv['code_pool']));
    writeStringToFile($pathModule, generateConfigXml($argv['full_module_name']));

    output('');
    output('Generated Base Module Files:');
    output('    ' . $pathEtc);
    output('    ' . $pathModule);
}
