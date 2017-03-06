<?php
namespace Pulsestorm\Magento2\Generate\SchemaUpgrade;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
pestle_import('Pulsestorm\Xml_Library\formatXmlString');

/**
 * One Line Description
 */
function getMitLicenseTextAsComment()
{
    return '/**
 * The MIT License (MIT)
 * Copyright (c) 2015 - '.date('Y').' Pulse Storm LLC, Alan Storm
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files (the 
 * "Software"), to deal in the Software without restriction, including 
 * without limitation the rights to use, copy, modify, merge, publish, 
 * distribute, sublicense, and/or sell copies of the Software, and to 
 * permit persons to whom the Software is furnished to do so, subject to 
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included 
 * in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS 
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY 
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT 
 * OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR 
 * THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */';
    
}

function prefacePhpStringWithMitLicense($string)
{
    $lines = preg_split('{[\r\n]}', $string);
    $found = false;
    $new = [];
    foreach($lines as $line)
    {
        $new[] = $line;
        if($found) {continue;}
        if(preg_match('{^namespace.+?;}', $line))
        {
            $new[] = getMitLicenseTextAsComment();
            $found = true;
        }
    }
    
    return implode("\n", $new);
}

function getUpgradeSchemaPathFromModuleInfo($moduleInfo)
{
    return $moduleInfo->folder . '/Setup/UpgradeSchema.php';
}

function getUpgradeDataPathFromModuleInfo($moduleInfo)
{
    return $moduleInfo->folder . '/Setup/UpgradeData.php';
}

function classFileIsOurDataUpgrade($path)
{
    $contents = file_get_contents($path);
    return strpos($contents, 'Setup\Scripts') !== false &&
        strpos($contents, 'this->scriptHelper->run') !== false;
}

function classFileIsOurSchemaUpgrade($path)
{
    $contents = file_get_contents($path);
    return strpos($contents, 'Setup\Scripts') !== false &&
        strpos($contents, 'this->scriptHelper->run') !== false;
}

function moduleHasOrNeedsOurUpgradeData($moduleInfo)
{
    $path = getUpgradeDataPathFromModuleInfo($moduleInfo);
    if(!file_exists($path))
    {        
        return true;
    }    
    
    if(classFileIsOurDataUpgrade($path))
    {
        return true;
    }
    
    return;
}

function moduleHasOrNeedsOurUpgradeSchema($moduleInfo)
{
    $path = getUpgradeSchemaPathFromModuleInfo($moduleInfo);
    if(!file_exists($path))
    {
        return true;
    }    
    
    if(classFileIsOurSchemaUpgrade($path))
    {
        return true;
    }
    
    return;
}

function checkForUpgradeData($moduleInfo)
{
    if(!moduleHasOrNeedsOurUpgradeData($moduleInfo))
    {
        exitWithErrorMessage("Bailing: Upgrade Data already exists and it not pestle's");
    }
}

function checkForUpgradeSchema($moduleInfo)
{
    if(!moduleHasOrNeedsOurUpgradeSchema($moduleInfo))
    {
        exitWithErrorMessage("Bailing: Upgrade Schema already exists and is not pestle's");
    }
}

function checkForSchemaInstall($moduleInfo)
{
    if(!file_exists($moduleInfo->folder . '/Setup/InstallSchema.php'))
    {
        exitWithErrorMessage("Bailing: Module needs an InstallSchema first.");
    }
}

function getSetupScriptPathFromModuleInfo($moduleInfo, $type='schema')
{
    return $moduleInfo->folder . '/upgrade_scripts/' . $type;
}

function checkForExistingUpgradeScript($moduleInfo, $upgradeVersion)
{
    $types = ['schema', 'data'];
    foreach($types as $type)
    {
        $baseScriptPath = getSetupScriptPathFromModuleInfo($moduleInfo, $type);
        if(file_exists($baseScriptPath . '/' . $upgradeVersion . '.php'))
        {
            exitWithErrorMessage("A $upgradeVersion.php $type script already exists");
        }
    }  
}

function getModuleXmlPathFromModuleInfo($moduleInfo)
{
    return $moduleInfo->folder . '/etc/module.xml';
}

function checkModuleXmlForVersion($moduleInfo, $upgradeVersion)
{
    $xml = simplexml_load_file(getModuleXmlPathFromModuleInfo($moduleInfo));
    $oldVersion = $xml->module['setup_version'];
    if(version_compare($oldVersion, $upgradeVersion) !== -1)
    {
        exitWithErrorMessage("New module version ({$upgradeVersion}) " .
            "is older or equal to old module version ({$oldVersion}).");
    }
    return $xml;
}

function checkUpgradeVersionValidity($moduleInfo, $upgradeVersion)
{
    $parts = explode('.',$upgradeVersion);
    $parts = array_filter($parts, 'is_numeric');    
    if(count($parts) !== 3)
    {
        exitWithErrorMessage("Version does not appear to be in numeric X.X.X format.");
    }        
    checkForExistingUpgradeScript($moduleInfo, $upgradeVersion);      
    checkModuleXmlForVersion($moduleInfo, $upgradeVersion);      
}

function getSchemaClassNameFromModuleInfo($moduleInfo)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\Setup\UpgradeSchema';
}

function getDataClassNameFromModuleInfo($moduleInfo)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\Setup\UpgradeData';
}

function getDataUseStatements()
{
    return 'use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;    
';    
}

function getDataClassBody($moduleInfo)
{
    $setupScriptClassName = getSetupScriptClassNameFromModuleInfo($moduleInfo);
    return '
    protected $scriptHelper;
    public function __construct(
        '.$setupScriptClassName.' $scriptHelper
    )
    {
        $this->scriptHelper = $scriptHelper;
    }
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup, 
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();        
        $this->scriptHelper->run($setup, $context, \'data\');
        $setup->endSetup();
    }        
';    
}

function getSchemaUseStatements()
{
    return 'use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;';
}

function getSetupScriptClassNameFromModuleInfo($moduleInfo)
{
    return '\\' . $moduleInfo->vendor . '\\' . $moduleInfo->short_name . '\Setup\Scripts';
}

function getSchemaClassBody($moduleInfo)
{
    $setupScriptClassName = getSetupScriptClassNameFromModuleInfo($moduleInfo);
    return '
    protected $scriptHelper;
    public function __construct(
        '.$setupScriptClassName.' $scriptHelper
    )
    {
        $this->scriptHelper = $scriptHelper;
    }
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup, 
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();        
        $this->scriptHelper->run($setup, $context, \'schema\');
        $setup->endSetup();
    }      
';    
}

function generateUpgradeSchemaClass($moduleInfo)
{
    $path = getUpgradeSchemaPathFromModuleInfo($moduleInfo);
    $className = getSchemaClassNameFromModuleInfo($moduleInfo);

    $contents = createClassTemplateWithUse($className, false, 'UpgradeSchemaInterface');
    $contents = str_replace('<$use$>', getSchemaUseStatements(), $contents);
    $contents = str_replace('<$body$>', getSchemaClassBody($moduleInfo), $contents);
    $contents = prefacePhpStringWithMitLicense($contents);
        
    output("Creating $className");
    createClassFile($className, $contents);        
}

function generateUpgradeDataClass($moduleInfo)
{
    $path = getUpgradeDataPathFromModuleInfo($moduleInfo);
    $className = getDataClassNameFromModuleInfo($moduleInfo);

    $contents = createClassTemplateWithUse($className, false, 'UpgradeDataInterface');
    $contents = str_replace('<$use$>', getDataUseStatements(), $contents);
    $contents = str_replace('<$body$>', getDataClassBody($moduleInfo), $contents);
    $contents = prefacePhpStringWithMitLicense($contents);    
    output("Creating $className");    
    createClassFile($className, $contents); 
}

function getScriptsClassBody($moduleInfo)
{
    return '
    protected $dirReader;
    protected $currentModuleVersionFromDisk=false;
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $dirReader
    )
    {
        $this->dirReader = $dirReader;
    }

    public function run($setup, $context, $type)
    {
        foreach($this->getSetupScripts($type) as $version=>$script)
        {
            $this->runUpgradeScriptIfNeeded($version, $script, $context, $setup);
        }            
    }
    
    protected function runUpgradeScriptIfNeeded($version, $script, $context, $setup)
    {        
        if(!version_compare($context->getVersion(), $version, \'<\'))
        {
            return;
        }

        if(version_compare($this->getCurrentModuleVersionFromDisk(), $version) === -1)
        {
            return;
        }
        include $script;                
    }  
        
    protected function getSetupScripts($type)
    {
        $files = glob($this->getBaseModuleDirectory() . \'/upgrade_scripts/\' .
            $type . \'/*.*.*.php\');

        usort($files, function($a, $b){
            $a = pathinfo($a)[\'filename\'];
            $b = pathinfo($b)[\'filename\'];
            return version_compare($a, $b);
        });
                    
        $withVersionKeys = [];
        foreach($files as $file)
        {
            $withVersionKeys[pathinfo($file)[\'filename\']] = $file;
        }
        
        return $withVersionKeys;
    }
    
    protected function getModuleNameFromStaticClassName()
    {
        $parts = explode("\\\\", static::class);
        return $parts[0] . \'_\' . $parts[1];
    }
    
    protected function getBaseModuleDirectory()
    {
        return $this->dirReader->getModuleDir(\'\',$this->getModuleNameFromStaticClassName());        
    }

    /**
     * We don\'t trust any of the standard class mechanisms to stay stable version
     * to version, and that seems important in an upgrade class that shouldn\'t
     * ever change.
     */    
    protected function getCurrentModuleVersionFromDisk()
    {
        if(!$this->currentModuleVersionFromDisk)
        {
            $xml = $this->loadXmlFile($this->getBaseModuleDirectory() . \'/etc/module.xml\');
            $this->currentModuleVersionFromDisk = $xml->module[\'setup_version\'];
        }
        return $this->currentModuleVersionFromDisk;
    }
    
    protected function loadXmlFile($path)
    {
        return simplexml_load_file($path);
    }      
';    
}

function generateScriptHelperClass($moduleInfo)
{
    $setupScriptClassName = getSetupScriptClassNameFromModuleInfo($moduleInfo);    
    $contents = createClassTemplateWithUse($setupScriptClassName, false);
    $contents = str_replace('<$use$>', '', $contents);
    $contents = str_replace('<$body$>', getScriptsClassBody($moduleInfo), $contents);
    $contents = prefacePhpStringWithMitLicense($contents);    
    createClassFile($setupScriptClassName, $contents);         
}

function getSchemaUpgradeScriptBody()
{
    return '<?php ' . "\n" .
'/**
 * This script `included` via class method, inherits this variable from that context
 * @var $setup \Magento\Framework\Setup\SchemaSetupInterface
 */
 $setup;

/**
 * This script `included` via class method, inherits this variable from that context
 * @var $setup \Magento\Framework\Setup\ModuleContextInterface
 */
 $context;
 
//create a table
//         $table = $setup->getConnection()
//             ->newTable($setup->getTable(Gallery::GALLERY_VALUE_TO_ENTITY_TABLE))
//             ->addColumn(
//                 \'value_id\',
//                 \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
//                 null,
//                 [\'unsigned\' => true, \'nullable\' => false],
//                 \'Value media Entry ID\'
//             )
//         $setup->getConnection()->createTable($table);

//update a table
// $installer = $setup;
// $tableAdmins = $setup->getTable(\'admin_user\');
// 
// $setup->getConnection()->addColumn(
//     $tableAdmins,
//     \'failures_num\',
//     [
//         \'type\' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
//         \'nullable\' => true,
//         \'default\' => 0,
//         \'comment\' => \'Failure Number\'
//     ]
// );
 ';
}

function getDataUpgradeScriptBody()
{
    return '<?php ' . "\n" .
'/**
 * This script `included` via class method, inherits this variable from that context
 * @var $setup \Magento\Framework\Setup\ModuleDataSetupInterface
 */
 $setup;

/**
 * This script `included` via class method, inherits this variable from that context
 * @var $setup \Magento\Framework\Setup\ModuleContextInterface
 */
 $context;

//insert data  
//             $connection = $setup->getConnection();      
//             $select = $connection->select()
//                 ->from(
//                     $this->relationProcessor->getTable(\'catalog_product_link\'),
//                     [\'product_id\', \'linked_product_id\']
//                 )
//                 ->where(\'link_type_id = ?\', Link::LINK_TYPE_GROUPED);
// 
//             $connection->query(
//                 $connection->insertFromSelect(
//                     $select, $this->relationProcessor->getMainTable(),
//                     [\'parent_id\', \'child_id\'],
//                     AdapterInterface::INSERT_IGNORE
//                 )
//             ); 

//update data
// $connection = $setup->getConnection(\'sales\');
// $select = $connection->select()
//     ->from($setup->getTable(\'sales_order_payment\'), \'entity_id\')
//     ->columns([\'additional_information\'])
//     ->where(\'additional_information LIKE ?\', \'%token_metadata%\');
//     ...
//     $connection->update(
//         $setup->getTable(\'sales_order_payment\'),
//         [\'additional_information\' => serialize($additionalInfo)],
//         [\'entity_id = ?\' => $item[\'entity_id\']]
//     );
// }      
 ';
}

function incrementModuleXml($moduleInfo, $upgradeVersion)
{
    output("Incrementing module.xml to {$upgradeVersion}");
    $path = getModuleXmlPathFromModuleInfo($moduleInfo);
    $xml = simplexml_load_file($path);
    $xml->module['setup_version'] = $upgradeVersion;    
    writeStringToFile($path, formatXmlString($xml->asXml()));
}

function generateUpgradeScripts($moduleInfo, $upgradeVersion)
{    
    $setupPath = getSetupScriptPathFromModuleInfo($moduleInfo, 'schema');
    output("Creating {$upgradeVersion} Upgrade Scripts in {$setupPath}");    
    writeStringToFile($setupPath . '/' . $upgradeVersion . '.php', 
        getSchemaUpgradeScriptBody());    
            
    $setupPath = getSetupScriptPathFromModuleInfo($moduleInfo, 'data');        
    output("Creating {$upgradeVersion} Upgrade Scripts in {$setupPath}");    
    writeStringToFile($setupPath . '/' . $upgradeVersion . '.php', 
        getDataUpgradeScriptBody());
}        

/**
* BETA: Generates a migration-based UpgradeSchema and UpgradeData classes
*
* @command magento2:generate:schema-upgrade
* @argument module_name Module Name? [Pulsestorm_Helloworld]
* @argument upgrade_version New Module Version? [0.0.2]
*/
function pestle_cli($argv, $options)
{
    $moduleInfo = getModuleInformation($argv['module_name']);
    checkForSchemaInstall($moduleInfo);
    checkForUpgradeSchema($moduleInfo);
    checkForUpgradeData($moduleInfo);
    checkUpgradeVersionValidity($moduleInfo, $argv['upgrade_version']);
    
    generateUpgradeSchemaClass($moduleInfo);
    generateUpgradeDataClass($moduleInfo);
    generateScriptHelperClass($moduleInfo);
    generateUpgradeScripts($moduleInfo, $argv['upgrade_version']);
    incrementModuleXml($moduleInfo, $argv['upgrade_version']);
}

function exportedSchemaUpgrade($argv, $options)
{
    return pestle_cli($argv, $options);
}