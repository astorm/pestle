<?php
namespace Pulsestorm\Pestle\Tests;
require_once 'PestleBaseTest.php';
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Cli\Code_Generation\generateInstallSchemaTable');
class GenerateInstallSchemaTest extends PestleBaseTest
{
    public function testSetup()
    {
        $result = generateInstallSchemaTable();
        $this->assertTrue(is_string($result));
    }
    
    public function testGenerateBlank()
    {
        $result = generateInstallSchemaTable(
            'unit_test', [], 'A testing table'
        );
        $fixture = '$table = $installer->getConnection()->newTable(
            $installer->getTable(\'unit_test\')
    )->addColumn(
            \'unit_test_id\',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            array (
  \'identity\' => true,\'nullable\' => false,\'primary\' => true,\'unsigned\' => true,
),
            \'Entity ID\'
        )->addColumn(
            \'title\',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            array (
  \'nullable\' => false,
),
            \'Demo Title\'
        )->addColumn(
            \'creation_time\',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            array (
),
            \'Creation Time\'
        )->addColumn(
            \'update_time\',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            array (
),
            \'Modification Time\'
        )->addColumn(
            \'is_active\',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            array (
  \'nullable\' => false,\'default\' => \'1\',
),
            \'Is Active\'
        )->setComment(
             \'A testing table\'
         );
$installer->getConnection()->createTable($table);';
        $this->assertEquals(
            $result, 
            $fixture
        );
    }    
}