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

function generateConfigXml() {
    return '<?xml version="1.0" encoding="UTF-8"?>
<config>
    <modules>
        <Pulsestorm_SimpleRest>
            <version>0.1.0</version>
        </Pulsestorm_SimpleRest>
    </modules>
</config>';
}

function generateModuleDeclarationFile() {
    return '<?xml version="1.0"?>
<config>
    <modules>
        <Pulsestorm_SimpleRest>
            <active>true</active>
            <codePool>local</codePool>
            <depends />
        </Pulsestorm_SimpleRest>
    </modules>
</config>';
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

    writeStringToFile($pathEtc, generateModuleDeclarationFile());
    writeStringToFile($pathModule, generateConfigXml());

    output('');
    output('Generated Base Module Files:');
    output('    ' . $pathEtc);
    output('    ' . $pathModule);
}
