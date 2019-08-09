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
            'unit_test', 'ModelTest', [], 'A testing table'
        );
        $fixture = '        $table = $installer->getConnection()->newTable(
            $installer->getTable(\'unit_test\')
        )->addColumn(
            \'modeltest_id\',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [ \'identity\' => true, \'nullable\' => false, \'primary\' => true, \'unsigned\' => true ],
            \'Entity ID\'
        )->addColumn(
            \'title\',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [ \'nullable\' => false ],
            \'Demo Title\'
        )->addColumn(
            \'creation_time\',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [ \'nullable\' => false, \'default\' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT ],
            \'Creation Time\'
        )->addColumn(
            \'update_time\',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [ \'nullable\' => false, \'default\' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE ],
            \'Modification Time\'
        )->addColumn(
            \'is_active\',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            [ \'nullable\' => false, \'default\' => \'1\' ],
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