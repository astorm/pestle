<?php
namespace Pulsestorm\Magento2\Generate\Schemaaddcolumn;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Magento2\Generate\Ui\Addschemacolumn\exported_pestle_cli');
pestle_import('Pulsestorm\Magento2\Generate\Ui\Addschemacolumn\getColumnTypes');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

/**
* Genreated a Magento 2 addColumn DDL definition and inserts into file
*
* Command scans creates column definition code and, if provided
* attempts to insert it into provided php_file.  Inserting means
* looking for this pattern.
*   newTable($installer->getTable('table_name'))->addColumn
* and if found, scanning to the ; and inserting the addColumn
*
* @command magento2:generate:schema-add-column
* @argument php_file PHP file with newTable call? [skip]
* @argument table Database Table? (packagename_modulename_modelnames)
* @argument column Columns Name? (new_column)
* @argument column_type @callback selectColumnType
*/
function pestle_cli($argv, $options)
{
    return exported_pestle_cli($argv, $options);
}

function selectColumnType($arguments, $index, $fullArguments)
{
    if(isset($arguments[$index]))
    {
        return $arguments[$index];
    }
    $types = array_values(getColumnTypes());
    $value = inputFromArray("Column Type?", $types, 1);
    return $value;
}
