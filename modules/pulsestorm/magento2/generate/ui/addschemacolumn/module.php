<?php
namespace Pulsestorm\Magento2\Generate\Ui\Addschemacolumn;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');

pestle_import('Pulsestorm\Magento2\Cli\Generate\Menu\inputFromArray');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');

function getColumnTypes()
{
    return [
        'TYPE_BIGINT'  =>'bigint',
        'TYPE_BOOLEAN' =>'boolean',
        'TYPE_DATE'    =>'date',
        'TYPE_DATETIME'=>'datetime',
        'TYPE_DECIMAL' =>'decimal',
        'TYPE_FLOAT'   =>'float',
        'TYPE_INTEGER' =>'integer',
        'TYPE_SMALLINT'=>'smallint',
        
        'PS_TYPE_VARCHAR'   =>'varchar',
        'PS_TYPE_VARBINARY' =>'varbinary',     
                
        'PS_TYPE_TEXT' =>'text',
        'PS_TYPE_BLOB' =>'blob',        
        
        'PS_TYPE_MEDIUM_TEXT' =>'mediumtext',
        'PS_TYPE_MEDIUM_BLOB' =>'mediumblob',        

        'PS_TYPE_LONG_TEXT' =>'longtext',
        'PS_TYPE_LONG_BLOB' =>'longblob',   
                
        'PS_TYPE_TEXT' =>'text',
        'PS_TYPE_BLOB' =>'blob',                        
        // 'TYPE_TIMESTAMP'=>'timestamp',
    ];
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

function validateColumnType($type)
{
    $types = getColumnTypes();
    if(!in_array($type, $types))
    {
        exitWithErrorMessage("ERROR: Unknown column type {$type}");
    }
}

function getClassConstantFromType($type)
{
    $types = getColumnTypes();
    $types = array_flip($types);
    $constant = $types[$type];

    if(strpos($constant, 'PS_') !== false && strpos($constant, '_BLOB') !== false)
    {
        $constant = 'TYPE_BLOB';
    }

    if(strpos($constant, 'PS_') !== false)
    {
        $constant = 'TYPE_TEXT';
    }
            
    return '\Magento\Framework\DB\Ddl\Table::' . $constant;
}

function getLengthFromType($type)
{
    $legend = [
        'bigint'=>'null',
        'boolean'=>'null',
        'date'=>'null',
        'datetime'=>'null',
        'decimal'=>'[12,4]',
        'float'=>'null',
        'integer'=>'null',
        'smallint'=>'null',        
        'varchar'=>'255',
        'varbinary'=>'255',                     
        'text'=>'"64K"',
        'blob'=>'"64K"',                
        'mediumtext'=>'"2M"',
        'mediumblob'=>'"2M"',        
        'longtext'=>'"4G"',
        'longblob'=>'"4G"',   
    ]; 
    return $legend[$type];   
}

function getPropertyArrayFromType($type)
{
    $legend = [
        'bigint'        =>"['nullable' => false, 'default' => 0]",
        'boolean'       =>"['nullable' => false, 'default' => 0]",        
        'date'          =>"[]",
        'datetime'      =>"[]",
        'decimal'       =>"['nullable' => false, 'default' => '0.0000']",
        'float'         =>"['nullable' => false, 'default' => '0.0000']",        
        'integer'       =>"['nullable' => false, 'default' => 0]",
        'smallint'      =>"['nullable' => false, 'default' => 0]",        
        'varchar'       =>"['nullable' => false, 'default' => '']",
        'varbinary'     =>"['nullable' => true, 'default' => null]",                     
        'text'          =>"['nullable' => true, 'default' => null]",
        'blob'          =>"['nullable' => true, 'default' => null]",                
        'mediumtext'    =>"['nullable' => true, 'default' => null]",
        'mediumblob'    =>"['nullable' => true, 'default' => null]",        
        'longtext'      =>"['nullable' => true, 'default' => null]",
        'longblob'      =>"['nullable' => true, 'default' => null]",   
    ]; 
    
    return $legend[$type];
}

function getColumnProps($type)
{
    
    return [
        'typeConstant'   => getClassConstantFromType($type),    //'\Magento\Framework\DB\Ddl\Table::TYPE_TEXT',
        'length'         => getLengthFromType($type),           //'255',
        'propertyArray'  => getPropertyArrayFromType($type),    //"['nullable' => false]",
        'comment'        => '',
    ];
}

function generateAddColumn($name, $type)
{
    $props = getColumnProps($type);
    $props['comment'] = '"' . $name . ' field"';
    extract($props);
    $string = "
->addColumn('$name',
            $typeConstant,
            $length,
            $propertyArray,
            $comment
        )";
        
    return $string;        
}

function getNextNonWhitespaceToken(&$tokens, $i, $count, $flag=false)
{
    $i++;
    for($i=$i;$i<$count;$i++)
    {
        $token = $tokens[$i];
        //skip whitespace
        if($token->token_name === 'T_WHITESPACE') { continue; }
        
        //if we're at the end, return false
        if(!isset($tokens[$i+1]))
        {        
            return false;
        }         
        return $token;        
    }
    return false;
}

//scan until we find a newTable T_STRING followed by a ( T_SINGLE_CHAR
function scanFoundNewTable($state, $token, $nextToken)
{
    return $state === 'start' && 
            ($token->token_name     === 'T_STRING' && $token->token_value === 'newTable') && 
            ($nextToken->token_name === 'T_SINGLE_CHAR' && $nextToken->token_value === '(');
}

function scanIsOurTableAndReturnIndex($tokens, $i, $count, $tableName)
{
    $result           = fetchTokensUntilAddColumn($tokens, $i, $count);

    //if we reached the end of the file, bail
    if(!$result){ return 'end-of-file'; } 

    $newTableTokens   = $result['newTableTokens'];
    $i                = $result['i'];


    $string = implode('', array_map(function($item){
        return $item->token_value;
    }, $newTableTokens));

    //check if tokens contain our table            
    if(strpos($string, $tableName) === false)
    {
        //if not, start over
        return 'start-over';
    }
    //if so, set mode and continue
    $state = 'scanningToStatementEnd';
    return $i;
}

function fetchTokensUntilAddColumn(&$tokens, $i, $count)
{
    $newTableTokens = [];
    for($i=$i;$i<$count;$i++)
    {   
        $token = $tokens[$i];
        $newTableTokens[] = $token;
        if(($token->token_name === 'T_STRING' && $token->token_value === 'addColumn'))
        {
            return [
                'newTableTokens'=>$newTableTokens,
                'i'=>$i];
        }
    }
    
    return false;
}

function getTokensWithInsertedCodeFromSourceFile($columnCode, $file, $table)
{    
    $tokens                 = pestle_token_get_all(file_get_contents($file));
    $count                  = count($tokens);
    $state                  = 'start'; //scanningNewtable, scanningToStatementEnd
    $newTableTokens         = [];
    $tokensWithNewAddColumn = [];
    for($i=0;$i<$count;$i++)
    {        
        //get current token and next token, breaking
        //out if there's no new token  
        $token      = $tokens[$i];
        $nextToken  = getNextNonWhitespaceToken($tokens, $i, $count);
        
        //skip whitespace
        if($token->token_name === 'T_WHITESPACE') { continue; }
        
        //scan until we find a newTable T_STRING followed by a ( T_SINGLE_CHAR
        if(scanFoundNewTable($state, $token, $nextToken))
        {
            $state = 'scanningNewTable';
            continue;
        }
        
        //pull out everything until we hit an addColumn
        //does the pulled out string contain our table name?
        if($state === 'scanningNewTable')
        {
            $index = scanIsOurTableAndReturnIndex($tokens, $i, $count, $table);
            if($index === 'end-of-file') { 
                break; 
            }
            if($index === 'start-over') {
                $state = 'start';
                continue;            
            }
            $i     = $index;
            $state = 'scanningToStatementEnd';
            continue;
        }    
        
        //if so, scan until ending ;
        if($state === 'scanningToStatementEnd')
        {
            if($token->token_name='T_SINGLE_CHAR' && $token->token_value === ';')
            {
                //then insert our code block into tokens and break
                $beforeTokens = array_slice($tokens, 0, $i);
                $afterTokens  = array_slice($tokens, $i);
                
                $token = new \stdClass;
                $token->token_name  = 'T_FAKE_INSERT_HACK';
                $token->token_value = trim($columnCode);
                $middleTokens = [$token];
                
                $tokensWithNewAddColumn = array_merge(
                    $beforeTokens, $middleTokens, $afterTokens
                );
                //return our new tokens
                return $tokensWithNewAddColumn;
            }
        }                           
    }    
    
    //return empty array, indicating error
    return $tokensWithNewAddColumn;   
}
/**
* Genreated a Magento 2 addColumn DDL definition and inserts into file
*
* Command scans creates column definition code and, if provided
* attempts to insert it into provided php_file.  Inserting means
* looking for this pattern.
*   newTable($installer->getTable('table_name'))->addColumn
* and if found, scanning to the ; and inserting the addColumn         
*
* @command magento2:generate:ui:add-schema-column
* @argument php_file PHP file with newTable call? [skip]
* @argument table Database Table? (packagename_modulename_modelnames)
* @argument column Columns Name? (new_column)
* @argument column_type @callback selectColumnType
*/
function pestle_cli($argv)
{
    validateColumnType($argv['column_type']);
    $columnCode = generateAddColumn($argv['column'], $argv['column_type']);
    if($argv['php_file'] === 'skip')
    {
        output($columnCode);
    }
    
    $tokens     = getTokensWithInsertedCodeFromSourceFile(
        $columnCode, $argv['php_file'], $argv['table']); 

    if(!$tokens)
    {
        exitWithErrorMessage(
            "We couldn't find a newTable call with {$argv['table']}" . "\n" . 
            "Exiting with an error, but here's the code." . "\n" . 
            $columnCode
        );
    }        
    
    output("Adding addColumn Call to file");
    $string = implode('', array_map(function($item){
        return $item->token_value;
    }, $tokens));

    writeStringToFile($argv['php_file'], $string);   
}
