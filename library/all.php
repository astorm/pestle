<?php
namespace Pulsestorm\Cli\Build_Command_List{
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ReflectionFunction;
use function Pulsestorm\Pestle\Importer\pestle_import;






function getListOfFilesInModuleFolder()
{
    $path = \Pulsestorm\Pestle\Runner\getBaseProjectDir() . '/modules/';
    $objects = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path), 
        RecursiveIteratorIterator::SELF_FIRST
    );
    return $objects;
}

function includeAllModuleFiles()
{
    $objects = getListOfFilesInModuleFolder();
    // $path = realpath('modules/');
    // $objects = new RecursiveIteratorIterator(
    //     new RecursiveDirectoryIterator($path), 
    //     RecursiveIteratorIterator::SELF_FIRST
    // );
    foreach($objects as $name => $object){
        $info = pathinfo($name);        
        if($info['basename'] == 'module.php')
        {
            require_once $name;
        }
    }

}

function buildCommandList()
{
    includeAllModuleFiles();
    
    $functions = get_defined_functions();
    $lookup    = [];
    foreach($functions['user'] as $function)
    {
        if(strpos($function, 'pestle_cli') === false)
        {
            continue;
        }
        $r = new ReflectionFunction($function);
        // $doc_comment        = getDocCommentAsString($function);
        $parsed_doc_command = \Pulsestorm\Pestle\Library\parseDocBlockIntoParts($r->getDocComment());
        
        $command = array_key_exists('command', $parsed_doc_command) 
            ? $parsed_doc_command['command'] : ['pestle-none-set'];

        $command = array_shift($command);            

        $lookup[$command] = $r->getFilename();
    }
    cacheCommandList($lookup);
    return $lookup;
}

function cacheCommandList($lookup)
{
    $cache_dir = \Pulsestorm\Pestle\Importer\getCacheDir();
    file_put_contents($cache_dir . '/command-list.ser', serialize($lookup));
}

/**
* Converts a markdown files to an aiff
* @command build_command_list
*/
function pestle_cli($argv)
{
    $lookup = buildCommandList();
    foreach($lookup as $command=>$file)
    {
        \Pulsestorm\Pestle\Library\output($command);
    }
    
}

}
namespace Pulsestorm\Cli\Code_Generation{
use function Pulsestorm\Pestle\Importer\pestle_import;

function templateCommandClass($namespace, $module_name, $command_name)
{
    $command_prefix = 'ps';
    
    $class_file_string = 
'<?php
namespace '.$namespace.'\\'.$module_name.'\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class '.$command_name.' extends Command
{
    protected function configure()
    {
        $this->setName("'.$command_prefix.':'.strToLower($command_name).'");
        $this->setDescription("A command the programmer was too lazy to enter a description for.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Hello World");  
    }
} ';
    return $class_file_string;
}

function createNamespaceFromNamespaceAndCommandName($namespace_module, $command_name)
{
    if(strpos($command_name,'generate_') !== false)
    {
        $parts = explode('_', $command_name);
        array_shift($parts);
        
        $post_fix = implode(' ', $parts);
        $post_fix = ucwords($post_fix);
        $post_fix = str_replace(' ', '\\', $post_fix);
        $command_name = 'generate\\' . $post_fix;
    }
    $namespace_portion = str_replace(' ','_',
        ucwords(str_replace('_',' ',$command_name)));
    //$namespace = 'Pulsestorm\Magento2\Cli\\' . $namespace_portion;
    $namespace_module = trim($namespace_module, '\\');
    $namespace = $namespace_module . '\\' . $namespace_portion;
    return $namespace;
}

function createPathFromNamespace($namespace)
{
    $parts = explode('\\', $namespace);
    $path_dir  = strToLower('modules/' . implode('/', $parts));
    $path_full = $path_dir . '/module.php';
    return $path_full;
}

function generateInstallSchemaNewTable($table_name)
{
    return '$installer->getConnection()->newTable(
            $installer->getTable(\''.$table_name.'\')
    )';
}

function exportArrayForString($array)
{
    ob_start();
    var_export($array);
    return ob_get_clean();
}

function processNewColumnAttributes($array)
{
    $attrs = exportArrayForString($array);

    $old_array_syntax = "/^array \((.*)\)$/s";
    $new_array_syntax = "[$1]";
    $attrs = preg_replace($old_array_syntax, $new_array_syntax, $attrs);

    $escaped_ddl_constant = "/\'(\\\\[^:]+)::([^']+)\'/";
    $unescaped_ddl_constant = "$1::$2";
    $attrs = preg_replace($escaped_ddl_constant, $unescaped_ddl_constant, stripslashes($attrs));

    $normalize_whitespace = "/\s+/";
    $attrs = preg_replace($normalize_whitespace, " ", $attrs);

    return $attrs;
}

function generateInstallSchemaNewColumn($column)
{
    return '->addColumn(
            \''.$column['name'].'\',
            '.$column['type_constant'].',
            '.$column['size'].',
            '.processNewColumnAttributes($column['attributes']).',
            \''.$column['comment'].'\'
        )';
}

function generateInstallSchemaAddComment($comment)
{
    return '->setComment(
             \''.$comment.'\'
         )';
}

function generateInstallSchemaGetDefaultColumnId($table)
{
    $id = [
        'name'          => $table . '_id',
        'type_constant' => '\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER',
        'size'          => 'null',
        'attributes'    => ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
        'comment'       => 'Entity ID'
    ];
    return $id;
}

function generateInstallSchemaGetDefaultColumnTitle()
{
    $title = [        
        'name'          => 'title',
        'type_constant' => '\Magento\Framework\DB\Ddl\Table::TYPE_TEXT',
        'size'          => 255,
        'attributes'    => ['nullable' => false],
        'comment'       => 'Demo Title'
    ];
    return $title;
}

function generateInstallSchemaGetDefaultColumnCreationTime()
{
    $creation_time = [        
        'name'          => 'creation_time',
        'type_constant' => '\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP',
        'size'          => 'null',
        'attributes'    => ['nullable' => false, 'default' => '\Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT'],
        'comment'       => 'Creation Time'
    ];            
    return $creation_time;
}

function generateInstallSchemaGetDefaultColumnUpdateTime()
{
    $update_time = [
        'name'          =>'update_time',
        'type_constant' =>'\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP',
        'size'          => 'null',
        'attributes'    => ['nullable' => false, 'default' => '\Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE'],
        'comment'       => 'Modification Time'
    ];
    return $update_time;
}

function generateInstallSchemaGetDefaultColumnIsAction()
{
    $is_active = [
        'name'          => 'is_active',
        'type_constant' => '\Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT',
        'size'          => 'null',
        'attributes'    => ['nullable' => false, 'default' => '1'],
        'comment'       => 'Is Active'    
    ];
    return $is_active;
}
function generateInstallSchemaGetDefaultColumns($table)
{
    $id            = generateInstallSchemaGetDefaultColumnId($table);
    $title         = generateInstallSchemaGetDefaultColumnTitle();
    $creation_time = generateInstallSchemaGetDefaultColumnCreationTime();
    $update_time   = generateInstallSchemaGetDefaultColumnUpdateTime();
    $is_active     = generateInstallSchemaGetDefaultColumnIsAction();
    return [$id, $title, $creation_time, $update_time, $is_active];
}

function generateInstallSchemaTable($table_name='', $columns=[], $comment='')
{
    $block = '$table = ' . generateInstallSchemaNewTable($table_name);
    $default_columns = generateInstallSchemaGetDefaultColumns($table_name);
    $columns = array_merge($default_columns, $columns);
    foreach($columns as $column)
    {
        $block .= generateInstallSchemaNewColumn($column);
    }
    if($comment)
    {
        $block .= generateInstallSchemaAddComment($comment);
    }
    return $block .= ';' . "\n" .
    '$installer->getConnection()->createTable($table);';
}

function templateRegistrationPhp($module_name, $type='MODULE')
{
    return '<?php
    \Magento\Framework\Component\ComponentRegistrar::register(
        \Magento\Framework\Component\ComponentRegistrar::'.$type.',
        \''.$module_name.'\',
        __DIR__
    );';    
}

function createBasicClassContents($full_model_name, $method_name, $extends=false)
{
    $parts = explode('\\', $full_model_name);
    $name = array_pop($parts);
    $namespace = implode('\\', $parts);
    $contents =  '<' . '?' . 'php' . "\n";
    $contents .= 'namespace ' . $namespace . ";\n";
    $contents .= 'class ' . $name ;
    $contents .= "\n" . '{' . "\n";
    $contents .= '    public function ' . $method_name . '($parameters)' . "\n";
    $contents .= '    {' . "\n"; 
    $contents .= '        var_dump(__METHOD__); exit;' . "\n";
    $contents .= '    }' . "\n";
    $contents .= '}' . "\n";
    return $contents;
}

function templateInterface($interface, $functions=[])
{
    $class      = trim($interface, '\\');
    $parts      = explode('\\',$class);
    $name       = array_pop($parts);
    $template   = '<' . '?' . 'php' . "\n" .
    'namespace ' . implode('\\',$parts) . ";\n" . 
    "interface $name \n{\n\n";
    $template   .= "}";    
    
    return $template;
}

function createClassTemplateWithUse($class, $extends=false, $implements=false, $includeUse=false)
{
    $template = createClassTemplate($class, $extends, $implements, $includeUse);
    $template = preg_replace('%namespace.+?;%',"$0\n<\$use\$>",$template);
    return $template;
}

function createClassTemplate($class, $extends=false, $implements=false, $includeUse=false)
{
    $class = trim($class, '\\');
    $parts = explode('\\',$class);
    $name  = array_pop($parts);
    
    $template = '<' . '?' . 'php' . "\n" .
    'namespace ' . implode('\\',$parts) . ";\n";
    if($includeUse)
    {
        $template .= '<$use$>' . "\n";
    }
    $template .= "class $name";
    if($extends)
    {
        $template .= " extends $extends";
    }
    if($implements)
    {
        $template .= " implements $implements";
    }    
    $template .= "\n" . 
    '{' . '<$body$>' . '}' . "\n";

    return $template;
}

function getZendPsrLogLevelMap()
{
    return [
        'Zend_Log::EMERG'   => 'Psr\Log\LogLevel::EMERGENCY',   // Emergency: system is unusable
        'Zend_Log::ALERT'   => 'Psr\Log\LogLevel::ALERT',       // Alert: action must be taken immediately
        'Zend_Log::CRIT'    => 'Psr\Log\LogLevel::CRITICAL',    // Critical: critical conditions
        'Zend_Log::ERR'     => 'Psr\Log\LogLevel::ERROR',       // Error: error conditions
        'Zend_Log::WARN'    => 'Psr\Log\LogLevel::WARNING',     // Warning: warning conditions
        'Zend_Log::NOTICE'  => 'Psr\Log\LogLevel::NOTICE',      // Notice: normal but significant condition
        'Zend_Log::INFO'    => 'Psr\Log\LogLevel::INFO',        // Informational: informational messages
        'Zend_Log::DEBUG'   => 'Psr\Log\LogLevel::DEBUG',       // Debug: debug messages    
    ];
}

function createControllerClass($class, $area, $acl='ACL RULE HERE')
{
    $extends = '\Magento\Framework\App\Action\Action';
    if($area === 'adminhtml')
    {
        $extends = '\Magento\Backend\App\Action';
    }
    $template = createControllerClassTemplate($class, $extends);
    
    $context_hint  = '\Magento\Framework\App\Action\Context';
    if($area === 'adminhtml')
    {
        $context_hint = '\Magento\Backend\App\Action\Context';
    }    
    $body = '
    protected $resultPageFactory;
    public function __construct(
        ' . $context_hint . ' $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;        
        return parent::__construct($context);
    }
    
    public function execute()
    {
        return $this->resultPageFactory->create();  
    }';
    if($area === 'adminhtml')
    {
        $body .= '    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(\''.$acl.'\');
    }            
        ';        
    }
    $body .= "\n";
    return str_replace('<$body$>', $body, $template);
}

function createControllerClassTemplate($class, $extends=false, $implements=false)
{
    $class = trim($class, '\\');
    $parts = explode('\\',$class);
    $name  = array_pop($parts);
    
    $template = '<' . '?' . 'php' . "\n" .
    'namespace ' . implode('\\',$parts) . ";\n" . 
    "class $name";
    if($extends)
    {
        $template .= " extends $extends";
    }
    if($implements)
    {
        $template .= " implements $implements";
    }    
    $template .= "\n" . 
    '{' . '<$body$>' . '}' . "\n";

    return $template;
}

/**
* @command library
*/
function pestle_cli()
{
}}
namespace Pulsestorm\Cli\Format_Php{
use function Pulsestorm\Pestle\Importer\pestle_import;



function tokenIsSemiColonAndNextTokenIsNotTCloseTag($tokens, $key)
{
    $current_token = $tokens[$key];
    $next_token    = false;
    if(array_key_exists($key+1, $tokens))
    {
        $next_token = $tokens[$key+1];
    }
    if(!$next_token)
    {
        return false;
    }
    
    if($current_token->token_value === ';' && $next_token->token_name !== 'T_CLOSE_TAG')
    {
        return true;
    }

    return false;
}

/**
* One Line Description
*
* @command format_php
* @argument file Which file?
*/
function pestle_cli($argv)
{
    define('START', 0);
    define('PARSE_IF', 1);
    define('INSIDE_IF_BLOCK', 2);
    
    $file = $argv['file'];
    $tokens = \Pulsestorm\Cli\Token_Parse\pestle_token_get_all(file_get_contents($file));    
    
    //remove whitespace tokens
    $tokens = array_filter($tokens, function($token){
        return $token->token_name !== 'T_WHITESPACE';
    });
    $tokens = array_values($tokens);

    $state        = 0;
    $indent_level = 0;
    foreach($tokens as $key=>$token)
    {
        $before = '';
        $after  = '';
        
        //state switching
        if($token->token_name == 'T_IF')
        {
            $state = PARSE_IF;
        }
        
        if($state == PARSE_IF && $token->token_value === ':')
        {
            $indent_level++;
            $state = INSIDE_IF_BLOCK;
        }
        
        if($state == INSIDE_IF_BLOCK && $token->token_name === 'T_ENDIF')
        {
            $state = START;
            $indent_level--;
        }
                        
        //manipuate extra output tokens
        if($token->token_value === '{')
        {
            $indent_level++;
            $after = "\n" . str_repeat("    ", $indent_level);
        }
        
        if($token->token_value === '}')
        {
            $indent_level--;        
            $after = "\n" . str_repeat("    ", $indent_level);
        }        
        
        if($token->token_name === 'T_CLOSE_TAG')
        {
            $after = "\n" . str_repeat("    ", $indent_level);       
        }
        
        if(tokenIsSemiColonAndNextTokenIsNotTCloseTag($tokens, $key))
        {
            $after = "\n" . str_repeat("    ", $indent_level);       
        }
        
        if($token->token_name === 'T_INLINE_HTML' && !trim($token->token_value))
        {
            continue;
        }
        //do output
        echo $before;
        echo $token->token_value;
        echo $after;        
    }
}
}
namespace Pulsestorm\Cli\Md_To_Say{
use function Pulsestorm\Pestle\Importer\pestle_import;


use Michelf\Markdown;

function swapExtension($filename, $from, $to)
{
    return preg_replace('%\.'.$from.'$%','.' . $to, $filename);
}

/**
* Converts a markdown files to an aiff
* @command md_to_say
*/
function pestle_cli($argv)
{
    $file = \Pulsestorm\Pestle\Library\inputOrIndex("Path to Markdown File?", null, $argv, 0);

    $contents = file_get_contents($file);
    $html     = Markdown::defaultTransform($contents);            
    $html     = preg_replace(
        '%<pre><code>.+?</code></pre>%six', 
        '<p>[CODE SNIPPED].</p>', 
        $html
    );
    $html = str_replace('</p>','</p><br>',$html);
    
    $tmp = tempnam('/tmp', 'md_to_say') . '.html';
    file_put_contents($tmp, $html);    
    
    $cmd = 'textutil -convert txt ' . $tmp;
    `$cmd`;
    
    $tmp_txt    = swapExtension($tmp, 'html','txt');
    $tmp_aiff   = swapExtension($tmp, 'html','aiff');
    
    $cmd = "say -f $tmp_txt -o $tmp_aiff";
    \Pulsestorm\Pestle\Library\output($cmd);
    `$cmd`;
    // $tmp_txt = preg_replace('%\.html$%','.txt', $tmp);
    
    \Pulsestorm\Pestle\Library\output($tmp_aiff);    
    \Pulsestorm\Pestle\Library\output("Done");
}}
namespace Pulsestorm\Cli\Self_Update{
use function Pulsestorm\Pestle\Importer\pestle_import;


define(
    'PESTLE_CURRENT_URL',
    'http://pestle.pulsestorm.net/pestle.phar'
);
function getLocalPharPath()
{
    global $argv;
    $path = realpath($argv[0]);
    return $path;
}

function isPhar($path)
{
    $contents = file_get_contents($path);
    return strpos($contents, '__HALT_COMPILER') !== false;
}
function validateLocalPharPath($path)
{    
    if(!isPhar($path))
    {
        \Pulsestorm\Pestle\Library\output("$path doesn't look like a phar -- can't update.");
        exit(1);
    }
}

function fetchCurrentAndWriteToTemp()
{
    $contents = file_get_contents(PESTLE_CURRENT_URL);
    $file     = tempnam('/tmp','pestle_');
    file_put_contents($file,$contents);
    \Pulsestorm\Pestle\Library\output("Downloaded to $file");
    return $file;
}

function backupCurrent($path)
{
    $pathBackup = $path . '.' . time();
    \Pulsestorm\Pestle\Library\output("Backing up $path to $pathBackup");
    copy($path, $pathBackup);
    if(!file_exists($pathBackup) || !isPhar($pathBackup))
    {
        \Pulsestorm\Pestle\Library\output("Could not backup to $pathBackup, bailing");
        exit(1);
    }
    \Pulsestorm\Pestle\Library\output("Backed up current pestle to $pathBackup");
    return $pathBackup;    
}

/**
* @command selfupdate
*/
function pestle_cli()
{
    $localPharPath = getLocalPharPath();    
    $tmpFile       = fetchCurrentAndWriteToTemp();
    
    validateLocalPharPath($localPharPath);      
    backupCurrent($localPharPath);    
    
    //super gross -- thanks PHP
    $permissions = substr(sprintf('%o', fileperms($localPharPath)),-4);
    
    \Pulsestorm\Pestle\Library\output("Reaplced $localPharPath");
    rename($tmpFile, $localPharPath);
    
    chmod($localPharPath, octdec($permissions));
}}
namespace Pulsestorm\Cli\Token_Parse{
use function token_get_all as php_token_get_all;

define('STATE_PARSING',                             0);
define('STATE_FOUND_FUNCTION',                      1);
define('STATE_FOUND_SPECIFIC_FUNCTION',             2);
define('STATE_FOUND_FIRST_POST_SPECIFIC_BRACKET',   3);
define('STATE_BRACKET_COUNT_ZEROD_OUT',             4);

/**
* @command library
*/
function pestle_cli()
{
}

function getFunctionFromClass($string, $function_name)
{
    return getFunctionFromCode($string, $function_name);
}

function getFunctionFromCode($string, $function)
{
    $string = trim($string);
    if($string[0] !== '<' && $string[1] !== '?')
    {
        $string = '<' . '?php ' . $string;
    }

    $tokens = pestle_token_get_all($string);
    $state                              = 0;    
    $count_bracket                      = 0;
    $new_tokens                         = [];
    foreach($tokens as $token)
    {
        $token_name = $token->token_name;
        $token_value = $token->token_value;
        switch($state)
        {
            case STATE_PARSING:
                if($token_name == 'T_FUNCTION')
                {
                    $state = STATE_FOUND_FUNCTION;
                }                
                break;
            case STATE_FOUND_FUNCTION:
                if($token_name == 'T_STRING' && $token_value == $function)
                {
                    $new_tokens[] = $token;
                    $state = STATE_FOUND_SPECIFIC_FUNCTION;
                }
                if($token_name == 'T_STRING' && $token_value !== $function)
                {
                    $state = STATE_PARSING;
                }
                break;
            case STATE_FOUND_SPECIFIC_FUNCTION:
                $new_tokens[] = $token;
                if($token_name == 'T_SINGLE_CHAR' && $token_value == '{')
                {
                    $state = STATE_FOUND_FIRST_POST_SPECIFIC_BRACKET;
                    $count_bracket++;
                }
                break;
            case STATE_FOUND_FIRST_POST_SPECIFIC_BRACKET:
                $new_tokens[] = $token;
                if($token_name == 'T_SINGLE_CHAR' && $token_value == '{')
                {
                    $count_bracket++;
                }
                if($token_name == 'T_SINGLE_CHAR' && $token_value == '}')
                {
                    $count_bracket--;
                }   
                if($count_bracket === 0)
                {
                    $state = STATE_BRACKET_COUNT_ZEROD_OUT;
                }             
                break;
            case STATE_BRACKET_COUNT_ZEROD_OUT:
            
                $values = array_map(function($token){
                    return $token->token_value;
                }, $new_tokens);
                return 'function ' . implode('',  $values);
                break;
            default:
                throw new \Exception("Unknown State");
        }
    }
    //if } is the last string
    if($count_bracket === 0)
    {
        $values = array_map(function($token){
            return $token->token_value;
        }, $new_tokens);
        return 'function ' . implode('',  $values);        
    }
    
    throw new \Exception("Parser Bug. Cries.");    
}

function fix_token($token)
{
    if(is_array($token))
    {
        $token['token_name'] = token_name($token[0]);
        $token['token_value'] = $token[1];
        $token['token_line'] = $token[2];
    }    
    else
    {
        $tmp                = array();
        $tmp['token_value'] = $token;
        $tmp['token_name']  = 'T_SINGLE_CHAR';
        $token              = $tmp;
    }
    return (object) $token;
}

function fix_all_tokens(&$tokens)
{
    for($i=0;$i<count($tokens);$i++)
    {
        $tokens[$i] = fix_token($tokens[$i]);
    }
    return $tokens;
}

function outputTokens($tokens, $buffer=false)
{
    if($buffer)
    {
        ob_start();
    }
    foreach($tokens as $token)
    {
        echo $token->token_value;
    }
    if($buffer)
    {
        return ob_get_clean();
    }
}

function pestle_token_get_all($string)
{
    $tokens = php_token_get_all($string);
    return fix_all_tokens($tokens);
}

function token_get_all($string)
{
    $tokens = php_token_get_all($string);
    return fix_all_tokens($tokens);
}

function run($argv)
{
    $file = $argv[1];
    $result = outputChangedFile($file, true);
    echo $result;
}

function outputChangedFile($file, $buffer)
{
    $tokens = pestle_token_get_all(file_get_contents($file));        
    $tokens = fix_all_tokens($tokens);

    $to_replace = array(
        'Mage_Adminhtml_Controller_Action'              => '\Magento\Backend\Controller\Adminhtml\Action',
        'Mage_Core_Block_Template'                      => '\Magento\Core\Block\Template',
        'Mage_Core_Helper_Abstract'                     => '\Magento\Core\Helper\AbstractHelper',
        'Mage_Core_Helper_Data'                         => '\Magento\Core\Helper\Data',
        'Mage_Core_Model_Abstract'                      => '\Magento\Core\Model\AbstractModel',
        'Mage_Core_Model_Session_Abstract'              => '\Magento\Core\Model\Session\AbstractSession',
        'Mage_Core_Model_Event_Invoker_InvokerDefault'  => '\Magento\Event\Invoker\InvokerDefault',
        'Mage_Core_Model_Event_Manager'                 => '\Magento\Event\Manager',
        'Varien_Object'                                 => '\Magento\Object', 
        'Varien_Event_Observer'                         => '\Magento\Event\Observer'
    );
    foreach($tokens as $token)
    { 
        if($token->token_name = 'T_STRING' && in_array($token->token_value, array_keys($to_replace)))
        {
            $token->token_value = $to_replace[$token->token_value];
        }
    }    
    
    
    return outputTokens($tokens, $buffer);
}
}
namespace Pulsestorm\Financial\Parse_Citicard{
use function Pulsestorm\Pestle\Importer\pestle_import;


function apply_column_headers($data)
{
    static $headers = false;
    $headers = !$headers ? array_keys(array_flip($data)) : $headers;		
    
    for($i=0;$i<count($headers);$i++)
    {
        $data[$headers[$i]] = $data[$i];
    }
    
    foreach($data as $key=>$value)
    {
        if(is_numeric($key))
        {
            unset($data[$key]);
        }
    }
    return $data;
}

function file_get_contents_csv($filename,$has_headers=true)
{
    $all 		= array();
    $file 		= fopen($filename,'r');
    if($has_headers)
    {
        apply_column_headers(fgetcsv($file));
    }
    while($data = fgetcsv($file))
    {
        if($has_headers)
        {
            $all[] 	= apply_column_headers($data);
        }
        else
        {
            $all[] 	= $data;
        }
        
    }
    fclose($file);	
    
    return $all;
}

function parseDescription($string)
{
    return [
        'description'=>trim($string)
    ];
//     preg_match('%\d\d\d-\d\d\d-\d\d\d\d%', $string, $matches);
//     $phone = array_pop($matches);
//     
//     $state = 
}
	
/**
* One Line Description
*
* @command parse_citicard
* @argument file File to Parse?
* @argument count Starting Count?
*/
function pestle_cli($argv)
{
    $file   = $argv['file'];
    $count  = $argv['count'];
    $items  = file_get_contents_csv($file);
    foreach($items as $item)
    {
        $parts = parseDescription($item['Description']);
        $description = $parts['description'];
        if($description === 'ELECTRONIC PAYMENT-THANK YOU')
        {
            continue;
        }
        // 120-Paid On 03/10/2016:028.28 Do it Best Hardware
        \Pulsestorm\Pestle\Library\output($count,'-Paid On ', $item['Date'],':',$item['Debit'], 
            ' ', $description);
        $count++;
    }
}
}
namespace Pulsestorm\Magento2\Cli\Baz_Bar{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command baz_bar
*/
function pestle_cli($argv)
{
    \Pulsestorm\Pestle\Library\output("Hello Sailor");
}
}
namespace Pulsestorm\Magento2\Cli\Check_Acl{
use function Pulsestorm\Pestle\Importer\pestle_import;




function traverseXmlFilesForNodeAndExtractUniqueValues($dir, $file, $node_name, $callback=false)
{
    $values = [];
    $files  = \Pulsestorm\Phpdotnet\glob_recursive($dir . '/' . $file);
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
    $tokens = \Pulsestorm\Cli\Token_Parse\pestle_token_get_all(
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
    $files = \Pulsestorm\Phpdotnet\glob_recursive($dir . '/*/Controller/*.php');
    $code  = array_map(function($file){
        $function = \Pulsestorm\Cli\Token_Parse\getFunctionFromCode(file_get_contents($file), '_isAllowed');
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
* @command check_acl
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
                         
    \Pulsestorm\Pestle\Library\output("Checking that all used IDs are defined:");    
    foreach($used_rule_ids as $id)
    {
        $result = 'ERROR -- not defined';
        if(in_array($id, $defined_rule_ids))
        {
            $result = 'OK                  ';
        }
        \Pulsestorm\Pestle\Library\output("  $result : $id");
    }

    \Pulsestorm\Pestle\Library\output('');
        
    \Pulsestorm\Pestle\Library\output("Checking that all defined IDs are used:");            
    foreach($defined_rule_ids as $id)
    {
        $result = 'ERROR -- not used';    
        if(in_array($id, $used_rule_ids))
        {
            $result = 'OK               ';
        }    
        \Pulsestorm\Pestle\Library\output("  $result : $id");
    }
    
    \Pulsestorm\Pestle\Library\output('');
    \Pulsestorm\Pestle\Library\output('An unused ID may indicate an error, or may indicate a valid parent rule');
    \Pulsestorm\Pestle\Library\output("Done");
}
}
namespace Pulsestorm\Magento2\Cli\Check_Class_And_Namespace{
use function Pulsestorm\Pestle\Importer\pestle_import;




function parseNamespace($contents)
{
    preg_match('%namespace(.+?);%', $contents, $matches);
    
    if(count($matches) < 1)
    {
        return false;
    }
    return trim($matches[1]);
}

function parseClass($contents)
{
    preg_match('%class(.+?){%s', $contents, $matches);
    if(count($matches) < 1)
    {
        return false;
    }    
    $line = trim($matches[1]);
    $parts = preg_split('%\s{1,100}%',$line);
    return array_shift($parts);
}

/**
* 
* @command check_class_and_namespace
*/
function pestle_cli($argv)
{    
    $path = \Pulsestorm\Pestle\Library\inputOrIndex('Which folder?','/path/to/magento/app/code/Pulsestorm',
    $argv, 0);
    $files = \Pulsestorm\Phpdotnet\glob_recursive($path . '/*');
    
    foreach($files as $file)
    {
        $file = realpath($file);
        if(strpos($file, '.php') === false)
        {
            \Pulsestorm\Pestle\Library\output("NOT .php: Skipping $file");
            continue;
        }

        $contents  = file_get_contents($file);
        $namespace = parseNamespace($contents);
        if(!$namespace)
        {
            \Pulsestorm\Pestle\Library\output("No Namspace: Skipping $file");
            continue;            
        }
        $class     = parseClass($contents);
        if(!$class)
        {
            \Pulsestorm\Pestle\Library\output("No Class: Skipping $class");
            continue;            
        }        
        $full_class = $namespace . '\\' . $class;
        $path       = str_replace('\\','/', $full_class) . '.php';
    
        if(strpos($file, $path) === false)
        {
            \Pulsestorm\Pestle\Library\output("ERROR: Path `$path` not in");
            \Pulsestorm\Pestle\Library\output($file);
        }
        else
        {
            \Pulsestorm\Pestle\Library\output('.');
        }
    }
}
}
namespace Pulsestorm\Magento2\Cli\Check_Htaccess{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Short Description
* Long
* Description
* @command check_htaccess
*/
function pestle_cli($argv)
{
    $files = [
        './app/.htaccess',
        './.htaccess',
        './app/.htaccess',
        './bin/.htaccess',
        './dev/.htaccess',
        './lib/.htaccess',
        './phpserver/.htaccess',
        './pub/.htaccess',
        './pub/errors/.htaccess',
        './pub/media/.htaccess',
        './pub/media/customer/.htaccess',
        './pub/media/downloadable/.htaccess',
        './pub/media/import/.htaccess',
        './pub/media/theme_customization/.htaccess',
        './pub/static/.htaccess',
        './pub/static.finally/.htaccess',
        './setup/.htaccess',
        './setup/config/.htaccess',
        './setup/performance-toolkit/.htaccess',
        './setup/pub/.htaccess',
        './setup/src/.htaccess',
        './setup/view/.htaccess',
        './update/.htaccess',
        './update/app/.htaccess',
        './update/dev/.htaccess',
        './update/pub/.htaccess',
        './update/var/.htaccess',
        './var/.htaccess',
        './var/composer_home/.htaccess',
        './var/composer_home/cache/.htaccess',        
    ];
    
    foreach($files as $file)
    {
        if(!file_exists($file))
        {
            \Pulsestorm\Pestle\Library\output("ERROR: Missing: " . $file);
            continue;
        }
        \Pulsestorm\Pestle\Library\output("Found: $file");
    }
    \Pulsestorm\Pestle\Library\output("Done");
}}
namespace Pulsestorm\Magento2\Cli\Check_Registration{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Short Description
* Long
* Description
* @command check_registration
*/
function pestle_cli($argv)
{
    $path = 'app/code';
    if(count($argv) > 0)
    {
        $Path = $argv[0];
    }
    
    foreach(glob($path . '/*/*') as $file)
    {
        $parts = explode('/', $file);
        $module = implode('_', array_slice($parts, count($parts) - 2));
        
        $file = $file . '/' . 'registration.php';
        if(file_exists($file))
        {
            \Pulsestorm\Pestle\Library\output("Registration Exists");
            $contents = file_get_contents($file);
            if(strpos($contents, "'" . $module . "'") !== false)
            {
                \Pulsestorm\Pestle\Library\output("Registration contains $module string");
                continue;
            }
            \Pulsestorm\Pestle\Library\output("However, it's missing single quoted '$module' string");
            \Pulsestorm\Pestle\Library\output("");
            continue;            
        }
        \Pulsestorm\Pestle\Library\output("No $file");
        $answer = \Pulsestorm\Pestle\Library\input("Create? [Y/n]", 'n');
        if($answer !== 'Y')
        {
            continue;
        }
        file_put_contents($file, \Pulsestorm\Cli\Code_Generation\templateRegistrationPhp($module));
        \Pulsestorm\Pestle\Library\output("Created $file");
        \Pulsestorm\Pestle\Library\output("");
    }
}
}
namespace Pulsestorm\Magento2\Cli\Check_Templates{
use function Pulsestorm\Pestle\Importer\pestle_import;





/**
* Checks for incorrectly named template folder
* Long
* Description
* @command check_templates
*/
function pestle_cli($argv)
{
    $base = \Pulsestorm\Magento2\Cli\Library\askForModuleAndReturnFolder($argv) . '/view';
    
    $view_areas = glob($base . '/*');
    foreach($view_areas as $area)
    {
        \Pulsestorm\Pestle\Library\output("Checking $area");
        if(is_dir($area . '/template'))
        {
            \Pulsestorm\Pestle\Library\output("    `template` should be `templates`");
            continue;
        }
        \Pulsestorm\Pestle\Library\output("    OK");
    }
    \Pulsestorm\Pestle\Library\output("Done");
}}
namespace Pulsestorm\Magento2\Cli\Class_From_Path{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Short Description
* Long
* Description
* @command class_from_path
*/
function pestle_cli($argv)
{
    $path = \Pulsestorm\Pestle\Library\input('Enter Path: ');
    $parts = explode('/',$path);
    $class = [];
    foreach($parts as $part)
    {
        if($part === 'code' || count($class) > 0)
        {
            $class[] = $part;
        }
    }
    array_shift($class);

    $class_name = array_pop($class);
    $body = '<' . '?' . 'php' . "\n" . 
    'namespace ' . implode('\\', $class) . ";\n" .
    'class ' . str_replace('.php','',$class_name) . "\n" . '{}';
    
    \Pulsestorm\Pestle\Library\output($body);
}}
namespace Pulsestorm\Magento2\Cli\Convert_Class{
use function Pulsestorm\Pestle\Importer\pestle_import;







/**
* Short Description
* Long
* Description
* @command convert_class
*/
function pestle_cli($argv)
{
    $type        = \Pulsestorm\Pestle\Library\input("Which type (model, helper, block)?", 'model');
    $alias       = \Pulsestorm\Pestle\Library\input("Which alias?", 'pulsestorm_helloworld/observer_newsletter');    
    $path_config = \Pulsestorm\Pestle\Library\input("Which config.xml?", 'app/code/community/Pulsestorm/Helloworld/etc/config.xml');
    
    $config      = simplexml_load_file($path_config);
    $class       = \Pulsestorm\Magento2\Cli\Library\resolveAlias($alias, $config, $type);
    // output($class);
    $mage_1_path = \Pulsestorm\Magento2\Cli\Library\getMage1ClassPathFromConfigPathAndMage2ClassName($path_config, $class);
    $mage_2_path = str_replace(['/core','/community','/local'], '', $mage_1_path);
    
    
    \Pulsestorm\Pestle\Library\output('');
    \Pulsestorm\Pestle\Library\output("New Class Path");
    \Pulsestorm\Pestle\Library\output('-----------------------');
    \Pulsestorm\Pestle\Library\output($mage_2_path);
    \Pulsestorm\Pestle\Library\output('');
    
    \Pulsestorm\Pestle\Library\output("New Class Content");
    \Pulsestorm\Pestle\Library\output('-----------------------');
    \Pulsestorm\Pestle\Library\output(\Pulsestorm\Magento2\Cli\Library\convertMageOneClassIntoNamespacedClass($mage_1_path));
    \Pulsestorm\Pestle\Library\output('');
    
    \Pulsestorm\Pestle\Library\output("DI Lines");
    \Pulsestorm\Pestle\Library\output('-----------------------');
    \Pulsestorm\Pestle\Library\output(implode("\n", \Pulsestorm\Magento2\Cli\Library\getDiLinesFromMage2ClassName($class)));
    \Pulsestorm\Pestle\Library\output('');    
}
}
namespace Pulsestorm\Magento2\Cli\Convert_Observers_Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;




/**
* Short Description
* Long
* Description
* @command convert_observers_xml
*/
function pestle_cli($argv)
{
    $paths = $argv;
    if(count($argv) === 0)
    {
        $paths = [\Pulsestorm\Pestle\Library\input("Which config.xml?", 'app/code/Mage/Core/etc/config.xml')];
    }
    foreach($paths as $path)
    {
        $xml = simplexml_load_file($path);
        $scopes = ['global','adminhtml','frontend'];
        foreach($scopes as $scope)
        {
            $xml_new = \Pulsestorm\Magento2\Cli\Library\convertObserverTreeScoped($xml->{$scope}, $xml);
            \Pulsestorm\Pestle\Library\output($scope);
            \Pulsestorm\Pestle\Library\output($xml_new->asXml());
            \Pulsestorm\Pestle\Library\output('--------------------------------------------------');            
        }
    }

}}
namespace Pulsestorm\Magento2\Cli\Convert_Selenium_Id_For_Codecept{
use function Pulsestorm\Pestle\Importer\pestle_import;



function getCommandAndTwoArgs($string)
{
    $parts = preg_split('%[\r\n]%',$string);
    
    foreach($parts as $part)
    {
        if(!$part){continue;}
        preg_match('%<td>(.*)</td>%six', $part, $matches);
        if(!$matches){continue;}
        if(count($matches) < 1)
        {
            var_dump($part);
            exit(__FUNCTION__);
        }
        $stuff[] = str_replace('&gt;','>',$matches[1]);
        // $part = ltrim($part, '<td>');        
        // $part = rtrim($part, '</td>');                
        // $part = rtrim('</td>', $part);
        // output($part);
    }
    
    return [
        'command'   =>$stuff[0],
        'arg1'      =>$stuff[1],
        'arg2'      =>$stuff[2],
    ];
}

function parseIntoCommands($contents)
{
    $parts  = explode('<tbody>', $contents);
    $contents = array_pop($parts);
    $parts  = preg_split('%</tr>%six', $contents);
    array_pop($parts);
    $all    = [];
    foreach($parts as $part)
    {
        $all[] = getCommandAndTwoArgs($part);
        
    }
    return $all;
}

function getCodeceptionTemplate()
{
    return '$I-><$methodName$>(<$args$>);';
}

function convertCommandPause($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','wait',$template);
    $template = str_replace('<$args$>',($info['arg1'] / 1000),$template);    
    return $template;
    // return '$I->wait('.$info['arg1'].');';
}

function getDefaultTimeoutInSeconds()
{
    return 30;
}

function convertCommandOpen($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','amOnPage',$template);
    $template = str_replace('<$args$>',
        "'" . $info['arg1'] . "'",$template);    
    return $template;
}

function convertCommandClickandwait($info)
{
    $template = getCodeceptionTemplate();
    $timeout  = '"' . getDefaultTimeoutInSeconds() . '"';
    $template = str_replace('<$methodName$>','click',$template);
    $template = str_replace('<$args$>',"'" . $info['arg1'] . "'",$template); 
    $template .=  "\n" . convertInfoArray(['command'=>'waitForElementPresent',
                                'arg1'=>'css=body','arg2'=>'']);    
    return $template;
}

function convertCommandWaitfortext($info)
{
    $template = getCodeceptionTemplate();
    $timeout  = '"' . getDefaultTimeoutInSeconds() . '"';
    $template = str_replace('<$methodName$>','selectOption',$template);
    $template = str_replace('<$args$>',
        "'" . $info['arg1'] . "',".$timeout.",'" . $info['arg2'] . "'",$template);    
    return $template;

}

function convertCommandSelect($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','selectOption',$template);
    $template = str_replace('<$args$>',
        "'" . $info['arg1'] . "','" . $info['arg2'] . "'",$template);    
    return $template;
}

function convertCommandType($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','fillField',$template);
    $template = str_replace('<$args$>',
        "'" . $info['arg1'] . "','" . $info['arg2'] . "'",$template);    
    return $template;
}

function convertCommandWaitforelementpresent($info)
{
    $timeout  = '"' . getDefaultTimeoutInSeconds() . '"';
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','waitForElement',$template);
    $template = str_replace('<$args$>',"'" . $info['arg1'] . "'," . $timeout,$template);    
    return $template; 
}

function convertCommandClick($info)
{
    $template = getCodeceptionTemplate();
    $template = str_replace('<$methodName$>','click',$template);
    $template = str_replace('<$args$>',"'" . $info['arg1'] . "'",$template);    
    return $template; 
}

function convertInfoArray($info)
{
    $method = 'convertCommand' . 
        ucwords(strtolower($info['command']));
    $method = '\Pulsestorm\Magento2\Cli\Convert_Selenium_Id_For_Codecept\\' . $method;
    return call_user_func($method, $info);        
    // return '$I-><$methodName$>(<$args$>);';
}

/**
* Converts a selenium IDE html test for conception
* @todo serialize numbers as string
* @todo unescape HTML in args
* @todo remove css= ... convert id= to #
* @todo waitfortext is selectOption, has incorrect arguments, reversed
* @todo name= is not a thinge either
* @todo label= is not a thing
* @command convert_selenium_id_for_codecept
*/
function pestle_cli($argv)
{
    $file = \Pulsestorm\Magento2\Cli\Library\indexOrInput('Which Selenium IDE test?', 'codecept.html', $argv, 0);
    $contents = file_get_contents($file);    
    
    $commands_and_args = parseIntoCommands($contents);
    
    $final = array_map(function($info){                
        return convertInfoArray($info);
    }, $commands_and_args);
    
    \Pulsestorm\Pestle\Library\output(implode("\n", $final));
}
}
namespace Pulsestorm\Magento2\Cli\Convert_System_Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Short Description
* Long
* Description
* @command convert_system_xml
*/
function pestle_cli($argv)
{
    $xml = \Pulsestorm\Pestle\Library\input('Which system.xml?', 'app/code/core/Mage/Core/etc/system.xml');
    $xml = simplexml_load_file($xml);
    $xml_new = simplexml_load_string('<config><system></system></config>');
    $sort = 1000;
    foreach($xml->tabs->children() as $tab)
    {
        $new_tab = $xml_new->system->addChild('tab');
        $new_tab->addAttribute('id',$tab->getName());
        $new_tab->addAttribute('translate',(string)$tab['translate']);
        $new_tab->addAttribute('sortOrder',$sort);
        $sort+=10;
        
        $new_tab->addChild('label', (string) $tab->label);
        // output($tab->getName());
    }
    
    foreach($xml->sections->children() as $section)
    {
        $new_section = $xml_new->system->addChild('section');
        $new_section->addAttribute('id',$section->getName());
        $new_section->addAttribute('translate',(string)$section['translate']);
        $new_section->addAttribute('type',(string)$section->frontend_type);
        $new_section->addAttribute('sortOrder',$sort);
        $sort += 10;
        $new_section->addAttribute('showInDefault',(string)$section->show_in_default);
        $new_section->addAttribute('showInWebsite',(string)$section->show_in_website);
        $new_section->addAttribute('showInStore',(string)$section->show_in_store);
        
        $new_section->addChild('label', (string)$section->label);
        $new_section->addChild('tab', (string)$section->tab);
        $new_section->addChild('resource', 'XXXX');
        
        //id="advanced" translate="label" type="text" sortOrder="910" showInDefault="1" showInWebsite="1" showInStore="1"
        
        // output($section->getName());
        foreach($section->groups->children() as $group)
        {
            $new_group = $new_section->addChild('group');
            $new_group->addAttribute('id',$group->getName());
            $new_group->addAttribute('translate',(string)$group['translate']);
            $new_group->addAttribute('type',(string)$group->frontend_type);
            $new_group->addAttribute('sortOrder',$sort);
            $sort += 10;
            $new_group->addAttribute('showInDefault',(string)$group->show_in_default);
            $new_group->addAttribute('showInWebsite',(string)$group->show_in_website);
            $new_group->addAttribute('showInStore',(string)$group->show_in_store);

            $new_group->addChild('label', (string)$group->label);
            $new_group->addChild('frontend_model', 'XXXX');
                    
            // output($group->getName());
            foreach($group->fields->children() as $field)
            {
                //id="email" translate="label" type="text" sortOrder="2" showInDefault="1" showInWebsite="1" showInStore="1"            
                $new_field = $new_group->addChild('field');
                $new_field->addAttribute('id',$field->getName());
                $new_field->addAttribute('translate',(string)$field['translate']);
                $new_field->addAttribute('type',(string)$field->frontend_type);
                $new_field->addAttribute('sortOrder',$sort);
                $sort += 10;
                $new_field->addAttribute('showInDefault',(string)$field->show_in_default);
                $new_field->addAttribute('showInWebsite',(string)$field->show_in_website);
                $new_field->addAttribute('showInStore',(string)$field->show_in_store);
                foreach($field->children() as $field_child)
                {
                    if(in_array($field_child->getName(), ['id','translate','type','sort_order','show_in_default','show_in_website','show_in_store','frontend_type'])) 
                    { 
                        continue; 
                    }
                    $new_field->addChild($field_child->getName(), (string) $field_child);
                }                            
                // output($field->getName());
            }
        }
    }
    
    echo $xml_new->asXml(),"\n";
    \Pulsestorm\Pestle\Library\output("Done");
}
}
namespace Pulsestorm\Magento2\Cli\Dev_Import{
use function Pulsestorm\Pestle\Importer\pestle_import;

/**
* This is a test
* @command dev_import
*/
function pestle_cli($argv)
{
    \Pulsestorm\Pestle\Library\output("test");
}}
namespace Pulsestorm\Magento2\Cli\Dev_Namespace{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* This is a test
* @command dev_namespace
*/
function pestle_cli($argv)
{
    $file = \Pulsestorm\Pestle\Library\inputOrIndex(
        "File?", '', $argv, 0);
        
    $contents = file_get_contents($file);        
    preg_match('%namespace (.+?);%',$contents,$matches);
    $namespace = $matches[1];
    
    $namespace = strToLower($namespace);        
    $path      = 'modules/' . str_replace('\\','/', $namespace);    
    $full_name = $path . '/module.php';   
    if(!is_dir($path))
    { 
        mkdir($path, 0755, true);
    }
    copy($file, $full_name);
    rename($file, $file . '.moved');
}}
namespace Pulsestorm\Magento2\Cli\Export_Module{
error_reporting(E_ALL);
use function Pulsestorm\Pestle\Importer\pestle_import;




function getNextTConstantEncapsedStringFromTokenArray($tokens, $index)
{    
    $tokens = array_slice($tokens, $index+1);  
    foreach($tokens as $token)
    {   
        if($token->token_name === 'T_CONSTANT_ENCAPSED_STRING')
        {
            return $token;
        }
    }
}

function isTokenFunction($token, $function, $tokens, $index)
{
    if(!isset($tokens[$index+1])) { return false; }
    if($tokens[$index+1]->token_name === 'T_WHITESPACE')
    {
        $index++;
        return isTokenFunction($tokens[$index], $function, $tokens, $index);
    }

    return  !($token->token_value !== $function || 
            $tokens[$index+1]->token_value !== '(');
}

function isTokenPestleImport($token, $tokens, $index)
{
    return isTokenFunction($token, 'pestle_import', $tokens, $index);
}

function removeWhitespaceFromTokens($tokens)
{
    $tokens = array_filter($tokens, function($token){
        return $token->token_name !== 'T_WHITESPACE';
    });
    return $tokens;
}

function getFunctionNamesFromPestleImports($tokens)
{
    $tokens = removeWhitespaceFromTokens($tokens);
    $tokens = array_values($tokens);
    $imports=[];
    foreach($tokens as $index=>$token)
    {    
        if(!isTokenPestleImport($token, $tokens, $index)){ continue;}
        $imports[] = getNextTConstantEncapsedStringFromTokenArray($tokens, $index);
    }    
    
    $importedNames = array_map(function($token){
        return trim($token->token_value,"'\"");
    }, $imports);
    
    $return = [];
    foreach($importedNames as $name)
    {
        $parts = explode('\\', $name);
        $return[$name] = array_pop($parts);
    }
    
    return $return;
}

function getRealNamespaceFromImportedFunction($function)
{
    $path = \Pulsestorm\Pestle\Importer\getPathFromFunctionName($function);
    $tokens = \Pulsestorm\Cli\Token_Parse\pestle_token_get_all(file_get_contents($path));
    
    $flag = false;
    $tokensNamespace = [];
    foreach($tokens as $token)
    {
        if($token->token_value === 'namespace')
        {
            $flag = true;
            continue;
        }
        if(!$flag) { continue; }
        if($token->token_value === ';'){break;};
        $tokensNamespace[] = $token;
    }
    $asString = trim(
        implode('',
            array_map(function($token){
                return $token->token_value;
            }, $tokensNamespace)
        )
    );
    return trim($asString,'\\');
}

function replaceFunctionCallWithFunctionCallInTokens($current, $new, $tokens)
{
    foreach($tokens as $index=>$token)
    {
        if($tokens[$index]->token_value !== $current) {continue;}
        if(!isTokenFunction($token, $current, $tokens, $index)){continue;}
        $token->token_value = '\\' . getRealNamespaceFromImportedFunction($new) . '\\' . $current;
        $tokens[$index] = $token;
    }
    
    return $tokens;
}

function changeToBlockedNamespace($string)
{
    $string = preg_replace('%(^.)%m',"\t$1",$string);
    $string = preg_replace('%(namespace.+?);%',"$1{",$string);
    $string = str_replace("\t<?php",'',$string);
    $string = str_replace("\tnamespace",'namespace',$string);
    $string .= "\n" . '}';    
    return $string;
}

function getTokensAsString($tokens)
{
    $values = array_map(function($token){
        return $token->token_value;
    }, $tokens);
    
    $string = implode('',$values);
    // $string = changeToBlockedNamespace($string);

    return $string;
}

function replaceNamespacedFunction($tokens)
{
    $function_names = getFunctionNamesFromPestleImports($tokens);
    foreach($function_names as $full=>$short)
    {
        $tokens = replaceFunctionCallWithFunctionCallInTokens(
            $short, $full, $tokens);
    }
    return $tokens;
}

function removePestleImports($tokens)
{
    $tokensCleaned = [];
    $flag = true;
    foreach($tokens as $index=>$token)
    {
        if(isTokenPestleImport($token, $tokens, $index))
        {
            $flag = false;
        }
        
        if($flag)
        {
            $tokensCleaned[] = $token;
        }
        else
        {
            if($token->token_value === ';')
            {
                $flag = true;
            }
        }
    }
    return $tokensCleaned;
}

function turnIntoBlockedNamespace($tokens)
{
    $flag = false;
    foreach($tokens as $index=>$token)
    {
        if($token->token_value === 'namespace')
        {
            $flag = true;
        }        
        if(!$flag) {continue;}        
        if($token->token_value !== ';'){continue;}
        $token->token_value = '{';
        $flag = false;
    }
    $tokens[] = (object) [
        'token_value'=>'}',
        'token_name'=>'T_SINGLE_CHAR'
    ];
    return $tokens;
}

function removePhpTag($tokens)
{
    $tokens = array_filter($tokens, function($token){
        return $token->token_name !== 'T_OPEN_TAG';
    });
    return array_values($tokens);
}

function getFilesFromArguments($arguments)
{
    global $argv;
    array_shift($argv);
    array_shift($argv);
    if(count($argv) === count($arguments))
    {
        $files = [$arguments['module_file']];
    }
    else
    {
        $files = $argv;
    }
    
    $files = array_filter($files, function($file){
        return 
            strpos($file, 'pulsestorm/pestle/importer/module.php') === false &&
            strpos($file, 'pulsestorm/pestle/runner/module.php') === false ;
    });
    return $files;
}

/**
* This is a test
* @command export_module
* @argument module_file Which file?
*/
function pestle_cli($arguments)
{    
    $files = getFilesFromArguments($arguments);
    foreach($files as $file)
    {
        $tokens = \Pulsestorm\Cli\Token_Parse\pestle_token_get_all(file_get_contents($file));    
        $tokens = replaceNamespacedFunction($tokens);
        $tokens = removePestleImports($tokens);
        $tokens = removePhpTag($tokens);
        $tokens = turnIntoBlockedNamespace($tokens);
        //collect names of all functions    
        $string = getTokensAsString($tokens);    
        // output("##PROCESSING: $file");
        \Pulsestorm\Pestle\Library\output($string);
        // output("##DONE PROCESSING: $file");        
    }
}}
namespace Pulsestorm\Magento2\Cli\Extract_Mage2_System_Xml_Paths{
use function Pulsestorm\Pestle\Importer\pestle_import;




/**
* Generates Mage2 config.xml
* Extracts configuration path's from a Magento 2 module's
* system.xml file, and then generates a config.xml file
* for the creation of default values
*
* @command extract_mage2_system_xml_paths
*/
function pestle_cli($argv)
{
    $paths = $argv;
    if(count($argv) === 0)
    {
        $paths = [\Pulsestorm\Pestle\Library\input("Which system.xml?", './app/code/Magento/Theme/etc/adminhtml/system.xml')];
    }

    foreach($paths as $path)
    {
        $tree = \Pulsestorm\Magento2\Cli\Library\getSimpleTreeFromSystemXmlFile($path);
    }
    
    $xml = simplexml_load_string(
    '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd"><default></default></config>');
    foreach($tree as $section=>$groups)
    {
        $section = $xml->default->addChild($section);
        foreach($groups as $group=>$fields)
        {
            $group   = $section->addChild($group);
            foreach($fields as $field)
            {
                $group->addChild($field, 'DEFAULTVALUE');
            }
        }
    }
    echo $xml->asXml();
}
}
namespace Pulsestorm\Magento2\Cli\Extract_Session{
use function Pulsestorm\Pestle\Importer\pestle_import;



class Session
{
    public static function unserialize($session_data) {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserialize_php($session_data);
                break;
            case "php_binary":
                return self::unserialize_phpbinary($session_data);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }

    private static function unserialize_php($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

    private static function unserialize_phpbinary($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}

/**
* @command extract_session
*/
function pestle_cli($argv)
{
    $contents = file_get_contents($argv[0]);
    // echo $contents;
    
    $array    = Session::unserialize($contents);    
    var_dump($array);
    \Pulsestorm\Pestle\Library\output("Foo");
}}
namespace Pulsestorm\Magento2\Cli\Fix_Direct_Om{
use function Pulsestorm\Pestle\Importer\pestle_import;










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
        $files = array_merge($files, \Pulsestorm\Phpdotnet\glob_recursive($folder . '/*.' . $extension));
    }
    return $files;
}

function extractArguments($tokens, $index)
{
    $c = $index;
    $arguments = [];
    while(isset($tokens[$c]))
    {
        $token = $tokens[$c];
        $arguments[] = $token;
        
        if($token->token_value === ')')
        {
            break;
        }
        $c++;
    }
    
    $arguments = array_filter($arguments, function($item){
        return $item->token_value !== '(' && $item->token_value !== ')';
    });
    return array_values($arguments);
}

function reportOnMethod($token, $result)
{ 
    $result->methodCalled = $token->token_value;
    return $result;
}

function stripQuotes($string)
{
    $string   = str_replace("'",'',$string);
    $string   = str_replace('"','',$string);
    return $string;
}

function getNewPropNameFromClass($class, $tokens, $c=0)
{    
    $class   = stripQuotes($class);    
    $prop    = \Pulsestorm\Magento2\Cli\Library\getVariableNameFromNamespacedClass($class);
    $prop    = str_replace('$','',$prop);

//     $parts   = explode('\\',$class);
//     $prop    = implode('',$parts);
//     $prop[0] = strToLower($prop[0]);

    if($c > 0)
    {
        $prop .= $c;
    }    
    
    $matches =  array_filter($tokens, function($item) use ($prop){
                    return $item->token_value === $prop;
                });
                              
    if(count($matches) > 0)
    {
        $c++;
        return getNewPropNameFromClass($class, $tokens, $c);
    }                

    return $prop;
}

function reportOnObjectManagerCall($tokens, $index)
{
    $result = new \stdClass;
    $result->methodCalled   = '';
    $result->arguments      = [];
    $result->class          = '';
    $result->newPropName    = '';
    $result->token_position = $index;
    $result->previous_token = $tokens[$index-1];
    
    $c = $index+1;
    $next_token = $tokens[$c];
    $result = reportOnMethod($next_token, $result);
    $arguments = extractArguments($tokens, $c+1);
    if(count($arguments) === 0)
    {
        \Pulsestorm\Pestle\Library\output("        NO ARGUMENTS");
        return $result;
    }
    $first = array_shift($arguments);
    if($first)
    {
        $result->class = $first->token_value;        
    }
    
    if(count($arguments) > 0)
    {
        $result->arguments = $arguments;
    }
    else
    {
        $result->newPropName = getNewPropNameFromClass($result->class, $tokens);
    }
    return $result;
}

function warnFiles($file)
{
    $types = ['Proxy', 'Factory', 'dev/test','Interceptor', 'Test.php'];
    foreach($types as $type)
    {
        if(strpos($file, $type))
        {
            \Pulsestorm\Pestle\Library\output("    WARNING: Looks like a {$type}");
        }
    }

}

function processToken($tokens, $token, $c)
{
    $result = false;
    if($c > 0)
    {
        $previous_token = $tokens[$c-1];
    }
    if($token->token_name === 'T_OBJECT_OPERATOR' && $previous_token->token_value == '_objectManager')
    {
        $result = reportOnObjectManagerCall($tokens, $c);                
    }
    return $result;
}

function tokensFilterWhitespace($tokens)
{
    foreach($tokens as $index=>$token)
    {
        $token->originalIndex = $index;
    }
    
    $tokens = array_filter($tokens, function($token){
        return $token->token_name !== 'T_WHITESPACE';
    });
    $tokens = array_values($tokens); //reindexes

    return $tokens;
}

function processFile($file, $tokens_all, $tokens)
{    
    $c=0;        
    $results = [];        
    foreach($tokens as $token)
    {           
        $item = processToken($tokens, $token, $c);            
        if($item)
        {
            $results[$file][] = $item;
        }
        $c++;
    }
    return $results;
}

function outputResults($results)
{
    foreach($results as $file=>$array)
    {
        \Pulsestorm\Pestle\Library\output("In $file");
        foreach($array as $result)
        {
            \Pulsestorm\Pestle\Library\output("    Found {$result->previous_token->token_value} on line {$result->previous_token->token_line}");                
            \Pulsestorm\Pestle\Library\output("        METHOD: {$result->methodCalled}");
            \Pulsestorm\Pestle\Library\output("        CLASS: {$result->class}");
            \Pulsestorm\Pestle\Library\output("        EXTRA ARGUMENTS: " . count($result->arguments));
            \Pulsestorm\Pestle\Library\output("        NEW PROP: " . $result->newPropName);                        
        }
    }        

}

function validateResults($results)
{
    foreach($results as $file=>$array)
    {
        $contents = file_get_contents($file);
        if(strpos($contents, 'function __construct') === false)
        {
            \Pulsestorm\Pestle\Library\output("No __construct in {$file}, I don't know what to do " . 
                    "with that, bailing");
            exit;
        }
        
        foreach($array as $result)
        {
            if($result->class[0] === '$')
            {
                \Pulsestorm\Pestle\Library\output( "{$result->class} looks like a variable, not a " .
                        "class string.  I don't know what to do with " .
                        "that, bailing.");
                exit;
            }
            
            if(!in_array($result->methodCalled, ['create','get']))
            {
                \Pulsestorm\Pestle\Library\output( "Called {$result->methodCalled}, I don't know what " . 
                        "to do with that, bailing");
                exit;
            }
            
            if(count($result->arguments) > 0)
            {
                \Pulsestorm\Pestle\Library\output( "Found extra \$arguments, not sure what to do with " . 
                        "that, bailing ");
                exit;
            }            
        }
    }
}

function replaceObjectManager($file, $array, $tokens_all)
{
    $indexAndPropNames = array_map(function($result){
        $item           = new \stdClass;
        $item->index    = $result->previous_token->originalIndex;
        $item->propName = $result->newPropName;
        $item->method   = $result->methodCalled;
        return $item;
    }, $array);

    $tokensNew = [];   
    $state     = TOKEN_BASELINE;
    $propName  = '';
    $method    = '';
    foreach($tokens_all as $index=>$token)
    {
        if($state === TOKEN_BASELINE)
        {
            $thing = array_filter($indexAndPropNames, function($item) use ($index){
                return $item->index === $index;
            });
            $thing = array_shift($thing);
        
            //if we couldn't extract anything, add the token
            if(!$thing) 
            {
                $tokensNew[] = $token;
                continue;
            }
            $state = TOKEN_REMOVING_OM;
            $propName = $thing->propName;
            $method   = $thing->method;
        }
        if($state === TOKEN_REMOVING_OM && $token->token_value === ')')
        {
            $tmp = new \stdClass;
            $state = TOKEN_BASELINE;
            $tmp->token_value = $propName;
            if($method === 'create')
            {
                $tmp->token_value .= '->create()';
            }
            $tokensNew[] = $tmp;
        }
    }
    $tokenValues = array_map(function($token){
        return $token->token_value;
    }, $tokensNew);
    \Pulsestorm\Pestle\Library\writeStringToFile($file,implode('',$tokenValues));
}

function performInjection($file, $array)
{
    $alreadyInjected = [];
    foreach($array as $result)
    {
        $class = stripQuotes($result->class);            
        if(in_array($class, $alreadyInjected)) { continue; }            
        \Pulsestorm\Magento2\Cli\Generate\Di\injectDependencyArgumentIntoFile(
            $class, $file, '$' . $result->newPropName);
        $alreadyInjected[] = $class;                                    
    }        
}

function prepareResultsIfCreateFactoryIsNeeded($array)
{
    $new = [];
    foreach($array as $result)
    {
        $tmp = clone $result;
        if($result->methodCalled === 'create')
        {
            $tmp->newPropName .= 'Factory';
            $tmp->class       .= 'Factory';
        }
        $new[] = $tmp;
    }
    return $new;
}

function performInjectionAndReplaceObjectManager($results, $tokens_all)
{
    foreach($results as $file=>$array)
    {        
        $array = prepareResultsIfCreateFactoryIsNeeded($array);
        replaceObjectManager($file, $array, $tokens_all);                             
        performInjection($file, $array);        
    }
}

function getBaseMagentoDirFromFile($dir)
{
    $dir    = realpath($dir);
    $split  = '/app/code/';
    $parts  = explode($split, $dir);
    if(count($parts) === 1)
    {
        $split = '/vendor/';
        $parts = explode($split, $dir);   
    }
    return array_shift($parts) . rtrim($split,'/');
}

function extractFullClassExtends($tokens)
{
    $c=0;
    $flag = false;
    $all = [];
    foreach($tokens as $token)
    {
        if($token->token_name === 'T_EXTENDS')
        {
            $flag = true;
            continue;
        }
        
        if($flag && !in_array($token->token_name, ['T_STRING','T_NS_SEPARATOR']))
        {
            break;
        }
        
        if($flag)
        {
            $all[] = $token;
        }        
        $c++;        
    }
    
    return implode('',array_map(function($item){
        return $item->token_value;
    }, $all));        
}

function getBaseConstructor($file, $tokens)
{
    $base       = getBaseMagentoDirFromFile($file);
    $class      = extractFullClassExtends($tokens);

    $base_file  = $base . str_replace('\\','/',$class) . '.php';
    
    $base_contents = file_get_contents($base_file);
    $function   = \Pulsestorm\Cli\Token_Parse\getFunctionFromCode($base_contents, '__construct');
}

/**
* Test Command
* argument foobar @callback exampleOfACallback
* @command fix_direct_om
* @argument folder Folder to scan
* @argument extensions File extensions? [php, phtml]
*/
function pestle_cli($arguments, $options)
{
    \Pulsestorm\Pestle\Library\output("TODO: When there's not an existing __construct");
    \Pulsestorm\Pestle\Library\output("TODO: When file doesn't exist");
    \Pulsestorm\Pestle\Library\output("TODO: Flag to ask if you want to replace a file");
    \Pulsestorm\Pestle\Library\output("TODO: Prop Name \Foo\Bar\Splat\Baz\Boo ->barBazBoo");    
    
    \Pulsestorm\Magento2\Cli\Generate\Di\defineStates(); 
    define('TOKEN_BASELINE',    0);
    define('TOKEN_REMOVING_OM', 1);
    
    extract($arguments);

    $files = getFiles($folder, $extensions);   
    foreach($files as $file)
    {                
        // output('.');        
        if(preg_match('%.bak.php%', $file))
        {
            // output("{$file} looks like a backup, skipping.");
            continue;
        }
        
        // output($file);
        $tokensAll  = \Pulsestorm\Cli\Token_Parse\pestle_token_get_all(file_get_contents($file));
        $tokens     = tokensFilterWhitespace($tokensAll);                
        
        // getBaseConstructor($file, $tokens);
        
        
        $results    = processFile($file, $tokensAll, $tokens);        
        outputResults($results);  
        
        //do the fixing
        validateResults($results);
        #performInjectionAndReplaceObjectManager($results, $tokensAll);
    }
    \Pulsestorm\Pestle\Library\output("Done");
}
}
namespace Pulsestorm\Magento2\Cli\Fix_Permissions_Modphp{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* "Fixes" permissions for development boxes
* running mod_php by setting things to 777.
* I am a traitor 
* @command fix_permissions_modphp
*/
function pestle_cli($argv)
{
    $base = \Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir();
    $cmds = [
        "find $base/pub/static -exec chmod 777 '{}' +",
	    "find $base/var/ -exec chmod 777 '{}' +",
    ];
    
    foreach($cmds as $cmd)
    {
        $results = `$cmd`;
        if($results)
        {
            \Pulsestorm\Pestle\Library\output($results);
        }
    }
}
}
namespace Pulsestorm\Magento2\Cli\Foo_Bar{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command foo_bar
*/
function pestle_cli($argv)
{
    \Pulsestorm\Pestle\Library\output("Hello Sailor");
}
}
namespace Pulsestorm\Magento2\Cli\Format_Xml_String{
use DomDocument;

/**
* @command library
*/
function pestle_cli($argv)
{
}

function format_xml($xml_string)
{
    $dom = new DomDocument();
    $dom->preserveWhitespace = false;			
    $dom->loadXml($xml_string);
    $dom->formatOutput		= true;			
    $output = $dom->saveXml();
    
    return $output;
}
}
namespace Pulsestorm\Magento2\Cli\Generate\Acl{
use function Pulsestorm\Pestle\Importer\pestle_import;







/**
* One Line Description
*
* @command generate_acl
* @argument module_name Which Module? [Pulsestorm_HelloWorld]
* @argument rule_ids Rule IDs? [<$module_name$>::top,<$module_name$>::config,]
*/
function pestle_cli($argv)
{
    extract($argv);    
    $rule_ids = explode(',', $rule_ids);
    $rule_ids = array_filter($rule_ids);
    
    $path = \Pulsestorm\Magento2\Cli\Library\getBaseModuleDir($module_name) . '/etc/acl.xml';
    if(!file_exists($path))
    {
        $xml = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('acl'));
        \Pulsestorm\Pestle\Library\writeStringToFile($path, $xml->asXml());
    }    
    $xml = simplexml_load_file($path);
    
    $xpath = 'acl/resources/resource[@id=Magento_Backend::admin]';
    
    foreach($rule_ids as $id)
    {        
        $id = trim($id);
        $xpath .= '/resource[@id='.$id.',@title=TITLE HERE FOR]';
    }
    \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml,$xpath);
    
    \Pulsestorm\Pestle\Library\writeStringToFile($path, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
    \Pulsestorm\Pestle\Library\output("Created $path");
}
}
namespace Pulsestorm\Magento2\Cli\Generate\Command{
use function Pulsestorm\Pestle\Importer\pestle_import;










function createPhpClass($module_dir, $namespace, $module_name, $command_name)
{
    $class_file_string = \Pulsestorm\Cli\Code_Generation\templateCommandClass($namespace, $module_name, $command_name);

    if(!is_dir($module_dir . '/Command'))
    {
        mkdir($module_dir . '/Command');
    }
    
    \Pulsestorm\Pestle\Library\writeStringToFile($module_dir . '/Command/'.$command_name.'.php', $class_file_string);
}

function createDiIfNeeded($module_dir)
{
    $path_di_xml = $module_dir . '/etc/di.xml';
    
    if(!file_exists($path_di_xml))
    {
        $xml_di = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('di'));
        \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml_di, 'type[@name=Magento\Framework\Console\CommandList]');
        \Pulsestorm\Pestle\Library\writeStringToFile($path_di_xml, \Pulsestorm\Xml_Library\formatXmlString($xml_di->asXml()));       
    }
    return $path_di_xml;
}

/**
* Generates bin/magento command files
* This command generates the necessary files and configuration 
* for a new command for Magento 2's bin/magento command line program.
*
*   pestle.phar Pulsestorm_Generate Example
* 
* Creates
* app/code/Pulsestorm/Generate/Command/Example.php
* app/code/Pulsestorm/Generate/etc/di.xml
*
* @command generate_command
* @argument module_name In which module? [Pulsestorm_Helloworld]
* @argument command_name Command Name? [Testbed]
*/
function pestle_cli($argv)
{
    $module_info        = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($argv['module_name']);    
    $namespace          = $module_info->vendor;
    $module_name        = $module_info->name;
    $module_shortname   = $module_info->short_name;
    $module_dir         = $module_info->folder;    
    $command_name       = $argv['command_name'];
    // $command_name       = input("Command Name?", 'Testbed');    
        
    \Pulsestorm\Pestle\Library\output($module_dir);    
            
    createPhpClass($module_dir, $namespace, $module_shortname, $command_name);
    
    $path_di_xml = createDiIfNeeded($module_dir);
    
    $xml_di = simplexml_load_file($path_di_xml);
    
    //get commandlist node
    $nodes = $xml_di->xpath('/config/type[@name="Magento\Framework\Console\CommandList"]');
    $xml_type_commandlist = array_shift($nodes);
    if(!$xml_type_commandlist)
    {
        throw new Exception("Could not find CommandList node");
    }
    
    $argument = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml_type_commandlist, 
        '/arguments/argument[@name=commands,@xsi:type=array]');

    $full_class = $namespace.'\\'.$module_shortname.'\\Command\\' . $command_name;    
    $item_name  = str_replace('\\', '_', strToLower($full_class));
    $item       = $argument->addChild('item', $full_class);
    $item->addAttribute('name', $item_name);
    $item->addAttribute('xsi:type', 'object', 'http://www.w3.org/2001/XMLSchema-instance');
    
    $xml_di     = \Pulsestorm\Xml_Library\formatXmlString($xml_di->asXml());
        
    \Pulsestorm\Pestle\Library\writeStringToFile($path_di_xml, $xml_di);       
    
}
}
namespace Pulsestorm\Magento2\Cli\Generate\Config_Helper{
use function Pulsestorm\Pestle\Importer\pestle_import;
use Exception;



/**
* Generates a help class for reading Magento's configuration
*
* This command will generate the necessary files and configuration 
* needed for reading Magento 2's configuration values.
* 
* @command generate_config_helper
* @todo needs to be implemented
*/
function pestle_cli($argv)
{
    throw new Exception("Implement Me");
    $template = trim('
<?php
namespace Pulsestorm\Api\Helper;
use Pulsestorm\Api\Controller\V1\Get\Settings;

class Config
{
    const CONFIG_TOP = \'pulsestorm\';
    protected $scopeConfig;
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function get($path=null)
    {
        $config = $this->scopeConfig->getValue($this->getTopLevelName());
        if($config === null)
        {
            return null;
        }
        if(!$path)
        {
            return $config;
        }
        $parts = explode(\'/\',$path);
        
        foreach($parts as $part)
        {
            if(!array_key_exists($part, $config))
            {
                return null;
            }
            $config = $config[$part];
        }
        
        return $config;
    }
    
    protected function getTopLevelName()
    {
        return self::CONFIG_TOP;
    }
}    
');  

    \Pulsestorm\Pestle\Library\output($template);  
}}
namespace Pulsestorm\Magento2\Cli\Generate\Crud\Model{
use function Pulsestorm\Pestle\Importer\pestle_import;








define('BASE_COLLECTION_CLASS'  , '\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection');
define('BASE_RESOURCE_CLASS'    , '\Magento\Framework\Model\ResourceModel\Db\AbstractDb');
define('BASE_MODEL_CLASS'       , '\Magento\Framework\Model\AbstractModel');

function getCollectionClassNameFromModuleInfo($module_info, $model_name)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\Model\ResourceModel\\' . $model_name . '\Collection';
}

function getResourceModelClassNameFromModuleInfo($module_info, $model_name)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\Model\ResourceModel\\' . $model_name;
}

function getModelClassNameFromModuleInfo($module_info, $model_name)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\Model\\' . $model_name;
}

function templateInstallDataFunction()
{
    return "\n" . '    public function install(\Magento\Framework\Setup\ModuleDataSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        //install data here
    }' . "\n";
}

function templateInstallFunction()
{
    return "\n" . '    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        //START: install stuff
        //END:   install stuff
        $installer->endSetup();
    }' . "\n";
}

function templateConstruct($init1=false, $init2=false)
{
    $params = array_filter([$init1, $init2]);
    $params = "'" . implode("','",$params) . "'";
    
    return "\n" . '    protected function _construct()' . "\n" .
    '    {' . "\n" .
    '        $this->_init('.$params.');' . "\n" .
    '    }' . "\n";
}

function templateRepositoryInterfaceAbstractFunction($modelShortInterface)
{
    return "
    public function save({$modelShortInterface} \$page);

    public function getById(\$id);

    public function getList(SearchCriteriaInterface \$criteria);

    public function delete({$modelShortInterface} \$page);

    public function deleteById(\$id);    
";    
}

function templateRepositoryInterfaceUse($longModelInterfaceName)
{
    return "
use {$longModelInterfaceName};
use Magento\Framework\Api\SearchCriteriaInterface;
";
}

function templateComplexInterface($useContents, $methodContents, $interfaceContents)
{
    $interfaceContents = preg_replace(
        '%(namespace.+?;)%',
        '$1' . "\n" . $useContents,
        $interfaceContents);

    $interfaceContents = preg_replace(
        '%\{\s*\}%six',
        '{' . rtrim($methodContents) . "\n" . '}' . "\n",
        $interfaceContents
    );        
    
    return $interfaceContents;
}

function createRepositoryInterfaceContents($module_info, $model_name, $interface)
{
    $modelInterface             = getModelInterfaceShortName($model_name);
    $modelInterfaceLongName     = getModelInterfaceName($module_info, $model_name);
    
    $contents                   = \Pulsestorm\Cli\Code_Generation\templateInterface($interface,[]);   
    $contentsAbstractFunctions  = templateRepositoryInterfaceAbstractFunction($modelInterface);
    $contentsUse                = templateRepositoryInterfaceUse($modelInterfaceLongName);
    
    $contents = templateComplexInterface($contentsUse, $contentsAbstractFunctions, $contents);
    
    return $contents;
}

function getModelRepositoryName($model_name)
{
    return $model_name . 'Repository';    
}

function templateUseFunctions($repositoryInterface, $thingInterface, $classModel, $collectionModel)
{        
    $thingFactory   = $classModel . 'Factory';
    $resourceModel  = $collectionModel . 'Factory';
    // $resourceModel       = 'Pulsestorm\HelloGenerate\Model\ResourceModel\Thing\CollectionFactory';
    
    return "
use {$repositoryInterface};
use {$thingInterface};
use {$thingFactory};
use {$resourceModel};

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchResultsInterfaceFactory;";
}

function templateRepositoryFunctions($modelName)
{
    $modelNameFactory = $modelName . 'Factory';
    $modelInterface   = getModelInterfaceShortName($modelName);
    return '
    protected $objectFactory;
    protected $collectionFactory;
    public function __construct(
        '.$modelNameFactory.' $objectFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory       
    )
    {
        $this->objectFactory        = $objectFactory;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }
    
    public function save('.$modelInterface.' $object)
    {
        try
        {
            $object->save();
        }
        catch(Exception $e)
        {
            throw new CouldNotSaveException($e->getMessage());
        }
        return $object;
    }

    public function getById($id)
    {
        $object = $this->objectFactory->create();
        $object->load($id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__(\'Object with id "%1" does not exist.\', $id));
        }
        return $object;        
    }       

    public function delete('.$modelInterface.' $object)
    {
        try {
            $object->delete();
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;    
    }    

    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }    

    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);  
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : \'eq\';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }  
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? \'ASC\' : \'DESC\'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $objects = [];                                     
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }
        $searchResults->setItems($objects);
        return $searchResults;        
    }';    
}

function createRepository($module_info, $model_name)
{
    $classCollection    = getCollectionClassNameFromModuleInfo($module_info, $model_name);
    $classModel         = getModelClassNameFromModuleInfo($module_info, $model_name);
    $modelInterface     = getModelInterfaceName($module_info, $model_name);
    $repositoryName     = getModelRepositoryName($model_name);
    $repositoryFullName = getModelClassNameFromModuleInfo($module_info, $repositoryName);
    $interface          = getModuleInterfaceName($module_info, $repositoryName, 'Api');    
    $template           = \Pulsestorm\Cli\Code_Generation\createClassTemplate($repositoryFullName, false, '\\' . $interface, true);
    
    $body               = templateRepositoryFunctions($model_name);
    $use                = templateUseFunctions($interface, $modelInterface, $classModel, $classCollection);
    $contents           = $template;
    $contents           = str_replace('<$body$>', $body, $contents);
    $contents           = str_replace('<$use$>' , $use,  $contents);
    
    $path               = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($repositoryFullName);        
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
    
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);    
}

function createRepositoryInterface($module_info, $model_name)
{    
    $repositoryName = getModelRepositoryName($model_name);
    $interface      = getModuleInterfaceName($module_info, $repositoryName, 'Api');
    $path           = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($interface);    
    $contents       = createRepositoryInterfaceContents($module_info, $model_name, $interface);
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
}

function createCollectionClass($module_info, $model_name)
{
    $path                   = $module_info->folder . "/Model/ResourceModel/$model_name/Collection.php";
    $class_collection       = getCollectionClassNameFromModuleInfo($module_info, $model_name);
    $class_model            = getModelClassNameFromModuleInfo($module_info, $model_name);
    $class_resource         = getResourceModelClassNameFromModuleInfo($module_info, $model_name);
            
    $template               = \Pulsestorm\Cli\Code_Generation\createClassTemplate($class_collection, BASE_COLLECTION_CLASS);
    $construct              = templateConstruct($class_model, $class_resource);

    $class_contents         = str_replace('<$body$>', $construct, $template);
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $class_contents);
}

function createResourceModelClass($module_info, $model_name)
{
    $path = $module_info->folder . "/Model/ResourceModel/$model_name.php";
    $db_table               = strToLower($module_info->name . '_' . $model_name);
    $db_id                  = strToLower($db_table) . '_id';
    $class_resource         = getResourceModelClassNameFromModuleInfo($module_info, $model_name);
    $template               = \Pulsestorm\Cli\Code_Generation\createClassTemplate($class_resource, BASE_RESOURCE_CLASS);    
    $construct              = templateConstruct($db_table, $db_id);
    $class_contents         = str_replace('<$body$>', $construct, $template);    
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $class_contents);    
}

function templateGetIdentities()
{
    return "\n" . '    public function getIdentities()
    {
        return [self::CACHE_TAG . \'_\' . $this->getId()];
    }' . "\n";
}

function templateCacheTag($tag_name)
{
    return "\n    const CACHE_TAG = '$tag_name';\n";
}

function getModelInterfaceShortName($model_name)
{
    return $model_name . 'Interface';
}

function createModelClass($module_info, $model_name)
{
    $path = $module_info->folder . "/Model/$model_name.php";
    $cache_tag           = strToLower($module_info->name . '_' . $model_name);
    $class_model         = getModelClassNameFromModuleInfo($module_info, $model_name);
    $class_resource      = getResourceModelClassNameFromModuleInfo($module_info, $model_name);
    $implements          = getModelInterfaceShortName($model_name) . ', \Magento\Framework\DataObject\IdentityInterface';
    $template            = \Pulsestorm\Cli\Code_Generation\createClassTemplate($class_model, BASE_MODEL_CLASS, $implements);    
    $construct           = templateConstruct($class_resource);

    $body                = 
        templateCacheTag($cache_tag)    .      
        $construct                      .
        templateGetIdentities();

    $class_contents      = str_replace('<$body$>', $body, $template);    
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $class_contents);    
}

function getModuleInterfaceName($module_info, $model_name, $type)
{
    return $module_info->vendor . '\\' . $module_info->short_name . 
        '\\' . $type .'\\' . getModelInterfaceShortName($model_name);

}

function getModelInterfaceName($module_info, $model_name)
{
    return getModuleInterfaceName($module_info, $model_name, 'Model');
//     return $module_info->vendor . '\\' . $module_info->short_name . 
//         '\Model\\' . getModelInterfaceShortName($model_name);
}

function createModelInterface($module_info, $model_name)
{
    $interface = getModelInterfaceName($module_info, $model_name);
    $path      = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($interface);
    $contents  = \Pulsestorm\Cli\Code_Generation\templateInterface($interface,[]);    
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
}

function createTableNameFromModuleInfoAndModelName($module_info, $model_name)
{
    return strToLower($module_info->name . '_' . $model_name);
}

function createSchemaClass($module_info, $model_name)
{
    $className  = str_replace('_', '\\', $module_info->name) . 
        '\Setup\InstallSchema';
    $path       = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($className);

    $template   = \Pulsestorm\Cli\Code_Generation\createClassTemplate($className, false, 
        '\Magento\Framework\Setup\InstallSchemaInterface');        
    $contents   = str_replace('<$body$>', templateInstallFunction(), $template);    
    if(!file_exists($path))
    {
        \Pulsestorm\Pestle\Library\output("Creating: " . $path);        
        \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
    }
    else
    {
        \Pulsestorm\Pestle\Library\output("File Already Exists: " . $path);
    }
    
    $table_name = createTableNameFromModuleInfoAndModelName(
        $module_info, $model_name);
    
    $install_code = \Pulsestorm\Cli\Code_Generation\generateInstallSchemaTable($table_name);
    $contents     = file_get_contents($path);
    $end_setup    = '$installer->endSetup();';
    $contents     = str_replace($end_setup, 
        "\n//START table setup\n" .
        $install_code .
        "\n//END   table setup\n" .
        $end_setup, $contents);
        
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
}

function createDataClass($module_info, $model_name)
{
    $className  = str_replace('_', '\\', $module_info->name) . 
        '\Setup\InstallData';
    $path       = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($className);
    $template   = \Pulsestorm\Cli\Code_Generation\createClassTemplate($className, false, 
        '\Magento\Framework\Setup\InstallDataInterface');        
    $contents   = str_replace('<$body$>', templateInstallDataFunction(), $template);        

    if(!file_exists($path))
    {
        \Pulsestorm\Pestle\Library\output("Creating: " . $path);        
        \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
    }
    else
    {
        \Pulsestorm\Pestle\Library\output("Data Installer Already Exists: " . $path);
    }        
}

/**
* One Line Description
*
* @command generate_crud_model
* @argument module_name Which module? [Pulsestorm_HelloGenerate]
* @argument model_name  What model name? [Thing]
*/
function pestle_cli($argv)
{
    $module_name = $argv['module_name'];
    $module_info = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($argv['module_name']);    
    $model_name  = $argv['model_name'];

    createRepositoryInterface($module_info, $model_name);    
    createRepository($module_info, $model_name);
    createModelInterface($module_info, $model_name);
    createCollectionClass($module_info, $model_name);
    createResourceModelClass($module_info, $model_name);
    createModelClass($module_info, $model_name);
    
    
    createSchemaClass($module_info, $model_name);
    createDataClass($module_info, $model_name);


}
}
namespace Pulsestorm\Magento2\Cli\Generate\Di{
use function Pulsestorm\Pestle\Importer\pestle_import;







use stdClass;
function getClassIndent()
{
    return '    ';
}

function arrayContainsConstructToken($tokens)
{
    foreach($tokens as $token)
    {
        if($token->token_name === 'T_STRING' && $token->token_value === '__construct')
        {
            return true;
        }
    }
    return false;
}

function insertConstrctorIntoPhpClassFileTokens($tokens)
{
    $indent = getClassIndent();
    $new_tokens = [];
    $state  = 0;
    $c      = 0;
    foreach($tokens as $token)
    {
        $new_tokens[] = $token;    
        if($state === 0 && $token->token_name === 'T_CLASS')
        {            
            $state = FOUND_CLASS_KEYWORD;
        }
        
        if($state === FOUND_CLASS_KEYWORD && $token->token_value === '{')
        {
            $state = FOUND_OPENING_CLASS_BRACKET;
            $tmp = new stdClass;
            //$tmp->token_value = "\n" . $indent . '#Property Here' . "\n";
            $tmp->token_value = "\n" . $indent  .   
                'public function __construct()' . "\n" .
                $indent . '{' . "\n" . $indent . '}' . "\n";
            
            $new_tokens[] = $tmp;           
        }        
        
        $c++;
    }
    
    return \Pulsestorm\Cli\Token_Parse\pestle_token_get_all(implodeTokensIntoContents($new_tokens));
}

function implodeTokensIntoContents($tokens)
{
    $values = array_map(function($token){
        return $token->token_value;
    }, $tokens);
        
    return implode('', $values);
}

function addCommaIfSpoolBackwardsRevealsConstructorParam($tokens)
{
    $starting_index = count($tokens) - 1;
    for($i=$starting_index;$i>-1;$i--)
    {
        $token = $tokens[$i];
        //got back to opening (, time to return
        if($token->token_value === '(')
        {
            return $tokens;
        }
        
        //found whitespace. Remove, continue backwards
        if($token->token_name === 'T_WHITESPACE')
        {
            continue;
        }
        
        //if we get here, that means there IS another param, slide on 
        //the comma and then return the tokens
        $tmp = new stdClass;
        $tmp->token_value = ',';
        $before = array_slice($tokens, 0,$i+1);
        $after  = array_slice($tokens, $i+1);
        // $new_tokens = $tokens;
        $new_tokens = array_merge($before, [$tmp], $after);
        return $new_tokens; 
    }
    
    return $tokens;
}

function trimWhitespaceFromEndOfTokenArray($tokens)
{
    $starting_index = count($tokens) - 1;
    for($i=$starting_index;$i>-1;$i--)
    {
        if($tokens[$i]->token_name !== 'T_WHITESPACE')
        {
            return $tokens;
        }
        unset($tokens[$i]);
    }
}

function original($argv)
{
    if(count($argv) === 0)
    {
        $argv = [\Pulsestorm\Pestle\Library\input("Which class?", 'Pulsestorm\Helloworld\Helper\Config')];
    }
    $class = array_shift($argv);
    
    \Pulsestorm\Pestle\Library\output("DI Lines");   
    \Pulsestorm\Pestle\Library\output('-----------------------');             
    \Pulsestorm\Pestle\Library\output(implode("\n",\Pulsestorm\Magento2\Cli\Library\getDiLinesFromMage2ClassName($class)));
    \Pulsestorm\Pestle\Library\output('');
}

function defineStates()
{
    define('FOUND_CLASS_KEYWORD'          , 1);
    define('FOUND_OPENING_CLASS_BRACKET'  , 2);
    define('FOUND_CONSTRUCT'              , 3);
    define('FOUND_CONSTRUCT_CLOSE_PAREN'  , 4);
    define('FOUND_CONSTRUCT_OPEN_BRACKET' , 5);
    // define('FOUND_', X);
}

function injectDependencyArgumentIntoFile($class, $file, $propName=false)
{
    $di_lines = (object) \Pulsestorm\Magento2\Cli\Library\getDiLinesFromMage2ClassName($class, $propName);
    $di_lines->parameter = trim(trim($di_lines->parameter,','));        
    
    $indent   = getClassIndent();
    $contents = file_get_contents($file);
    $tokens   = \Pulsestorm\Cli\Token_Parse\pestle_token_get_all($contents);    
    
    $has_constructor = arrayContainsConstructToken($tokens);
    if(!$has_constructor)
    {
        $tokens = insertConstrctorIntoPhpClassFileTokens($tokens);
    }
        
    $state  = 0;
    $c      = 0;
    $new_tokens = [];
    foreach($tokens as $token)
    {
        $new_tokens[] = $token;
        if($state === 0 && $token->token_name === 'T_CLASS')
        {            
            $state = FOUND_CLASS_KEYWORD;
        }
        
        if($state === FOUND_CLASS_KEYWORD && $token->token_value === '{')
        {
            $state = FOUND_OPENING_CLASS_BRACKET;
            $tmp = new stdClass;
            //$tmp->token_value = "\n" . $indent . '#Property Here' . "\n";
            $comment = $indent . '/**' . "\n" .
                $indent . '* Injected Dependency Description' . "\n" .
                $indent . '* ' . "\n" .                                
                $indent . '* @var \\'.$class.'' . "\n" .
                $indent . '*/' . "\n";
                $tmp->token_value = "\n" . $comment .            
                    $indent . $di_lines->property . "\n";
            
            $new_tokens[] = $tmp;           
        }
        
        if($state === FOUND_OPENING_CLASS_BRACKET && $token->token_value === '__construct')
        {
            $state = FOUND_CONSTRUCT;
        }
        
        if($state === FOUND_CONSTRUCT && $token->token_value === ')')
        {
            $state = FOUND_CONSTRUCT_CLOSE_PAREN;
            $tmp = new stdClass;
            $tmp->token_value = "\n" . $indent . $indent . $di_lines->parameter;
            

            $current_token = array_pop($new_tokens);
            $new_tokens   = trimWhitespaceFromEndOfTokenArray($new_tokens);
            $new_tokens   = addCommaIfSpoolBackwardsRevealsConstructorParam(
                $new_tokens);
                
                            
            $new_tokens[] = $tmp;             
            $new_tokens[] = $current_token;             
        }
        
        if($state === FOUND_CONSTRUCT_CLOSE_PAREN && $token->token_value === '{')
        {
            $state = FOUND_CONSTRUCT_OPEN_BRACKET;
            $tmp = new stdClass;
            // $tmp->token_value = "\n" . $indent . '#Property Assignment Here' . "\n";
            $tmp->token_value = "\n" . $indent . $indent . 
                $di_lines->assignment;
            
            $new_tokens[] = $tmp;              
        }

        $c++;
    }
    
    $contents = implodeTokensIntoContents($new_tokens);
    \Pulsestorm\Pestle\Library\output("Injecting $class into $file");
    \Pulsestorm\Pestle\Library\writeStringToFile($file, $contents); 
}

/**
* Injects a dependency into a class constructor
* This command modifies a preexisting class, adding the provided 
* dependency to that class's property list, `__construct` parameters 
* list, and assignment list.
*
*    pestle.phar generate_di app/code/Pulsestorm/Generate/Command/Example.php 'Magento\Catalog\Model\ProductFactory' 
*
* @command generate_di
* @argument file Which PHP class file are we injecting into?
* @argument class Which class to inject? [Magento\Catalog\Model\ProductFactory]
*
*/
function pestle_cli($argv)
{
    defineStates();
    $file = realpath($argv['file']);
    if(!$file)
    {
        exit("Could not find $file.\n");
    }     
    $class = $argv['class'];

    injectDependencyArgumentIntoFile($class, $file);       
}}
namespace Pulsestorm\Magento2\Cli\Generate\Install{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command generate_install
* @argument id_key Identity Key? [magento_2_new]
* @argument umask Default Umask? [000]
* @argument repo Composer Repo [https://repo.magento.com/]
* @argument composer_package Starting Package? [magento/project-community-edition]
* @argument folder Folder? [magento-2-source]
* @argument admin_first_name Admin First Name? [Alan]
* @argument admin_last_name Admin Last Name? [Storm]
* @argument admin_password Admin Password? [password12345]
* @argument admin_email Admin Email? [astorm@alanstorm.com]
* @argument admin_user Admin Username? [astorm@alanstorm.com]
* @argument db_host Database Host? [127.0.0.1]
* @argument db_user Database User? [root]
* @argument db_pass Database Password? [password12345]
* @argument email Admin Email? [astorm@alanstorm.com]
*/
function pestle_cli($argv)
{    
    //$composer_package .= '=2.1.0-rc1';
    extract($argv);
    
    $db_name = preg_replace('%[^a-zA-Z0-9]%','_', $id_key);
    $url     = preg_replace('%[^a-zA-Z0-9]%','-', $id_key) . '.dev';
    $cmds = [];
    $cmds[] = "composer create-project --repository-url=$repo $composer_package $folder";
    $cmds[] = "cd $folder";
    $cmds[] = "echo '$umask' >> magento_umask";
    $cmds[] = "echo \"We're about to ask for your MySQL password so we can create the database\"";
    $cmds[] = "echo 'CREATE DATABASE $db_name' | mysql -uroot -p";

    $cmds[] = "php bin/magento setup:install --admin-email $admin_email --admin-firstname $admin_first_name --admin-lastname $admin_last_name --admin-password $admin_password --admin-user $admin_user --backend-frontname admin --base-url http://$url --db-host 127.0.0.1 --db-name $db_name --db-password $db_pass --db-user $db_user --session-save files --use-rewrites 1 --use-secure 0 -vvv";    
    $cmds[] = 'php bin/magento sampledata:deploy';    
    $cmds[] = 'php bin/magento cache:enable';
    
    array_map(function($cmd){
        \Pulsestorm\Pestle\Library\output($cmd);
    }, $cmds);
}
}
namespace Pulsestorm\Magento2\Cli\Generate\Layout_Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;




/**
* One Line Description
* This command will generate the layout handle XML 
* files needed to add a block to Magento's page 
* layout
*
* @command generate_layout_xml
* @todo implement me please
*/
function pestle_cli($argv)
{
    \Pulsestorm\Pestle\Library\output("Needs to be implemented");
}}
namespace Pulsestorm\Magento2\Cli\Generate\Mage2_Command{
use function Pulsestorm\Pestle\Importer\pestle_import;






/**
* Generates pestle command boiler plate
* This command creates the necessary files 
* for a pestle command
*
*     pestle.phar generate_pestle_command command_name
*
* @command generate_pestle_command
* @argument command_name New Command Name? [foo_bar]
* @argument namespace_module Create in PHP Namespace? [Pulsestorm\Magento2\Cli]
*/
function pestle_cli($argv)
{
    $command_name = $argv['command_name'];
    $namespace = \Pulsestorm\Cli\Code_Generation\createNamespaceFromNamespaceAndCommandName($argv['namespace_module'], $command_name);
            
    $command = '<' . '?php' . "\n" .
        'namespace ' . $namespace . ';'  . "\n" .
        'use function Pulsestorm\Pestle\Importer\pestle_import;'       . "\n" .
        'pestle_import(\'Pulsestorm\Pestle\Library\output\');' . "\n\n" .
        '/**' . "\n" .
        '* One Line Description' . "\n" .
        '*' . "\n" .
        '* @command '.$command_name.'' . "\n" .
        '*/' . "\n" .
        'function pestle_cli($argv)' . "\n" .
        '{' . "\n" .        
        '    output("Hello Sailor");' . "\n" .
        '}' . "\n";
        
    \Pulsestorm\Pestle\Library\output("Creating the following module");        
    \Pulsestorm\Pestle\Library\output($command);
    
    $path_full = \Pulsestorm\Cli\Code_Generation\createPathFromNamespace($namespace);

    if(file_exists($path_full))
    {
        \Pulsestorm\Pestle\Library\output("$path_full already exists, bailing");
        exit;
    }

    \Pulsestorm\Pestle\Library\writeStringToFile($path_full, $command);
    \Pulsestorm\Pestle\Library\output("bbedit $path_full");
    \Pulsestorm\Pestle\Library\output("sublime $path_full");
    \Pulsestorm\Pestle\Library\output("vi $path_full");    
}}
namespace Pulsestorm\Magento2\Cli\Generate\Menu{
use function Pulsestorm\Pestle\Importer\pestle_import;
use stdClass;









function getMenuXmlFiles()
{
    $base = \Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir();
    // $results = `find $base/vendor -name menu.xml`;
    // $results = explode("\n", $results);
    $results = \Pulsestorm\Phpdotnet\glob_recursive("$base/vendor/menu.xml");            
    $results = array_filter($results);    
    return $results;
}

function inputFromArray($string='Please select the item:',$array)
{
    $num = 0;
    $end = '] ';
    $array = array_map(function($value) use (&$num,$end){        
        $num++;
        $value = '[' . $num . $end . $value;        
        return $value;
    }, $array);
    array_unshift($array, $string);
    
    $sentinal = true;
    while($sentinal)
    {        
        $choice = \Pulsestorm\Pestle\Library\input(implode("\n",$array) . "\n");
        $choice = (int) $choice;
        if(array_key_exists($choice, $array))
        {            
            $value    = $array[$choice];
            $parts   = explode($end, $value);
            array_shift($parts);
            $value   = array_shift($parts);
            $sentinal = false;
        }
    }
    return $value;
}

function getMenusWithValue($raw, $value)
{
    //get top level menus
    $parents = [];
    $raw = array_filter($raw, function($item) use (&$parents, $value){
        if(trim($item->parent) === $value)
        {
            $parents[] = $item->title . "\t(" . $item->id . ')';
            return false;
        }
        return true;
    });
    return $parents;
}

function choseMenuFromTop()
{
    $files  = getMenuXmlFiles();    
    $raw    = [];             
    foreach($files as $file)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);    
        libxml_clear_errors();
        libxml_use_internal_errors(false);                                               
        if(!$xml) { continue; }
        foreach($xml->menu->children() as $add)
        {
            $tmp         = new stdClass;
            $tmp->id     = (string) $add['id'];
            $tmp->parent = (string) $add['parent'];
            $tmp->title  = (string) $add['title'];
            $raw[]       = $tmp;
        }
    }
    
    $parents = getMenusWithValue($raw, '');
        
    $value       = parseIdentifierFromLabel(
                    inputFromArray("Select Parent Menu: ", $parents, 1));
    $continue    = \Pulsestorm\Pestle\Library\input("Use [$value] as parent? (Y/N)",'N');
    if(strToLower($continue) === 'y')
    {
        return $value;
    }
        
    $sections    = getMenusWithValue($raw, $value); 
    $value       = parseIdentifierFromLabel(inputFromArray("Select Parent Menu: ", $sections, 1));    
    return $value;
}

function parseIdentifierFromLabel($string)
{
    $parts = explode("\t", $string);
    $id    = array_pop($parts);
    return trim($id, '()');
}

function selectParentMenu($arguments, $index)
{
    if(array_key_exists($index, $arguments))
    {
        return $arguments[$index];
    }
        
    $parent     = '';
    $continue   = \Pulsestorm\Pestle\Library\input('Is this a new top level menu? (Y/N)','N');
    if(strToLower($continue) === 'n')
    {
        $parent = choseMenuFromTop();
    }
    return $parent;
}

function loadOrCreateMenuXml($path)
{
    if(!file_exists($path))
    {
        $xml    = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('menu'));
        \Pulsestorm\Pestle\Library\writeStringToFile($path, $xml->asXml());
    }
    $xml    = simplexml_load_file($path);
    return $xml;
}

function addAttributesToXml($argv, $xml)
{
    extract($argv);

    $add    = $xml->menu->addChild('add');    
    $add->addAttribute('id'              , $id);
    $add->addAttribute('resource'        , $resource);        
    $add->addAttribute('title'           , $title); 
    $add->addAttribute('action'          , $action);
    $add->addAttribute('module'          , $module_name);         
    $add->addAttribute('sortOrder'       , $sortOrder);             
    if($parent)
    {
        $parts   = explode('::', $parent);
        $depends = array_shift($parts);
        $add->addAttribute('parent'          , $parent); 
        $add->addAttribute('dependsOnModule' , $depends); 
    }
    return $xml;
}
/**
* One Line Description
*
* @command generate_menu
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
    extract($argv);
        
    $path = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($module_name)->folder . '/etc/adminhtml/menu.xml';
    $xml  = loadOrCreateMenuXml($path);
    $xml  = addAttributesToXml($argv, $xml);
             
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $xml->asXml());         
    \Pulsestorm\Pestle\Library\output("Writing: $path");
    \Pulsestorm\Pestle\Library\output("Done.");
}
}
namespace Pulsestorm\Magento2\Cli\Generate\Module{
use function Pulsestorm\Pestle\Importer\pestle_import;








/**
* Generates new module XML, adds to file system
* This command generates the necessary files and configuration
* to add a new module to a Magento 2 system.
*
*    pestle.phar Pulsestorm TestingCreator 0.0.1
*
* @argument namespace Vendor Namespace? [Pulsestorm]
* @argument name Module Name? [Testbed]
* @argument version Version? [0.0.1]
* @command generate_module
*/
function pestle_cli($argv)
{
    $namespace = $argv['namespace'];
    $name      = $argv['name'];
    $version   = $argv['version'];
    
    $full_module_name = implode('_', [$namespace, $name]);    
    
    $config = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('module'));
    $module = $config->addChild('module');
    $module->addAttribute('name'         , $full_module_name);
    $module->addAttribute('setup_version', $version);
    $xml = $config->asXml();
    
    $base_dir   = \Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir();
    $module_dir = implode('/',[$base_dir, 'app/code', $namespace, $name]);    
    $etc_dir    = $module_dir . '/etc';
    $reg_path   = $module_dir . '/registration.php';
    
    if(is_dir($etc_dir))
    {
        \Pulsestorm\Pestle\Library\output("Module directory [$etc_dir] already exists, bailing");
        return;
    }
    
    mkdir($etc_dir, 0777, $etc_dir);
    \Pulsestorm\Pestle\Library\writeStringToFile($etc_dir . '/module.xml', $xml);
    \Pulsestorm\Pestle\Library\output("Created: " . $etc_dir . '/module.xml');
    
    $register = \Pulsestorm\Cli\Code_Generation\templateRegistrationPhp($full_module_name);    
    \Pulsestorm\Pestle\Library\writeStringToFile($reg_path, $register);
    \Pulsestorm\Pestle\Library\output("Created: " . $reg_path);    
}}
namespace Pulsestorm\Magento2\Cli\Generate\Observer{
use function Pulsestorm\Pestle\Importer\pestle_import;






/**
* Generates Magento 2 Observer
* This command generates the necessary files and configuration to add 
* an event observer to a Magento 2 system.
*
*    pestle.phar generate_observer Pulsestorm_Generate controller_action_predispatch pulsestorm_generate_listener3 'Pulsestorm\Generate\Model\Observer3'
*
* @command generate_observer
* @argument module Full Module Name? [Pulsestorm_Generate]
* @argument event_name Event Name? [controller_action_predispatch]
* @argument observer_name Observer Name? [<$module$>_listener]
* @argument model_name Class Name? [<$module$>\Model\Observer]
*/
function pestle_cli($argv)
{
    $module         = $argv['module'];
    $event_name     = $argv['event_name'];
    $observer_name  = $argv['observer_name'];
    $model_name     = $argv['model_name'];
    $method_name    = 'execute';

    $path_xml_event = \Pulsestorm\Magento2\Cli\Library\initilizeModuleConfig(
        $module, 
        'events.xml', 
        'urn:magento:framework:Event/etc/events.xsd'
    );
                    
    $xml = simplexml_load_file($path_xml_event);
    $nodes = $xml->xpath('//event[@name="' . $event_name . '"]');
    $node  = array_shift($nodes);
    $event = $node;
    if(!$node)
    {
        $event = $node ? $node : $xml->addChild('event');
        $event->addAttribute('name', $event_name);    
    }
    $observer = $event->addChild('observer');
    $observer->addAttribute('name',     $observer_name);
    $observer->addAttribute('instance', $model_name);
    // $observer->addAttribute('method',   $method_name);

    \Pulsestorm\Pestle\Library\output("Creating: $path_xml_event");
    $path = \Pulsestorm\Pestle\Library\writeStringToFile($path_xml_event, $xml->asXml());
    
    \Pulsestorm\Pestle\Library\output("Creating: $model_name");
    $contents = \Pulsestorm\Cli\Code_Generation\createClassTemplate($model_name, false, '\Magento\Framework\Event\ObserverInterface');
    $contents = str_replace('<$body$>', 
    "\n" . 
    '    public function execute(\Magento\Framework\Event\Observer $observer){exit(__FILE__);}' .
    "\n" , $contents);
    \Pulsestorm\Magento2\Cli\Library\createClassFile($model_name, $contents);    
}
}
namespace Pulsestorm\Magento2\Cli\Generate\Plugin_Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;













function getDiXmlTemplate($config_attributes='xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd"')
{   
    if(!$config_attributes)
    {
        $config_attributes = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd"';
    }
    return trim('
<?xml version="1.0"?>
<config '.$config_attributes.'>

</config>
');

}

function underscoreClass($class)
{
    return strToLower(str_replace('\\','_',$class));
}

/**
* Generates plugin XML
* This command generates the necessary files and configuration 
* to "plugin" to a preexisting Magento 2 object manager object. 
*
*     pestle.phar generate_plugin_xml Pulsestorm_Helloworld 'Magento\Framework\Logger\Monolog' 'Pulsestorm\Helloworld\Plugin\Magento\Framework\Logger\Monolog'
* 
* @argument module_name Create in which module? [Pulsestorm_Helloworld]
* @argument class Which class are you plugging into? [Magento\Framework\Logger\Monolog]
* @argument class_plugin What's your plugin class name? [<$module_name$>\Plugin\<$class$>]
* @command generate_plugin_xml
*/
function pestle_cli($argv)
{
    // $module_info = askForModuleAndReturnInfo($argv);
    $module_info    = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($argv['module_name']);
    $class          = $argv['class'];
    $class_plugin   = $argv['class_plugin'];

    $path_di = $module_info->folder . '/etc/di.xml';
    if(!file_exists($path_di))
    {
        $xml =  simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('di'));           
        \Pulsestorm\Pestle\Library\writeStringToFile($path_di, $xml->asXml());
        \Pulsestorm\Pestle\Library\output("Created new $path_di");
    }
    
    $xml            =  simplexml_load_file($path_di);   
//     $plugin_name    = strToLower($module_info->name) . '_' . underscoreClass($class);
//     simpleXmlAddNodesXpath($xml,
//         "/type[@name=$class]/plugin[@name=$plugin_name,@type=$class_plugin]");
             
    $type = $xml->addChild('type');
    $type->addAttribute('name', $class);
    $plugin = $type->addChild('plugin');
    
    $plugin->addAttribute('name',strToLower($module_info->name) . '_' . underscoreClass($class));
    $plugin->addAttribute('type',$class_plugin);
    
    \Pulsestorm\Pestle\Library\writeStringToFile($path_di, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
    \Pulsestorm\Pestle\Library\output("Added nodes to $path_di");
    
    $path_plugin = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($class_plugin);  
    $body = implode("\n", [
        '    //function beforeMETHOD($subject, $arg1, $arg2){}',
        '    //function aroundMETHOD($subject, $procede, $arg1, $arg2){return $proceed($arg1, $arg2);}',
        '    //function afterMETHOD($subject, $result){return $result;}']);
    $class_definition = str_replace('<$body$>', "\n$body\n", \Pulsestorm\Cli\Code_Generation\createClassTemplate($class_plugin));
    \Pulsestorm\Pestle\Library\writeStringToFile($path_plugin, $class_definition);
    \Pulsestorm\Pestle\Library\output("Created file $path_plugin");
}






}
namespace Pulsestorm\Magento2\Cli\Generate\Psr_Log_Level{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* For conversion of Zend Log Level into PSR Log Level
* 
* This command generates a list of Magento 1 log levels, 
* and their PSR log level equivalents.
*
* @command generate_psr_log_level
*/
function pestle_cli($argv)
{   
    $map = \Pulsestorm\Cli\Code_Generation\getZendPsrLogLevelMap();
    foreach($map as $key=>$value)
    {
        \Pulsestorm\Pestle\Library\output($key . "\t\t" . $value);
    }
}}
namespace Pulsestorm\Magento2\Cli\Generate\Registration{
use function Pulsestorm\Pestle\Importer\pestle_import;





/**
* Generates registration.php
* This command generates the PHP code for a 
* Magento module registration.php file.
* 
*     $ pestle.phar generate_registration Foo_Bar
*     <?php
*         \Magento\Framework\Component\ComponentRegistrar::register(
*             \Magento\Framework\Component\ComponentRegistrar::MODULE,
*             'Foo_Bar',
*             __DIR__
*         );
* 
* @command generate_registration
* @argument module_name Which Module? [Vendor_Module] 
*/
function pestle_cli($argv)
{
    $module_name = $argv['module_name'];
    
    \Pulsestorm\Pestle\Library\output(\Pulsestorm\Cli\Code_Generation\templateRegistrationPhp($module_name));
}
}
namespace Pulsestorm\Magento2\Cli\Generate\Route{
use function Pulsestorm\Pestle\Importer\pestle_import;
use Exception;













function createControllerClassName($module,$area='frontend')
{
    $class = str_replace('_','\\',$module) . '\Controller';
    if($area === 'adminhtml')
    {
        $class .= '\Adminhtml';
    }
    $class .= '\Index\Index';
    return $class;
}

function getRouterIdFromArea($area)
{
    $legend      = [
        'frontend' =>'standard',
        'adminhtml'=>'admin'
    ];
    $areas       = array_keys($legend);
    if(!in_array($area, $areas))
    {
        throw new Exception("Could not find router id for area");
    }    

    return $legend[$area];    
}

function createRoutesXmlFile($module_info, $area, $frontname, $router_id, $route_id)
{
    $module = $module_info->name;
    $path = $module_info->folder . '/etc/'. $area . '/routes.xml';
    if(!file_exists($path))
    {
        $xml = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('routes'));
        \Pulsestorm\Pestle\Library\writeStringToFile($path, $xml->asXml());
    }
    $xml = simplexml_load_file($path);   

    \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml,
        "router[@id=$router_id]/" .
        "route[@id=$route_id,@frontName=$frontname]/" .
        "module[@name=$module]");
    
    \Pulsestorm\Pestle\Library\writeStringToFile($path, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
    \Pulsestorm\Pestle\Library\output($path);
             
    return $xml;
}

function createControllerClassForRoute($module, $area, $acl)
{
    $class = createControllerClassName($module, $area, $acl);
    $controllerClass = \Pulsestorm\Cli\Code_Generation\createControllerClass(
        $class, 
        $area
    );    
    $path_controller = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($class);    
    \Pulsestorm\Pestle\Library\writeStringToFile($path_controller, $controllerClass);
    
    \Pulsestorm\Pestle\Library\output($path_controller);
}

/**
* Creates a Route XML
* generate_route module area id 
* @command generate_route
* @argument module_name Which Module? [Pulsestorm_HelloWorld]
* @argument area Which Area (frontend, adminhtml)? [frontend]
* @argument frontname Frontname/Route ID? [pulsestorm_helloworld]
*/
function pestle_cli($argv)
{    
    $module      = $argv['module_name'];
    $area        = $argv['area'];    
    $frontname   = $argv['frontname'];    
    
    $module_info = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($module);        
    $router_id   = getRouterIdFromArea($area);
    $route_id    = $frontname;

    $xml = createRoutesXmlFile(
        $module_info, $area, $frontname, $router_id, $route_id
    );        
    
    $acl = $module . '::' . $frontname . '_menu';
    createControllerClassForRoute($module, $area, $acl);
    
    if($area === 'adminhtml')
    {
        \Pulsestorm\Pestle\Library\output("    Don't forget your menu.xml and acl.xml");
        \Pulsestorm\Pestle\Library\output('    action="'.$frontname.'/index/index"');
        \Pulsestorm\Pestle\Library\output('    id="' . $acl);
    }
}}
namespace Pulsestorm\Magento2\Cli\Generate\Theme{
use function Pulsestorm\Pestle\Importer\pestle_import;








function createFrontendFolders($base_folder, $package, $theme, $area)
{
    //web/css/source
    //fonts
    //images
    //js
    
    $folders = [
        $base_folder . '/web/css/source',
        $base_folder . '/fonts',
        $base_folder . '/images',
        $base_folder . '/js',                        
    ];
    
    foreach($folders as $folder)
    {
        if(!is_dir($folder))
        {
            \Pulsestorm\Pestle\Library\output("Creating: $folder");
            mkdir($folder,0755,true);
        }
        else
        {
            \Pulsestorm\Pestle\Library\output("Exists: $folder");
        }
    }
}

function createThemeXmlFile($base_folder, $package, $theme, $area, $parent_name)
{
    $path = $base_folder . '/theme.xml';    
    $xml  = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('theme'));
    
    $title  = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml, 'title');
    dom_import_simplexml($title)->nodeValue = ucwords($package . ' ' . $theme);
    
    if($parent_name)
    {
        $parent = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml, 'parent');
        dom_import_simplexml($parent)->nodeValue = $parent_name;
    }
    $image  = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml, 'media/preview_image');
    
    \Pulsestorm\Pestle\Library\output("Creating: $path");
    \Pulsestorm\Pestle\Library\writeStringToFile($path, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
}

function createRegistrationPhpFile($base_folder, $package, $theme, $area)
{
    $path = $base_folder . '/registration.php';    
    $registration_string = $area . '/' . $package . '/' . $theme;
    $registration = \Pulsestorm\Cli\Code_Generation\templateRegistrationPhp($registration_string, 'THEME');
    
    \Pulsestorm\Pestle\Library\output("Creating: $path");
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $registration);

}

function createViewXmlFile($base_folder, $package, $theme, $area)
{
    $path  = $base_folder . '/etc/view.xml'; 
    $xml   = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('view')); 
    $media = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml, 'media');
    \Pulsestorm\Pestle\Library\output("Creating: $path");
    \Pulsestorm\Pestle\Library\writeStringToFile($path, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));    
}
/**
* One Line Description
*
* @command generate_theme
* @argument package Theme Package Name? [Pulsestorm]
* @argument theme Theme Name? [blank]
* @argument area Area? (frontend, adminhtml) [frontend]
* @argument parent Parent theme (enter 'null' for none) [Magento/blank]
*
*/
function pestle_cli($argv)
{
    $package = $argv['package'];
    $theme   = $argv['theme'];    
    $area    = $argv['area'];
    $parent  = $argv['parent'];
    if(strpos($parent, 'null') !== false)
    {
        $parent = '';
    }
    $base_folder = \Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir() . '/app/design' . '/' .
        $area . '/' . $package . '/' . $theme;

    createThemeXmlFile($base_folder, $package, $theme, $area, $parent);
    createRegistrationPhpFile($base_folder, $package, $theme, $area);
    createViewXmlFile($base_folder, $package, $theme, $area);
    createFrontendFolders($base_folder, $package, $theme, $area);
    //theme.xml
    //registration.php
    //view.xml

    
    
                
    \Pulsestorm\Pestle\Library\output($base_folder);
    \Pulsestorm\Pestle\Library\output("Done");
}
}
namespace Pulsestorm\Magento2\Cli\Generate\View{
use function Pulsestorm\Pestle\Importer\pestle_import;










function createTemplateFile($module_info, $area, $template)
{
    $path = $module_info->folder . '/view/' . 
                $area . '/templates/' .  $template;

    \Pulsestorm\Pestle\Library\output("Creating $path");
    \Pulsestorm\Pestle\Library\writeStringToFile($path, '<h1>This is my template, there are many like it, but this one is mine.</h1>');                
    
}

function createHandleFile($module_info, $area, $template, $class, $handle, $layout)
{
    $xml = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('layout_handle'));
    $name  = strToLower($module_info->name) . 
        '_block_' . 
        strToLower(\Pulsestorm\Pestle\Library\getShortClassNameFromClass($class));
    
    \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml,
        'referenceBlock[@name=content]/block[' . 
        '@template=' . $template . ',' .
        '@class='    . $class    . ',' .
        '@name='     . $name     . ']'
    );
    
    $xml['layout'] = $layout;
    if($layout === '' || $area === 'adminhtml')
    {
        unset($xml['layout']);
    }

    $path = $module_info->folder . '/view/' . 
                $area . '/layout/' .  $handle . '.xml';

    \Pulsestorm\Pestle\Library\output("Creating: $path");
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $xml->asXml());                   
    
}

function createBlockClass($module_info, $block_name, $area='frontname')
{
    $class_name = str_replace('_', '\\', $module_info->name) . 
        '\Block\\';
    if($area === 'adminhtml')
    {
        $class_name .= 'Adminhtml\\';
    }        
    $class_name .= ucwords($block_name);
    
    \Pulsestorm\Pestle\Library\output("Creating: " . $class_name);
    $baseClass = '\Magento\Framework\View\Element\Template';
    if($area === 'adminhtml')
    {
        $baseClass = '\Magento\Backend\Block\Template';
    }
    $contents = \Pulsestorm\Cli\Code_Generation\createClassTemplate($class_name, $baseClass);
    $contents = str_replace('<$body$>', "\n".'    function _prepareLayout(){}'."\n", $contents);
    \Pulsestorm\Magento2\Cli\Library\createClassFile($class_name, $contents);
    return $class_name;
}

/**
* One Line Description
*
* @command generate_view
* @argument module_name Which Module? [Pulsestorm_HelloGenerate]
* @argument area Which Area? [frontend]
* @argument handle Which Handle? [<$module_name$>_index_index]
* @argument block_name Block Name? [Main]
* @argument template Template File? [content.phtml]
* @argument layout Layout (ignored for adminhtml) ? [1column]
*/
function pestle_cli($argv)
{
    $module_name    = $argv['module_name'];
    $area           = $argv['area'];
    $handle         = $argv['handle'];
    $block_name     = $argv['block_name'];            
    $template       = $argv['template'];            
    $layout         = $argv['layout'];
    
    $module_info    = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($module_name);

    createTemplateFile($module_info, $area, $template);    
    $class = createBlockClass($module_info, $block_name, $area);
    createHandleFile($module_info, $area, $template, $class, $handle, $layout);
    
}
}
namespace Pulsestorm\Magento2\Cli\Hello_Argument{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command hello_argument
* @argument greeting Please Enter the Greeting [Hello]
* @option explain Should I display the explain text?
* @argument entity Please Enter the Entity [World]
*/
function pestle_cli($argv, $options)
{    
    \Pulsestorm\Pestle\Library\output($argv['greeting'] . " " . $argv['entity']);
    if($options['explain'])
    {
        \Pulsestorm\Pestle\Library\output("This command demos automatic arguments");
    }
}
}
namespace Pulsestorm\Magento2\Cli\Hello_World{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* One Line Description
*
* @command hello_world
* @option service Which branch of the service
*/
function pestle_cli($argv, $options)
{
    $person = 'Sailor';
    if(array_key_exists('service', $options))
    {
        if($options['service'] === 'army')
        {
            $person = 'Soldier';
        }
        
    }
    \Pulsestorm\Pestle\Library\output("Hello $person");
}
}
namespace Pulsestorm\Magento2\Cli\Help{
use function Pulsestorm\Pestle\Importer\pestle_import;

/**
* Alias for list
* @command help
*/
function pestle_cli($argv)
{
    require_once __DIR__ . '/../list_commands/module.php';
    if(isset($argv[0]))
    {
        $argv[0] = \Pulsestorm\Pestle\Runner\applyCommandNameAlias($argv[0]);
    }
    return \Pulsestorm\Magento2\Cli\List_Commands\pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Library{
use ReflectionFunction;
use Exception;
use DomDocument;
use function Pulsestorm\Pestle\Importer\pestle_import;













function getModuleInformation($module_name)
{
    list($vendor, $name) = explode('_', $module_name);        
    return (object) [
        'vendor'        => $vendor,
        'short_name'    => $name,
        'name'          => $module_name,
        'folder'        => getBaseMagentoDir() . "/app/code/$vendor/$name",
    ];
}

function getBaseModuleDir($module_name)
{
    $path = getModuleInformation($module_name)->folder;
    if(!file_exists($path))
    {
        throw new Exception("No such path: $path");
    }
    return $path;
}

function askForModuleAndReturnInfo($argv, $index=0)
{
    $module_name = \Pulsestorm\Pestle\Library\inputOrIndex(
        "Which module?", 
        'Magento_Catalog', $argv, $index);
    return getModuleInformation($module_name);        
}

function askForModuleAndReturnFolder($argv)
{
    $module_folder = \Pulsestorm\Pestle\Library\inputOrIndex(
        "Which module?", 
        'Magento_Catalog', $argv, 0);
    list($package, $vendor) = explode('_', $module_folder);        
    return getBaseMagentoDir() . "/app/code/$package/$vendor";
}

function getBaseMagentoDir($path=false)
{
    if($path && \Pulsestorm\Pestle\Library\isAboveRoot($path))
    {
        \Pulsestorm\Pestle\Library\output("Could not find base Magento directory");
        exit;
    }

    $path = $path ? $path : getcwd();
    if(file_exists($path . '/app/etc/di.xml'))
    {
        return realpath($path);
    }
    return getBaseMagentoDir($path . '/..');
    // return $path;
}

function getModuleBaseDir($module)
{
    $path = implode('/', [
        getBaseMagentoDir(),
        'app/code',
        str_replace('_', '/', $module)]
    );
    
    return $path;
}

function getModuleConfigDir($module)
{
    return implode('/', [
        getModuleBaseDir($module), 
        'etc']);
}

function initilizeModuleConfig($module, $file, $xsd)
{
    $path = implode('/', [
        getModuleConfigDir($module),
        $file]);
        
    if(file_exists($path))
    {
        return $path;
    }        
    
    $xml = \Pulsestorm\Xml_Library\addSchemaToXmlString('<config></config>', $xsd);
    $xml = simplexml_load_string($xml);
            
    if(!is_dir(dirname($path)))
    {
        mkdir(dirname($path), 0777, true);
    }
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $xml->asXml());

    return $path;
}

function getSimpleTreeFromSystemXmlFile($path)
{
    $tree = [];
    $xml = simplexml_load_file($path);
    foreach($xml->system->section as $section)
    {
        $section_name        = (string) $section['id'];
        $tree[$section_name] = [];

        foreach($section->group as $group)
        {               
            $group_name = (string) $group['id']; 
            $tree[$section_name][$group_name] = [];
            foreach($group->field as $field)
            {
                $tree[$section_name][$group_name][] = (string) $field['id'];
            }
        }
    }
    return $tree;
}



function createClassFile($model_name, $contents)
{
    $path = getBaseMagentoDir() . '/app/code/' .
        str_replace('\\','/',$model_name) . '.php';
    
    if(file_exists($path))
    {
        \Pulsestorm\Pestle\Library\output($path, "\n" . 'File already exists, skipping');
        return;
    }
    if(!is_dir(dirname($path)))
    {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $contents);
}

function resolveAlias($alias, $config, $type='models')
{
    if($type[strlen($type)-1] !== 's')
    {
        $type .='s';
    }
    if(strpos($alias, '/') === false)
    {
        return $alias;
    }
    list($group, $model) = explode('/', $alias);
    $prefix = (string)$config->global->{$type}->{$group}->class;

    $model = str_replace('_', ' ', $model);
    $model = ucwords($model);
    $model = str_replace(' ', '_', $model);

    $mage1 = $prefix . '_' . $model;
    return str_replace('_','\\',$mage1);        
}

function convertObserverTreeScoped($config, $xml)
{        
    $xml_new = simplexml_load_string('<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd"></config>');
    if(!$config->events)
    {
        return $xml_new;
    }

    foreach($config->events->children() as $event)
    {
        $event_name = modifyEventNameToConvertFromMage1ToMage2($event->getName());
        $event_xml  = $xml_new->addChild('event');
        $event_xml->addAttribute('name',$event_name);
        
        foreach($event->observers->children() as $observer)
        {
            //<observer name="check_theme_is_assigned" instance="Magento\Theme\Model\Observer" method="checkThemeIsAssigned" />
            //shared = false
            $observer_xml = $event_xml->addChild('observer');
            $observer_xml->addAttribute('name', $observer->getName());
            $observer_xml->addAttribute('instance', resolveAlias((string) $observer->{'class'}, $xml));
            $observer_xml->addAttribute('method', (string) $observer->method);
            if( (string) $observer->type === 'model')
            {
                $observer_xml->addAttribute('shared','false');
            }
        }
    }
    
    return $xml_new;
}

function modifyEventNameToConvertFromMage1ToMage2NoAdminhtml($name)
{
    $parts = explode('_', $name);
    $parts = array_filter($parts, function($part){
        return $part !== 'adminhtml';
    });
    return implode('_', $parts);
}

function modifyEventNameToConvertFromMage1ToMage2($name)
{
    $name = modifyEventNameToConvertFromMage1ToMage2NoAdminhtml($name);
    return $name;
}

function getMage1ClassPathFromConfigPathAndMage2ClassName($path, $class)
{
    $path_from_pool = $path;
    $pools = ['community','core','local'];
    foreach($pools as $pool)
    {
        $path_from_pool = preg_replace('%^.*app/code/'.$pool.'/%','',$path_from_pool);
    }
    
    $parts_mage_2 = explode('\\',$class);
    $mage2_vendor = $parts_mage_2[0];
    $mage2_module = $parts_mage_2[1];
    
    $parts_mage_1 = explode('/', $path_from_pool);
    $mage1_vendor = $parts_mage_1[0];
    $mage1_module = $parts_mage_1[1];
    
    if( ($mage1_vendor !== $mage2_vendor) || $mage1_module !== $mage2_module)
    {
        throw new Exception('Config and alias do not appear to match');
    }
    
    $path_from_pool_parts = explode('/',$path);
    $new = [];
    for($i=0;$i<count($path_from_pool_parts);$i++)
    {
        $part = $path_from_pool_parts[$i];
        
        if($part === $mage1_vendor && $path_from_pool_parts[$i+1] == $mage1_module)
        {
            $new[] = str_replace('\\','/',$class) . '.php';
            break;
        }        
        $new[] = $part;
    }
    
    return implode('/',$new);
}

function getVariableNameFromNamespacedClass($class)
{
    $parts = explode('\\', $class);
    $parts = array_slice($parts, 2);
    
    $var = implode('', $parts);    
    
    if($var)
    {
        $var[0] = strToLower($var);
    }
    
    return '$' . $var;
}

function getDiLinesFromMage2ClassName($class, $var=false)
{
    if(!$var)
    {
        $var  = getVariableNameFromNamespacedClass($class);
    }
    $parameter  = '\\' . trim($class,'\\') . ' ' . $var . ',';
    $property   = 'protected ' . $var . ';';
    $assignment = '$this->' . ltrim($var, '$') . ' = ' . $var . ';';
    
    $lines = $parameter;
    
    return [
        'property' =>$property,
        'parameter'=>$parameter,
        'assignment'=>$assignment
    ];
}

function getKnownClassMap()
{
    return ['Mage\Core\Helper\Abstract'=>'Magento\Framework\App\Helper\AbstractHelper'];
}

function getKnownClassesMappedToNewClass($return)
{
    $full_class = $return['namespace'] . '\\' . $return['class'];
    $map = getKnownClassMap();
    // echo $full_class,"\n";
    if(!array_key_exists($full_class, $map))
    {
        return $return;
    }
    
    $parts = explode('\\', $map[$full_class]);

    $return = [        
        'class'     =>array_pop($parts),  
        'namespace' =>implode('\\',$parts),

    ];  
    return $return;    
}

function getNamespaceAndClassDeclarationFromMage1Class($class, $extends='')
{
    $parts = explode('_', $class);      
    $return = [        
        'class'     =>array_pop($parts),  
        'namespace' =>implode('\\',$parts),

    ];    
    
    $return = getKnownClassesMappedToNewClass($return);
    
    $return['full_class'] = $return['namespace'] . '\\' . $return['class'];
    return $return;
}

function convertMageOneClassIntoNamespacedClass($path_mage1)
{
    $text = file_get_contents($path_mage1);
    preg_match('%class.+?(extends)?.+?\{%', $text, $m);
    if(count($m) === 0)
    {
        throw new Exception("Could not extract class declaration");
    }
    $declaration = $m[0];
    if(strpos($declaration, 'implements'))
    {
        throw new Exception("Can't handle implements yet, but should be easy to add");
    }
    $class   = getNamespaceAndClassDeclarationFromMage1Class(
        \Pulsestorm\Pestle\Library\getClassFromDeclaration($declaration));
    $extends = getNamespaceAndClassDeclarationFromMage1Class(
        \Pulsestorm\Pestle\Library\getExtendsFromDeclaration($declaration)); 
        
    $declaration_new = \Pulsestorm\Pestle\Library\getNewClassDeclaration($class, $extends);
        
    $text = str_replace($declaration, $declaration_new, $text);
    return $text;
}

function inputModuleName()
{
    return \Pulsestorm\Pestle\Library\input("Which module?", 'Packagename_Vendorname');
}

function addSpecificChild($childNodeName, $node, $name, $type, $text=false)
{
    $namespace = \Pulsestorm\Xml_Library\getXmlNamespaceFromPrefix($node, 'xsi');
    $child = $node->addChild($childNodeName);
    $child->addAttribute('name',$name);
    $child->addAttribute('xsi:type',$type,$namespace);
    if($text)
    {
        $child[0] = $text;
    }
    return $child;
}

function addArgument($node, $name, $type, $text=false)
{
    return addSpecificChild('argument', $node, $name, $type, $text);
}

function addItem($node, $name, $type, $text=false)
{
    return addSpecificChild('item', $node, $name, $type, $text);
}

function validateAs($xml, $type)
{
    if($xml->getName() !== $type)
    {
        \Pulsestorm\Pestle\Library\output("Not a <$type/> node, looks like a <{$xml->getName()}/> node, bailing.");
        exit;
    }

}

function validateAsListing($xml)
{
    return validateAs($xml, 'listing');
}

function getOrCreateColumnsNode($xml)
{
    $columns = $xml->columns;
    if(!$columns)
    {
        $columns = $xml->addChild('columns');
    }
    return $columns;
}

/**
* Not a command, just library functions
* @command library
*/
function pestle_cli($argv)
{
}}
namespace Pulsestorm\Magento2\Cli\List_Commands{
use ReflectionFunction;
use function Pulsestorm\Pestle\Importer\pestle_import;




/**
* Lists help
* Read the doc blocks for all commands, and then
* outputs a list of commands along with thier doc
* blocks.  
* @command list_commands
*/
function pestle_cli($argv)
{
    \Pulsestorm\Cli\Build_Command_List\includeAllModuleFiles();
    
    $user = get_defined_functions()['user'];
    $executes = array_filter($user, function($function){
        $parts = explode('\\', $function);
        $function = array_pop($parts);
        return strpos($function, 'pestle_cli') === 0;
    });
    
        
    $commands = array_map(function($function){
        $r       = new ReflectionFunction($function);
        $command = \Pulsestorm\Pestle\Library\getAtCommandFromDocComment($r);
        return [
            'command'=>$command,
            'help'=>\Pulsestorm\Pestle\Library\getDocCommentAsString($r->getName()),
        ];
        // $function = str_replace('execute_', '', $function);
        // $parts = explode('\\', $function);
        // return array_pop($parts);
        // return $function;
    }, $executes);

    $command_to_check = array_shift($argv);        

    if($command_to_check)
    {
        $commands = array_filter($commands, function($s) use ($command_to_check){
            return $s['command'] === $command_to_check;
        });
    }
    \Pulsestorm\Pestle\Library\output('');
    foreach($commands as $command)
    {
        \Pulsestorm\Pestle\Library\output("Name");
        \Pulsestorm\Pestle\Library\output("    ", $command['command']);
        \Pulsestorm\Pestle\Library\output('');
        \Pulsestorm\Pestle\Library\output("Description");
        \Pulsestorm\Pestle\Library\output(preg_replace('%^%m','    $0',wordwrap($command['help'],70)));
        \Pulsestorm\Pestle\Library\output('');
        \Pulsestorm\Pestle\Library\output('');
    }
}}
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Preference{
use function Pulsestorm\Pestle\Importer\pestle_import;








function loadOrCreateDiXml($module_info)
{
    $path_di = $module_info->folder . '/etc/di.xml';
    if(!file_exists($path_di))
    {
        $xml =  simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('di'));           
        \Pulsestorm\Pestle\Library\writeStringToFile($path_di, $xml->asXml());
        \Pulsestorm\Pestle\Library\output("Created new $path_di");
    }    
    $xml            =  simplexml_load_file($path_di);       
    return [
        'path'=>$path_di,
        'xml'=>$xml
    ];
}

function generateDiConfiguration($argv)
{
    $moduleInfo        = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($argv['module']);
    $pathAndXml        = loadOrCreateDiXml($moduleInfo);
    $path              = $pathAndXml['path'];
    $di_xml            = $pathAndXml['xml'];

    $preference         = $di_xml->addChild('preference');
    $preference['for']  = $argv['for'];
    $preference['type'] = $argv['type'];
    
    \Pulsestorm\Pestle\Library\writeStringToFile($path, \Pulsestorm\Xml_Library\formatXmlString($di_xml->asXml()));    

}

function isTypeInterface($type)
{
    //string detection for now -- change to actually examine system?
    return strpos($type, 'Interface') !== false;
}

function generateNewClass($argv)
{    
    $pathType       = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($argv['type']);  
    
    $typeGlobalNs   = '\\' . trim($argv['for'],'\\');
    $classContents  = \Pulsestorm\Cli\Code_Generation\createClassTemplate($argv['type'], $typeGlobalNs);        
    if(isTypeInterface($typeGlobalNs))
    {
        $classContents  = \Pulsestorm\Cli\Code_Generation\createClassTemplate($argv['type'], null, $typeGlobalNs);
    }
    
    $classContents  = str_replace('<$body$>', '',$classContents);
    
    if(!file_exists($pathType))
    {
        \Pulsestorm\Pestle\Library\output("Creating $pathType");
        \Pulsestorm\Pestle\Library\writeStringToFile($pathType, $classContents);
    }
    else
    {
    \Pulsestorm\Pestle\Library\output("$pathType already exists, skipping creation");
    }
}

/**
* Generates a Magento 2.1 ui grid listing and support classes.
*
* @command magento2:generate:preference
* @argument module Which Module? [Pulsestorm_Helloworld]
* @argument for For which Class/Interface/Type? [Pulsestorm\Helloworld\Model\FooInterface]
* @argument type New Concrete Class? [Pulsestorm\Helloworld\Model\NewModel]
*/
function pestle_cli($argv)
{    
    generateDiConfiguration($argv);
    generateNewClass($argv);

    // output("Created file $path_plugin");       
    // output("This command will add to di.xml (create if needed)");
    // output("This command will also generate a class");
    // output("If passed an interface, class will implement");
    // output("If passed a class, class will extend");
    // output("Simple text matching for interface detection?");
//     generateUiComponentXmlFile(
//         $argv['grid_id'], $argv['db_id_column'], $module_info);                                        
//         
//     generateDataProviderClass(
//         $module_info, $argv['grid_id'], $argv['collection_resource'] . 'Factory');
//         
//     generatePageActionClass(
//         $module_info, $argv['grid_id'], $argv['db_id_column']);                    
//         
//     output("Don't forget to add this to your layout XML with <uiComponent name=\"{$argv['grid_id']}\"/> ");        
}
}
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Ui_Add_Column_Actions{
use function Pulsestorm\Pestle\Importer\pestle_import;







function getPackageAndModuleNameFromListingXmlFile($file)
{
    if(strpos($file, 'app/code') === false)
    {
        \Pulsestorm\Pestle\Library\output("At the time this command was written, pestle assumed app/code as a working directory");
        \Pulsestorm\Pestle\Library\output("That file isn't in app/code, so we need to bail :(");
        exit;
    }
    $parts = explode('app/code/', $file);
    $parts = explode('/', array_pop($parts));
    
    return [$parts[0], $parts[1]];
}

function getGridIdFromListingXmlFile($xml)
{
    $stuff = pathinfo($xml);
    return $stuff['filename'];    
}

function generatePageActionsClassFromListingXmlFileAndXml($file, $xml)
{
    list($package, $moduleName) = getPackageAndModuleNameFromListingXmlFile($file);
    $gridId                     = getGridIdFromListingXmlFile($file);
    
    $pageActionsClassName = $package . '\\' . $moduleName . '\\' . 
        'Ui\Component\Listing\Column\\' . 
        ucwords(preg_replace('%[^a-zA-Z0-9]%', '', $gridId)) . '\\' .
        'PageActions';
        
    var_dump($pageActionsClassName);        
    exit;
    return $actionsClass = 'Foo\Baz\Bar\Actions';
}

/**
* Generates a Magento 2.1 ui grid listing and support classes.
*
* @command magento2:generate:ui:add-column-actions
* @argument listing_file Which Listing File? []
* @argument index_field Index Field/Primary Key? [entity_id]
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['listing_file']);
    \Pulsestorm\Magento2\Cli\Library\validateAsListing($xml);
    
    $actionsClass = generatePageActionsClassFromListingXmlFileAndXml($argv['listing_file'], $xml);
    
    $columns = \Pulsestorm\Magento2\Cli\Library\getOrCreateColumnsNode($xml);            
    $actionsColumn = $columns->addChild('actionsColumn');
    $actionsColumn->addAttribute('name', 'actions');    
    $actionsColumn->addAttribute('class', $actionsClass);    
    $argument = \Pulsestorm\Magento2\Cli\Library\addArgument($actionsColumn, 'data', 'array');    
    $configItem = \Pulsestorm\Magento2\Cli\Library\addItem($argument, 'config', 'array');
    $indexField = \Pulsestorm\Magento2\Cli\Library\addItem($configItem, 'indexField', 'string', $argv['index_field']);
    
    \Pulsestorm\Pestle\Library\output(
        \Pulsestorm\Xml_Library\formatXmlString($xml->asXml())
    );
    
// <actionsColumn name="actions" class="Pulsestorm\ToDoCrud\Ui\Component\Listing\Column\Pulsestormtodolisting\PageActions">
//     <argument name="data" xsi:type="array">
//         <item name="config" xsi:type="array">
//             <item name="resizeEnabled" xsi:type="boolean">false</item>
//             <item name="resizeDefaultWidth" xsi:type="string">107</item>
//             <item name="indexField" xsi:type="string">pulsestorm_todocrud_todoitem_id</item>
//         </item>
//     </argument>
// </actionsColumn>

}
}
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Ui_Add_Column_Sections{
use function Pulsestorm\Pestle\Importer\pestle_import;






// pestle_import('Pulsestorm\Xml_Library\simpleXmlAddNodesXpath');    
// pestle_import('Pulsestorm\Xml_Library\formatXmlString');
// pestle_import('Pulsestorm\Xml_Library\getXmlNamespaceFromPrefix');
// pestle_import('Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml');
// pestle_import('Pulsestorm\Magento2\Cli\Library\getModuleInformation');
// pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');
// pestle_import('Pulsestorm\Magento2\Cli\Library\createClassFile');
// pestle_import('Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse');

/**
* Generates a Magento 2.1 ui grid listing and support classes.
*
* @command magento2:generate:ui:add-column-sections
* @argument listing_file Which Listing File? []
* @argument column_name Column Name? [ids]
* @argument index_field Index Field/Primary Key? [entity_id]
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['listing_file']);
    \Pulsestorm\Magento2\Cli\Library\validateAsListing($xml);
    $columns = \Pulsestorm\Magento2\Cli\Library\getOrCreateColumnsNode($xml);
    
    $sectionsColumn = $columns->addChild('selectionsColumn');
    $sectionsColumn->addAttribute('name', $argv['column_name']);    
    $argument = \Pulsestorm\Magento2\Cli\Library\addArgument($sectionsColumn, 'data', 'array');    
    $configItem = \Pulsestorm\Magento2\Cli\Library\addItem($argument, 'config', 'array');
    $indexField = \Pulsestorm\Magento2\Cli\Library\addItem($configItem, 'indexField', 'string', $argv['index_field']);

    writeStringToFile($argv['listing_file'], \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));     

}
}
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Ui_Grid{
use function Pulsestorm\Pestle\Importer\pestle_import;

    









function generateArgumentNode($xml, $gridId, $dataSourceName, $columnsName)
{
    $fullIdentifier = $gridId . '.' . $dataSourceName;
    
    $argument   = \Pulsestorm\Magento2\Cli\Library\addArgument($xml, 'data', 'array');
    $js_config  = \Pulsestorm\Magento2\Cli\Library\addItem($argument,'js_config','array');
    $provider   = \Pulsestorm\Magento2\Cli\Library\addItem($js_config, 'provider', 'string', $fullIdentifier);      
    $deps       = \Pulsestorm\Magento2\Cli\Library\addItem($js_config, 'deps', 'string', $fullIdentifier);      
    $argument   = \Pulsestorm\Magento2\Cli\Library\addItem($argument, 'spinner', 'string', $columnsName);         
        
    return $argument;
}

function addArgumentsToDataProvider($dataProvider, $providerClass, 
            $dataSourceName, $databaseIdName, $requestIdName)
{
    \Pulsestorm\Magento2\Cli\Library\addArgument($dataProvider, 'class','string',$providerClass);
    \Pulsestorm\Magento2\Cli\Library\addArgument($dataProvider, 'name','string',$dataSourceName);
    \Pulsestorm\Magento2\Cli\Library\addArgument($dataProvider, 'primaryFieldName','string',$databaseIdName);
    \Pulsestorm\Magento2\Cli\Library\addArgument($dataProvider, 'requestFieldName','string',$requestIdName);
    $dataForProvider = \Pulsestorm\Magento2\Cli\Library\addArgument($dataProvider, 'data','array');
    
    $config     = \Pulsestorm\Magento2\Cli\Library\addItem($dataForProvider, 'config','array');
    $update_url = \Pulsestorm\Magento2\Cli\Library\addItem($config,'update_url','url');
    $update_url->addAttribute('path', 'mui/index/render');

    $storageConfig = \Pulsestorm\Magento2\Cli\Library\addItem($config, 'storageConfig', 'array');    
    $indexField    = \Pulsestorm\Magento2\Cli\Library\addItem($storageConfig, 'indexField', 'string', $databaseIdName);
//     <item name="storageConfig" xsi:type="array">
//         <item name="indexField" xsi:type="string">pulsestorm_commercebug_log_id</item>
//     </item>                    
    
}

function generateDatasourceNode($xml, $dataSourceName, $providerClass, $databaseIdName, $requestIdName)
{
    $dataSource      = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml, "dataSource[@name=$dataSourceName]");
    
    $dataProvider    = \Pulsestorm\Magento2\Cli\Library\addArgument($dataSource, 'dataProvider','configurableObject');
    addArgumentsToDataProvider($dataProvider, $providerClass, $dataSourceName, 
                                $databaseIdName, $requestIdName);
    
    $data            = \Pulsestorm\Magento2\Cli\Library\addArgument($dataSource, 'data','array');
    $js_config       = \Pulsestorm\Magento2\Cli\Library\addItem($data, 'js_config', 'array');
    $component       = \Pulsestorm\Magento2\Cli\Library\addItem($js_config, 'component', 'string', 'Magento_Ui/js/grid/provider');
    return $dataSource;
}

function addBaseColumnItemNodes($config, $width, $indexField)
{
    \Pulsestorm\Magento2\Cli\Library\addItem($config, 'resizeEnabled', 'boolean', 'false');
    \Pulsestorm\Magento2\Cli\Library\addItem($config, 'resizeDefaultWidth', 'string', $width);
    \Pulsestorm\Magento2\Cli\Library\addItem($config, 'indexField', 'string', $indexField);
}

function addIdColumnToColumns($columns, $data, $idColumn)
{
    $columnId = $columns->addChild('column');
    $columnId->addAttribute('name', $idColumn);
    $data = \Pulsestorm\Magento2\Cli\Library\addArgument($columnId, 'data', 'array');
    $config = \Pulsestorm\Magento2\Cli\Library\addItem($data, 'config', 'array');
    
    \Pulsestorm\Magento2\Cli\Library\addItem($config, 'filter',  'string', 'textRange');
    \Pulsestorm\Magento2\Cli\Library\addItem($config, 'sorting', 'string', 'asc');
    
    $id = \Pulsestorm\Magento2\Cli\Library\addItem($config, 'label',   'string', 'ID');
    $id->addAttribute('translate', 'true');

}

function addActionsColumnToColumns($columns, $pageActionsClassName, $idColumn)
{
    $actionsColumn = $columns->addChild('actionsColumn');
    $actionsColumn->addAttribute('name','actions');
    $actionsColumn->addAttribute('class',$pageActionsClassName);
    $data = \Pulsestorm\Magento2\Cli\Library\addArgument($actionsColumn, 'data','array');
    $config = \Pulsestorm\Magento2\Cli\Library\addItem($data, 'config', 'array');
    addBaseColumnItemNodes($config, '107', $idColumn);        
    return $actionsColumn;
}

function generateColumnsNode($xml, $columnsName, $pulsestorm_commercebug_log_id, $pageActionsClassName)
{
    $columns         = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml, "columns[@name=$columnsName]");    
    $sectionColumns  = $columns->addChild('selectionsColumn');
    $sectionColumns->addAttribute('name','ids');
    $data = \Pulsestorm\Magento2\Cli\Library\addArgument($sectionColumns, 'data', 'array');
    $config = \Pulsestorm\Magento2\Cli\Library\addItem($data, 'config', 'array');
        
    addBaseColumnItemNodes($config, '55', $pulsestorm_commercebug_log_id);            
    addIdColumnToColumns($columns, $data, $pulsestorm_commercebug_log_id);
    addActionsColumnToColumns($columns, $pageActionsClassName, $pulsestorm_commercebug_log_id);
                
    return $columns;
}

function generateListingToolbar($xml)
{
    $columns         = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml, 'listingToolbar[@name=listing_top]/paging[@name=listing_paging');        
}

function generateDataSourceNameFromGridId($grid_id)
{
    $dataSourceName   = $grid_id . '_data_source';
    return $dataSourceName;
}

function generateColumnsNameFromGridId($grid_id)
{
    $columnsName      = $grid_id . '_columns';
    return $columnsName;
}

function generateProdiverClassFromGridIdAndModuleInfo($grid_id, $module_info)
{
    $providerClass = $module_info->vendor . '\\' . $module_info->short_name . '\\' .
        'Ui\\Component\\Listing\\DataProviders\\' .
        str_replace(' ','\\',ucwords(str_replace('_', ' ', $grid_id)));    
    return $providerClass;    
}

function generatePageActionClassNameFromPackageModuleAndGridId($package, $moduleName, $gridId)
{
    $pageActionsClassName = 'Pulsestorm\Commercebug\Ui\Component\Listing\Column\PageActions';
    $pageActionsClassName = $package . '\\' . $moduleName . '\\' . 
        'Ui\Component\Listing\Column\\' . 
        ucwords(preg_replace('%[^a-zA-Z0-9]%', '', $gridId)) . '\\' .
        'PageActions';
    return $pageActionsClassName;        
}

function generateRequestIdName()
{
    return 'id';
}

function generateUiComponentXmlFile($gridId, $databaseIdName, $module_info)
{
    $pageActionsClassName = generatePageActionClassNameFromPackageModuleAndGridId(
        $module_info->vendor, $module_info->short_name, $gridId);
    $requestIdName    = generateRequestIdName();
    $providerClass    = generateProdiverClassFromGridIdAndModuleInfo($gridId, $module_info);    
    $dataSourceName   = generateDataSourceNameFromGridId($gridId);    
    $columnsName      = generateColumnsNameFromGridId($gridId);

    $xml             = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('uigrid'));        
    $argument        = generateArgumentNode($xml, $gridId, $dataSourceName, $columnsName);        
    $dataSource      = generateDatasourceNode($xml, $dataSourceName, $providerClass, $databaseIdName, $requestIdName);    
    $columns         = generateColumnsNode($xml, $columnsName, $databaseIdName, $pageActionsClassName);
    generateListingToolbar($xml);   
    
    $path = $module_info->folder . 
        '/view/adminhtml/ui_component/' . $gridId . '.xml';        
    \Pulsestorm\Pestle\Library\output("Creating New $path");
    \Pulsestorm\Pestle\Library\writeStringToFile($path, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
    return $xml;
}

function addVirtualType($xml, $virtualTypeName, $virtualTypeType)
{
    $virtualType = $xml->addChild('virtualType');
    $virtualType->addAttribute('name', $virtualTypeName);
    $virtualType->addAttribute('type', $virtualTypeType);    
    return $virtualType;
}

function generateDiXml($module_info)
{
    $path_di = $module_info->folder . '/etc/adminhtml/di.xml';
    if(!file_exists($path_di))
    {
        $xml =  simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('di'));           
        \Pulsestorm\Pestle\Library\writeStringToFile($path_di, $xml->asXml());
        \Pulsestorm\Pestle\Library\output("Created new $path_di");
    }
    $xml = simplexml_load_file($path_di);
    
    $item = \Pulsestorm\Xml_Library\simpleXmlAddNodesXpath($xml, 
        'type[@name=Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory]/' .
        'arguments/argument[@name=collections,@xsi:type=array]/' .
        'item[@name=pulsestorm_commercebug_log_data_source,@xsi:type=string]' 
        
    );            
    $item[0] = 'Pulsestorm\Commercebug\Model\ResourceModel\Log\Grid\Collection';

    $virtualType = addVirtualType(
        $xml, 'Pulsestorm\Commercebug\Model\ResourceModel\Log\Grid\Collection',
        'Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult');
    
    $arguments   = $virtualType->addChild('arguments');
    $argument   = \Pulsestorm\Magento2\Cli\Library\addArgument($arguments, 'mainTable', 'string', 'pulsestorm_commercebug_log');
    $argument   = \Pulsestorm\Magento2\Cli\Library\addArgument($arguments, 'resourceModel', 'string', 'Pulsestorm\Commercebug\Model\ResourceModel\Log');
    
    return $xml;
}

function generatePageActionClass($moduleInfo, $gridId, $idColumn)
{
    $pageActionsClassName = generatePageActionClassNameFromPackageModuleAndGridId(
        $moduleInfo->vendor, $moduleInfo->short_name, $gridId);
        
    $editUrl              = 'adminhtml/'.$gridId.'/viewlog';        
    $prepareDataSource    = '
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            foreach ($dataSource["data"]["items"] as & $item) {
                $name = $this->getData("name");
                $id = "X";
                if(isset($item["'.$idColumn.'"]))
                {
                    $id = $item["'.$idColumn.'"];
                }
                $item[$name]["view"] = [
                    "href"=>$this->getContext()->getUrl(
                        "'.$editUrl.'",["id"=>$id]),
                    "label"=>__("Edit")
                ];
            }
        }

        return $dataSource;
    }    
    ' . "\n";            
    $contents = \Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse($pageActionsClassName, '\Magento\Ui\Component\Listing\Columns\Column');            
    $contents = str_replace('<$use$>','',$contents);
    $contents = str_replace('<$body$>', $prepareDataSource, $contents);
    
    \Pulsestorm\Pestle\Library\output("Creating: $pageActionsClassName");
    $return   = \Pulsestorm\Magento2\Cli\Library\createClassFile($pageActionsClassName,$contents);             
    return $return;
}

function generateDataProviderClass($moduleInfo, $gridId, $collectionFactory)
{
    $providerClass      = generateProdiverClassFromGridIdAndModuleInfo($gridId, $moduleInfo);    
    $collectionFactory  = '\\' . trim($collectionFactory, '\\');
    $constructor = '    
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        '.$collectionFactory.' $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }' . "\n";        
    
    $contents           = \Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse($providerClass, '\\Magento\Ui\DataProvider\AbstractDataProvider');
    $contents           = str_replace('<$use$>', '',  $contents);
    $contents           = str_replace('<$body$>', $constructor,  $contents);    
    
    \Pulsestorm\Pestle\Library\output("Creating: $providerClass");
    $return             = \Pulsestorm\Magento2\Cli\Library\createClassFile($providerClass,$contents);    
    return $contents;
}

/**
* Generates a Magento 2.1 ui grid listing and support classes.
*
* @command magento2:generate:ui:grid
* @argument module Which Module? [Pulsestorm_Gridexample]
* @argument grid_id Create a unique ID for your Listing/Grid! [pulsestorm_gridexample_log]
* @argument collection_resource What Resource Collection Model should your listing use? [Magento\Cms\Model\ResourceModel\Page\Collection]
* @argument db_id_column What's the ID field for you model? [pulsestorm_gridexample_log_id]
*/
function pestle_cli($argv)
{
    $module_info      = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($argv['module']);

    generateUiComponentXmlFile(
        $argv['grid_id'], $argv['db_id_column'], $module_info);                                        
        
    generateDataProviderClass(
        $module_info, $argv['grid_id'], $argv['collection_resource'] . 'Factory');
        
    generatePageActionClass(
        $module_info, $argv['grid_id'], $argv['db_id_column']);                    
        
    \Pulsestorm\Pestle\Library\output("Don't forget to add this to your layout XML with <uiComponent name=\"{$argv['grid_id']}\"/> ");        
}
}
namespace Pulsestorm\Magento2\Cli\Orphan_Content{
use function Pulsestorm\Pestle\Importer\pestle_import;

function getUrl($url)
{
    \Pulsestorm\Pestle\Library\output("Fetching $url");
    return `curl --silent $url`;
}

function getUrlsFromHtml($html)
{
    $urls = array();
    $xml = new \DomDocument;
    @$xml->loadHtml($html);
    $xml = $xml->saveXml();
    $xml = str_replace('xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/xhtml"',
    'xmlns="http://www.w3.org/1999/xhtml"', $xml);    
    $xml = str_replace('xml:lang="en" lang="en" xml:lang="en"',
    'xml:lang="en" lang="en"',$xml);
    $xml = simplexml_load_string(trim($xml));    
    $xml->registerXpathNamespace('e','http://www.w3.org/1999/xhtml');
    $nodes = $xml->xpath('//e:a');
    foreach($nodes as $node)
    {
        $urls[] = (string) $node['href'];
    }
    return $urls;
}
function fetchAllUrls()
{
    $urls = array();
    $urls['archive']        = getUrlsFromHtml(getUrl('http://alanstorm.com/archives'));
    $urls['magento']        = getUrlsFromHtml(getUrl('http://alanstorm.com/category/magento'));
    $urls['magento-2']      = getUrlsFromHtml(getUrl('http://alanstorm.com/category/magento-2'));    
    $urls['oro']            = getUrlsFromHtml(getUrl('http://alanstorm.com/category/orocrm'));
    $urls['sugarcrm']       = getUrlsFromHtml(getUrl('http://alanstorm.com/category/sugarcrm'));
    $urls['drupal']         = getUrlsFromHtml(getUrl('http://alanstorm.com/category/drupal'));
    $urls['webos']          = getUrlsFromHtml(getUrl('http://alanstorm.com/category/webos'));
    $urls['python']         = getUrlsFromHtml(getUrl('http://alanstorm.com/category/python'));
    $urls['applescript']    = getUrlsFromHtml(getUrl('http://alanstorm.com/category/applescript'));
    $urls['modern_php']     = getUrlsFromHtml(getUrl('http://alanstorm.com/category/modern_php'));
    $urls['laravel']        = getUrlsFromHtml(getUrl('http://alanstorm.com/category/laravel'));
    return $urls;
}

function normalizeUrls($urls)
{
    foreach($urls as $type=>$array)
    {
        foreach($array as $key=>$url)
        {
            $url = rtrim($url,'/');
            $url = str_replace('http://alanstorm.com',      '', $url);
            $url = str_replace('http://www.alanstorm.com',  '', $url);
            $array[$key] = $url;
        }
        
        $urls[$type] = $array;
    }
    return $urls;
    
}

function removeIrrelevantDataFromUrls($urls)
{
    $to_remove = array('/atom','/archives','/project','/contact','/links','/projects',
    '/site/contact','/about'
    );
    
    //URLs I don't want to categorize now
    $to_remove = array_merge($to_remove, array(
        "/commerce-bug-2-5-graphviz",
        "/seo",
        "/bust",
        "/digg",
        "/iphone",
        "/iphone1",
        "/mt4beta",
        "/xsltphp",
        "/Centered",
        "/net_book",
        "/blackbook",
        "/ie8_redux",
        "/mitlaptop",
        "/aspell_osx",
        "/freshmaker",
        "/How_Odd___",
        "/php_market",
        "/why_safari",
        "/ascii_table",
        "/uri_cleanup",
        "/10_4_Upgrade",
        "/bbedit_ctags",
        "/desktoplinux",
        "/laptopchange",
        "/tt4/archives",
        "/why_it_sucks",
        "/recursive_fud",
        "/stackoverflow",
        "/xquery_random",
        "/cut_copy_paste",
        "/macrumors_ajax",
        "/recentprojects",
        "/content_courier",
        "/dot_mac_gallery",
        "/welcome_to_2001",
        "/mod_rewrite_tips",
        "/serversideimages",
        "/ipad_consequences",
        "/secrets_of_design",
        "/whitespace_begone",
        "/too_many_addresses",
        "/aligning_dot_labels",
        "/commerce_bug_paypal",
        "/event_apart_seattle",
        "/nerd_notes_2007_oct",
        "/nothing_to_see_here",
        "/sugarcrm_model_bean",
        "/url_regex_explained",
        "//feeds_working_again",
        "/domdocument_php_stop",
        "/firefox_native_never",
        "/javascript_plus_plus",
        "/macbook_battery_woes",
        "/objective_c_selector",
        "/printing_google_maps",
        "/simple_php_job_queue",
        "/what_what_i_thinking",
        "/parsing_html_with_php",
        "/thinkup_stackexchange",
        "/macworld_2008_thoughts",
        "/web_standards_2008_sep",
        "/Five_Firefox_Extensions",
        "/inbox_fear_and_loathing",
        "/magento_commercebug_1_5",
        "/preemptive_recall_apple",
        "/test_driven_development",
        "/ie_8_standards_whinefest",
        "/install_rhino_javascript",
        "/magento_commerce_bug_two",
        "/more_magento_july18_2011",
        "/jquery_object_literal_oop",
        "/magento_api_helper_manual",
        "/magento_debug_release_1_4",
        "/markdown_hosted_wordpress",
        "/OS_X_10_4_and_transmit_22",
        "/objective_c_selector_part_2",
        "/objective_c_selector_part_3",
        "/objective_c_selector_part_4",
        "/magento_quickies_july_4_2011",
        "/pulse_storm_github_migration",
        "/magento_quickies_june_25_2011",
        "/bbedit_command_line_new_window",
        "/googles_three_new_web_browsers",
        "/interfaces-and-abstract-classes",
        "/magento_quickies_august_22_2011",
        "/javascript_command_line_beautifier",
        "/magento_config_revisited_interlude",
        "/in_depth_magento_dispatch_interlude",
        "/contents_shifting_please_remain_seated",
        "/recently_magento_quickies_august_1_2011",
        "/the_two_futures_for_javascript_libraries",
        "/magento_commerce_bug_session_based_toggles",
        "/setting_up_a_zend_application_in_a_subdirectory",
        "/if_it_aint_broke_make_it_slower_so_we_can_keep_busy", 
        '/methods_objective_c_deeply_weird',
        '/station_identification_2014',
        '/magento_ultimate_module_creator_review',
        '/magento_ultimate_module_creator_review',
        '/an_open_letter_to_magentos_leaders',
        '/magento_2_book_review_theme_web_page_assets',
        '/patreon_for_magento_2_content'
    ));

    foreach($urls as $type=>$array)
    {
        $new = array();
        foreach($array as $key=>$url)
        {            
            if(strpos($url, '/category') === 0)
            {
                continue;
            }
            
            if(strpos($url, 'http') === 0)
            {
                continue;
            }
            
            if(!$url)
            {
                continue;
            }
            
            if(in_array($url, $to_remove))
            {
                continue;
            }
            
            if($url[0] == '#')
            {
                continue;
            }            
            $new[] = $url;
        }
        $urls[$type] = $new;
    }
    return $urls;
}

/**
* One Line Description
*
* @command orphan_content
*/
function pestle_cli($argv)
{

    $urls = fetchAllUrls();
    $urls = normalizeUrls($urls);
    $urls = removeIrrelevantDataFromUrls($urls);

    $urls_archive = $urls['archive'];
    unset($urls['archive']);
    
    $missing = $urls_archive;
    foreach($urls as $key=>$urls)
    {
        $missing = array_diff($missing, $urls);
    }

    \Pulsestorm\Pestle\Library\output("The following array contains your orphan links: ");
    var_dump($missing);

}
}
namespace Pulsestorm\Magento2\Cli\Pandoc_Md{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command pandoc_md
* @argument file Markdown file to convert?
*/
function pestle_cli($argv)
{
    $file = $argv['file'];
    $basename = pathinfo($file)['filename'];
    $exportTo = ['pdf','epub','epub3','html','tex'];
    foreach($exportTo as $ext)
    {
        $cmd = "pandoc $file -s -o output/$basename.$ext";
        $results = `$cmd`;
        \Pulsestorm\Pestle\Library\output($results);
    }
}
}
namespace Pulsestorm\Magento2\Cli\Path_From_Class{
use function Pulsestorm\Pestle\Importer\pestle_import;




function getPathFromClass($class)
{
    $class = trim($class, '\\');
    return \Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir() . '/app/code/' . implode('/', explode('\\', $class)) . '.php';
}

/**
* Short Description
* Long
* Description
* @command path_from_class
*/
function pestle_cli($argv)
{
    $class = \Pulsestorm\Pestle\Library\input('Enter Class: ', 'Pulsestorm\Helloworld\Model\ConfigSourceProductIdentifierMode');
    \Pulsestorm\Pestle\Library\output(getPathFromClass($class));
}
}
namespace Pulsestorm\Magento2\Cli\Pestle_Clear_Cache{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command pestle_clear_cache
*/
function pestle_cli($argv)
{
    $cache_dir = \Pulsestorm\Pestle\Importer\getCacheDir();
    rename($cache_dir, $cache_dir . '.' . time());
    \Pulsestorm\Pestle\Importer\getCacheDir();
}
}
namespace Pulsestorm\Magento2\Cli\Read_Rest_Schema{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command read_rest_schema
* @argument url Base Url? [http://magento-2-with-keys.dev/]
*/
function pestle_cli($argv)
{
    extract($argv);
    $url .= '/rest/default/schema';
    $contents = file_get_contents($url);
    $object = json_decode($contents);    
    var_dump($object);
}
}
namespace Pulsestorm\Magento2\Cli\Search_Controllers{
use function Pulsestorm\Pestle\Importer\pestle_import;








function getAllControllerFiles($base)
{
    $files = glob($base . '/*');
    $controllers = array_filter($files, function($item){
        return is_dir($item . '/Controller/');
    });
    $controllers = array_map(function($item){
        return \Pulsestorm\Phpdotnet\glob_recursive($item . '/Controller/*.php');
    }, $files);    
    
    return $controllers;
}

function getControllersWithExecuteMethod($controllers)
{
    $return = [];
    foreach($controllers as $key=>$items)
    {
        foreach($items as $item)
        {
            $contents = file_get_contents($item);
            if(strpos($contents, 'execute') !== false)
            {
                $return[$item] = $contents;
            }
        }
    }
    
    return $return;

}

function getExecuteMethods($controllers)
{
    foreach($controllers as $file=>$contents)
    {
        $execute = \Pulsestorm\Cli\Token_Parse\getFunctionFromClass($contents, 'execute');
        \Pulsestorm\Pestle\Library\output($file);
        \Pulsestorm\Pestle\Library\output('--------------------------------------------------');
        \Pulsestorm\Pestle\Library\output($execute);
        \Pulsestorm\Pestle\Library\output('');
        
    }
}

/**
* Searches controllers
* @command search_controllers
*/
function pestle_cli($argv)
{
    $base = \Pulsestorm\Pestle\Library\inputOrIndex("Which folder to search?",'vendor/magento',$argv,0);
    $controllers = getAllControllerFiles($base);
    $controllers = getControllersWithExecuteMethod($controllers);
    $controllers = getExecuteMethods($controllers);
}
}
namespace Pulsestorm\Magento2\Cli\Test_Namespace_Integrity{
use function Pulsestorm\Pestle\Importer\pestle_import;



function getPhpModuleFiles()
{
    $files = \Pulsestorm\Cli\Build_Command_List\getListOfFilesInModuleFolder();
    $items = [];
    foreach($files as $name=>$file)
    {
        $info = pathinfo($name);
        if($info['basename'] !== 'module.php') { continue; }
        $items[] = $name;
    } 
    return $items;
}

function parseNamespaceFromString($string)
{
    preg_match('%namespace (.+?);%six',$string, $matches);
    return trim($matches[1]);
}

function parseNamespaceFromFile($file)
{
    return parseNamespaceFromString(file_get_contents($file));
}

function parseCommandFromString($string)
{
    preg_match('%^\*.+?@command(.+?)[\r\n]%mix', $string, $matches);
    return trim($matches[1]);
}

function parseCommandFromFile($file)
{
    return parseCommandFromString(file_get_contents($file));
}

function reportOnNamespaceAndFilepath($namespace, $command, $file)
{
    $parts = explode('/modules/', $file);
    $file_path_ns = str_replace('/module.php', '', array_pop($parts));
    $file_path_ns = str_replace('/','\\',$file_path_ns);
    if(strToLower($file_path_ns) !== strToLower($namespace))
    {
        \Pulsestorm\Pestle\Library\output('--------------------------------------------------');
        \Pulsestorm\Pestle\Library\output($file_path_ns);
        \Pulsestorm\Pestle\Library\output($file);
        \Pulsestorm\Pestle\Library\output($namespace);
        \Pulsestorm\Pestle\Library\output($command);
        \Pulsestorm\Pestle\Library\output('--------------------------------------------------'); 
    }
}

function reportOnNamespaceAndCommandName($namespace, $command, $file)
{
    $parts = explode('\\', $namespace);
    $last_namespace = strToLower(array_pop($parts));
    $second_last_namespace = strToLower(array_pop($parts));
    
    if( ($last_namespace !== $command) && 
        (($second_last_namespace . '_' . $last_namespace) !== $command) &&
        $command !== 'library')
    {        
        \Pulsestorm\Pestle\Library\output('--------------------------------------------------');
        \Pulsestorm\Pestle\Library\output($file);
        \Pulsestorm\Pestle\Library\output($namespace);        
        \Pulsestorm\Pestle\Library\output($last_namespace);
        \Pulsestorm\Pestle\Library\output($command);
        \Pulsestorm\Pestle\Library\output('--------------------------------------------------');    
    }
}

function extractPestleImports($namespace, $command, $file)
{
    $contents = php_strip_whitespace(($file));
    preg_match_all('%pestle_import.*?\((.+?)\).*?;%',$contents, $matches);
    $namespaces_in_file = array_map(function($item) use ($file){        
        $item = str_replace(["'",'"'], '', $item);
        if($item === '$files as $file')
        {
            // exit($file);
        }
        return $item;
    }, $matches[1]);
    
    $namespaces_in_file = array_filter($namespaces_in_file, function($item){
//         return !in_array($item, ['(.+?', '$files as $file','$all_pestle_imports, extractPestleImports($namespace, $command, $file',
//             '$namespace, $command, $file]'
            return 
                (strpos($item, 'pulsestorm') === 0) || 
                (strpos($item, 'Pulsestorm') === 0);
    });
    return $namespaces_in_file;
    // exit($contents);
}

/**
* One Line Description
*
* @command test_namespace_integrity
*/
function pestle_cli($argv)
{
    $files              = getPhpModuleFiles(); 
    $all_pestle_imports = [];  
    foreach($files as $file)
    {
        require_once $file;
        $namespace  = parseNamespaceFromFile($file);
        $command    = parseCommandFromFile($file);
        reportOnNamespaceAndCommandName($namespace, $command, $file);
        reportOnNamespaceAndFilepath($namespace, $command, $file);
        $all_pestle_imports = array_merge($all_pestle_imports, 
            extractPestleImports($namespace, $command, $file));
    }
    $all_pestle_imports = array_unique($all_pestle_imports);
    foreach($all_pestle_imports as $import)
    {
        \Pulsestorm\Pestle\Library\output($import);
        if(!function_exists($import))
        {
            \Pulsestorm\Pestle\Library\output("No such function $import, used in pestle_import somewhere");
        }
        
    }

    \Pulsestorm\Pestle\Library\output("Test Complete");
}
}
namespace Pulsestorm\Magento2\Cli\Test_Output{
use function Pulsestorm\Pestle\Importer\pestle_import;

/**
* One Line Description
*
* @command test_output
*/
function pestle_cli($argv)
{
    output("Hello Sailor");
}

function output()
{
    echo "I am hard coded and here for a test.";
}}
namespace Pulsestorm\Magento2\Cli\Testbed{
use function Pulsestorm\Pestle\Importer\pestle_import;






function getFrontendModelNodesFromMagento1SystemXml($xmls)
{
    $items = [];
    foreach($xmls as $xml_file)
    {        
        $xml = simplexml_load_file($xml_file);
        $items[$xml_file] = [];        
        foreach($xml->sections->children() as $section)
        {
            $strSection = $section->getName();
            foreach($section->groups->children() as $group)
            {
                $strGroup = $group->getName();
                foreach($group->fields->children() as $field)
                {
                    if($field->frontend_model)
                    {
                        $strField = $field->getName();
                        $items[$xml_file][] = implode('/', 
                        [$strSection, $strGroup, $strField]) . '::' . 
                        (string) $field->frontend_model;
                    }
                }
            }
        }        
    }
    
    return $items;
}

function getSectionXmlNodeFromSectionGroupAndField($xml,$section, $group, $field)
{
    $xpath = "/config/system/section[@id='$section']/group[@id='$group']/field[@id='$field']";
    $nodes = $xml->xpath($xpath);
    if(count($nodes) === 0)
    {
        throw new \Exception("Did no find node");
    }
    return array_shift($nodes);
}

function backupOldCode($arguments, $options)
{

    $xmls = [
    ];
    
    $frontend_models = getFrontendModelNodesFromMagento1SystemXml($xmls);

    foreach($frontend_models as $file=>$nodes)
    {
        $new_file = str_replace(
            ['/Users/alanstorm/Sites/magento-1-9-2-2.dev','/local'],
            '', $file);
        $new_file = \Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir() . 
            str_replace('/etc/', '/etc/adminhtml/', $new_file);            
        
        $xml = simplexml_load_file($new_file);
        
        foreach($nodes as $node)
        {
            list($path, $frontend_alias)   = explode('::', $node);
            list($section, $group, $field) = explode('/', $path);
            
            $node = getSectionXmlNodeFromSectionGroupAndField($xml, 
                $section, $group, $field);

            if($node->frontend_model)
            {
                \Pulsestorm\Pestle\Library\output("The frontend_model node already exists: " . $path);
                continue;
            }

            $class = convertAliasToClass($frontend_alias);
            $node->frontend_model = $class;
        }
        
        file_put_contents($new_file, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
    }
    //search XML files
    // $base = getBaseMagentoDir();
    // $files = `find $base -name '*.xml'`;
    // $files = preg_split('%[\r\n]%', $files);  
    // $files = array_filter($files, function($file){
    //     return strpos($file, '/view/') !== false &&
    //     !is_dir($file);    
    // });
    // 
    // $report;
    // foreach($files as $file)
    // {
    //     $xml = simplexml_load_file($file);
    //     if(!$xml->head){ continue; }
    //     output($file);        
    //     foreach($xml->head->children() as $node)
    //     {
    //         output('    ' . $node->getName());
    //     }
    // }
  

}

define('PARSE_FOUND_NAMESPACE', 1);
define('PARSE_FOUND_LINE_END', 2);
define('PARSE_FOUND_USE', 3);
function parseNamespaceFromTokens($tokens)
{
    $state = 0;
    $all   = [];
    $namespace_tokens = [];
    foreach($tokens as $token)
    {
        if($token->token_name === 'T_NAMESPACE')
        {
            $state = PARSE_FOUND_NAMESPACE;
        }
        if($state === PARSE_FOUND_NAMESPACE && $token->token_value !== ';')
        {
            $namespace_tokens[] = $token;
        }
        if($state === PARSE_FOUND_NAMESPACE && $token->token_value === ';')
        {
            $state = PARSE_FOUND_LINE_END;
            $all[] = $namespace_tokens;
            $namespace_tokens = [];
        }        
    }
    return $all;
}

function parseUsesFromTokens($tokens)
{
    $state = 0;
    $all   = [];
    $namespace_tokens = [];
    foreach($tokens as $token)
    {
        if($token->token_name === 'T_USE')
        {
            $state = PARSE_FOUND_USE;
        }
        if($state === PARSE_FOUND_USE && $token->token_value !== ';')
        {
            $namespace_tokens[] = $token;
        }
        if($state === PARSE_FOUND_USE && $token->token_value === ';')
        {
            $state = PARSE_FOUND_LINE_END;
            $all[] = $namespace_tokens;
            $namespace_tokens = [];
        }        
    }
    return $all;
}

function parseClassCodeFromTokens($tokens)
{
    //type hints in functions
    
    //after `new` keyword
    
    //directly before ::
    
    //in all of above, don't forget namespace seperator
    print_r($tokens);
    exit;
}

function parseSetupDiCompileReport()
{
    $contents   = file_get_contents('/Users/alanstorm/Dropbox/Untitled/Notes_2016-01-19_14-49-06');
    $contents   = preg_match_all('%
    Incorrect[ ]dependency[ ]in[ ]class[ ]
    (.+?)
    [ ]in[ ].+?php[\r\n](.+?)\t
    %six', $contents, $matches, PREG_SET_ORDER);
    
    $report = [];
    foreach($matches as $match)
    {
        if(!array_key_exists($match[1], $report))
        {
            $report[$match[1]] = [];
        }
        
        $report[$match[1]] = array_merge($report[$match[1]], preg_split('%[\\r\n]%', $match[2]));
    }
    
    foreach($report as $key=>$errors)
    {
        $errors = array_map(function($error){
            $parts      = explode(' ', $error);
            return array_shift($parts);
        }, $errors);
        
        $errors = array_filter($errors, function($error){
            $error = trim($error);
            return !in_array($error, ['Total', 'Errors']);
        });        
        $report[$key]   = array_unique($errors);
    }
    
    foreach($report as $key=>$errors)
    {
        \Pulsestorm\Pestle\Library\output($key);
        foreach($errors as $error)
        {
            \Pulsestorm\Pestle\Library\output('    ' . $error);
        }
    }
}

function testbedParsing()
{

    // inProgressParsing();
    
    $urls = [
        "http://stackoverflow.com/questions/5412950/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block/5413698",
        "http://stackoverflow.com/questions/5412950/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block",
        "http://topwebseiten.de/mage-news.de",
        "http://www.venchina.com/noticia/venezuela/2014-01-13/141565.html",
        "http://www.venchina.com/noticia/ent/index_181.html",
        "http://www.venchina.com/noticia/china/2013-03-09/136348.html",
        "http://forum.azmagento.com/magento-users-guide/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block-3267.html",
        "http://www.mage-news.de/startseite?page=128",
        "http://www.mage-news.de/englische-news?page=88",
        "http://answerlists.com/question/137218/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block",
        "http://gootomain.com/question/137218/how-would-i-pull-the-content-of-a-cms-page-into-a-static-block",
    ];
    foreach($urls as $url)
    {
        $html = `curl $url`;
        $html = str_replace('>',">\n",$html);
        preg_match_all('%^.*alanstorm\.com.*$%m',$html, $matches);    
        foreach($matches[0] as $match)
        {
            echo $match,"\n";
        }
    }

}

function exampleOfACallback($arguments, $index)
{
    return 'Value of Argument';
}

function getOldToNewClassMap()
{
    $files = [
//         'app/code/Package/Module/Model/System//Config/Backend/Design/Color/Validatetransparent.php',
//         'app/code/Package/Module/Model/System//Config/Source/Category/Grid/Columncount.php',
//         'app/code/Package/Module/Model/System//Config/Source/Css/Background/Attachment.php',
//         'app/code/Package/Module/Model/System//Config/Source/Css/Background/Positionx.php',
//         'app/code/Package/Module/Model/System//Config/Source/Css/Background/Positiony.php',
//         'app/code/Package/Module/Model/System//Config/Source/Css/Background/Repeat.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Font/Family/Google.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Font/Family/Groupcustomgoogle.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Font/Google/Subset.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Font/Size/Basic.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Icon/Color/Bw.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Icon/Color/Bwhover.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Section/Sidepadding.php',
//         'app/code/Package/Module/Model/System//Config/Source/Design/Section/Sidepaddingvalue.php',
//         'app/code/Package/Module/Model/System//Config/Source/Js/Jquery/Easing.php',
//         'app/code/Package/Module/Model/System//Config/Source/Layout/Element/Displayonhover.php',
//         'app/code/Package/Module/Model/System//Config/Source/Layout/Element/Replacewithblock.php',
//         'app/code/Package/Module/Model/System//Config/Source/Layout/Screen/Width/Widecustom.php'

        'app/code/Package/Module/Model/System/Config/Backend/Header/Centralcolunits.php',
        'app/code/Package/Module/Model/System/Config/Backend/Header/Leftcolunits.php',
        'app/code/Package/Module/Model/System/Config/Backend/Header/Rightcolunits.php',
        'app/code/Package/Module/Model/System/Config/Backend/Productpage/Imgcolunits.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Altimagecolumn.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Columncount.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Columncountmobile.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Columncountmobile.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Columncountmobile.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Hovereffect/Below.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Hovereffect/Below.php',
        'app/code/Package/Module/Model/System/Config/Source/Category/Grid/Size.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Design/Tex/Names.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primary.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarymenucontainer.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytop.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytop.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytop.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytopusermenu.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytopusermenuinsidemenu.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytopusermenuinsidemenu.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Position/Primarytopusermenuinsidemenu.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Usermenu/LineBreak/Position.php',
        'app/code/Package/Module/Model/System/Config/Source/Header/Usermenu/Position.php',
        'app/code/Package/Module/Model/System/Config/Source/Navshadow.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Position/All.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Position/All.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Position/All.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Related/Template.php',
        'app/code/Package/Module/Model/System/Config/Source/Product/Tabs/Mode.php',        


        ];

    $classes = array_map(function($item){
        $map = [
            'app/code/'=>'',
            '//'=>'/',
            '/'=>'\\',
            '.php'=>''
        ];
        $item = str_replace(array_keys($map), array_values($map), $item);
        return $item;
    }, $files);        
    
    $old_to_new = [];
    foreach($classes as $class)
    {
        $old_to_new[$class] = str_replace('Package\Module', 'Package\Module', $class);
    }
    return $old_to_new;
}

function classToPath($string)
{
    $map = [
        '\\'=>'/'
    ];
    return 'app/code/' . str_replace(
        array_keys($map), array_values($map), $string) . '.php'; 
}

function movingClasses()
{

    $oldToNew = getOldToNewClassMap();
    $pathSystemXml = 'app/code/Package/Module/etc/adminhtml/system.xml';
    $systemXmlContents    = file_get_contents($pathSystemXml);
    $newSystemXmlContents = $systemXmlContents;
    foreach($oldToNew as $old=>$new)
    {
        $newSystemXmlContents = str_replace($old, $new,$newSystemXmlContents);    
        $old_path = classToPath($old);
        $new_path = classToPath($new); 
        
        //creates directory
        $dir = dirname($new_path);
        if(!is_dir($dir))
        {
            \Pulsestorm\Pestle\Library\output("Creating Dir: " . $dir);
            `mkdir -p $dir`;
        }  
        
        //moves file           
        if(file_exists($old_path))
        {
            \Pulsestorm\Pestle\Library\output("Moving $old_path");
            `mv $old_path $new_path`;
        }
        
        //changes namespace
        if(file_exists($new_path))
        {
            $contents = file_get_contents($new_path);
            $contents_new = preg_replace(
                '%namespace Package\\\Module%',
                 'namespace Package\Module',
                 $contents);
            if($contents_new !== $contents)
            {
                \Pulsestorm\Pestle\Library\output("Rewriting $new_path");
                file_put_contents($new_path, $contents_new);
            }                 
        }                
    }
    if($newSystemXmlContents !== $systemXmlContents)
    {
        \Pulsestorm\Pestle\Library\output("Rewriting $pathSystemXml");
        file_put_contents($pathSystemXml, $newSystemXmlContents);
    }
    \Pulsestorm\Pestle\Library\output("done");              
}

function eavQuery()
{
    $id = 37;
    $tables = [
        'catalog_product_entity_datetime',
        'catalog_product_entity_decimal',
        'catalog_product_entity_int',
        'catalog_product_entity_text',
        'catalog_product_entity_varchar'];
    
    $sql = '';
    foreach($tables as $table)
    {
        $sql .= "
    SELECT eav_attribute.attribute_code, main_table.value_id, main_table.attribute_id, main_table.store_id, main_table.entity_id, value 
    FROM $table main_table  
    LEFT JOIN eav_attribute ON eav_attribute.attribute_id = main_table.attribute_id
    WHERE entity_id IN ($id)        
    UNION
        ";
    }
    
    \Pulsestorm\Pestle\Library\output("\n", $sql);
}

function getDatasourceClass($xml)
{
    $nodes = $xml->xpath('/listing/dataSource//argument[@name="class"]');
    $node = array_shift($nodes);
    return (string) $node;
}

function getFilesArray($folder)
{
    $files = `find $folder -name '*.xml'`;
    $files = preg_split('%[\r\n]%',$files);   
    $new   = [];
    foreach($files as $file)
    {
        $new[$file] = $file;
    } 

    return $new;
}

function loadXmlListingsFiles($files)
{
    $xmls   = array_map(function($file){
        $xml = @simplexml_load_file($file);
        return $xml;
    }, $files);
    $xmls = array_filter($xmls, function($xml){
        if(!$xml) { return false;}
        return $xml->getName() === 'listing';
    });
    return $xmls;
}

function getDataProviderClassesFromListing($xmls)
{
    $return = [];
    foreach($xmls as $file=>$xml)
    {
        $dataProviderClass = getDatasourceClass($xml);
        $return[$file] = $dataProviderClass;
    }
    $return = array_filter($return);
    return $return;
}

function getMaxClassLength($dataProviders)
{
    $max = 0;
    foreach($dataProviders as $file=>$class)
    {
        $length = strlen($class);
        if($length > $max)
        {
            $max = $length;
        }
    }
    return $max;
}

function getUniqueNameOfColumnsChildren($xmls, $columnsSubNode='columns')
{
    foreach($xmls as $file=>$xml)
    {
        $allColumns = $xml->xpath('//'.$columnsSubNode);
        
        foreach($allColumns as $columns)
        {
            foreach($columns->children() as $child)
            {
                $names[] = $child->getName();
            }
        }
    }
    $names = array_filter(array_unique($names), function($item){
        return $item !== 'argument';
    });;
    
    $known          = ['column','selectionsColumn','actionsColumn'];
    sort($known);
    sort($names);  
    if($names !== $known)
    {
        \Pulsestorm\Pestle\Library\output("New column type I don't know about, bailing");
        exit;
    }
    return $names;       
}

function reportDataProviderToListingXmlFileMap($xmls)
{
    // find grid listing => data provider class name mappings            
    $dataProviders  = getDataProviderClassesFromListing($xmls);
    $max            = getMaxClassLength($dataProviders);
    foreach($dataProviders as $file=>$class)
    {
        $indent = str_pad(' ',($max + 5) - strlen($class));
        \Pulsestorm\Pestle\Library\output($class . $indent . basename($file));
    }
}

function bailIfNonDataArgument($columns)
{
    foreach($columns as $column)
    {
        foreach($column->children() as $item)
        {
            if((string) $item['name'] !== 'data' || $item->getName() !== 'argument')
            {
                \Pulsestorm\Pestle\Library\output("A <column/> sub-node that's not a data argument?! Bailing");
                exit;
            }                
        }
    }
}

function getConfigFieldNamesForColumnNodes($xmls, $columnsSubNode='column')
{
    foreach($xmls as $file=>$xml)
    {        
        $columns = $xml->xpath('//' . $columnsSubNode);
        bailIfNonDataArgument($columns);
        
        foreach($columns as $column)
        {            
            foreach($column->argument->children() as $node)
            {
                if(!in_array($node['name'], ['options','config']))
                {
                    var_dump($node->asXml());
                    var_dump(__FUNCTION__);
                    exit;
                }                
                if((string)$node['name'] !== 'config')
                {
                    continue;
                }
                $tmp = [];
                foreach($node->children() as $item)
                {
                    $tmp[] = (string) $item['name'];                    
                }
                sort($tmp);                
                $configs[] = $tmp;
            }            
        }        
    } 
    
    usort($configs, function($a, $b){
        if(count($a) > count($b))
        {
            return 1;
        }
        if(count($a) < count($b))
        {
            return -1;
        }
        return 0;
    });
    
    return $configs;    
}

function reportOnOptionsArgumentAndDataTypes($xmls)
{
    foreach($xmls as $file=>$xml)
    {                
        // output($file);    
        $columns = $xml->xpath('//column');
        bailIfNonDataArgument($columns);
        
        foreach($columns as $column)
        {
            // output($column->asXml());
            // output($column->getName());
            // output($column->getName());
            $doc = simplexml_load_string('<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . $column->asXml() . '</root>');
            $hasOptionsItem = $doc->xpath('//item[@name="options"]');
            if(count($hasOptionsItem) === 0)
            {
                continue;
            }
            
            $dataTypes = $doc->xpath('//item[@name="dataType"]');
            if(count($dataTypes) !== 1)            
            {
                \Pulsestorm\Pestle\Library\output("More than one datatype, bailing");
                var_dump($dataTypes);
                exit;
            }
            $dataType = array_shift($dataTypes);
            $dataType = (string) $dataType;
            
            if($dataType !== 'select')
            {
                \Pulsestorm\Pestle\Library\output($file);
                \Pulsestorm\Pestle\Library\output($dataType);
                \Pulsestorm\Pestle\Library\output($column->asXml());
            }
            
        }
    }     
}

function reportValidateDateComponents($xmls)
{
    foreach($xmls as $file=>$xml)
    {                
        $columns = $xml->xpath('//column');
        // bailIfNonDataArgument($columns);
        
        foreach($columns as $column)
        {
            $doc = simplexml_load_string(
                '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
                $column->asXml() . 
                '</root>');
                
            $dataTypes = $doc->xpath('//item[@name="dataType"]');
            $node      = array_shift($dataTypes);
            if(!$node){ continue;}
            if ( (string)$node !== 'date') { continue;}
            // output($column->asXml());
            
            if((string)$column['class'] !== 'Magento\Ui\Component\Listing\Columns\Date')
            {
                \Pulsestorm\Pestle\Library\output("Date column with incorrect(?) class, bailing");
                exit;
            }
            $components = $doc->xpath('//item[@name="component"]');
            $component  = array_shift($components);
            if(!$component)
            {
                \Pulsestorm\Pestle\Library\output("There's no component configured, bailing");
                exit;
            }
            if((string) $component !== 'Magento_Ui/js/grid/columns/date')
            {
                \Pulsestorm\Pestle\Library\output("There's an incorrect(?) component configured, bailing");
                exit;
            }
            
        }
    } 
}

function reportOnNamedDataConfig($xmls, $name)
{
    foreach($xmls as $file=>$xml)
    {                
        $columns = $xml->xpath('//column/argument/item[@name="config"]/item[@name="'.$name.'"]');
        foreach($columns as $column)
        {
            \Pulsestorm\Pestle\Library\output($column->asXml());
        }
    } 
}

function reportUniqueCombinations($xmls, $uniqueConfigCombinations, $columnsSubNode='column')
{
    foreach($uniqueConfigCombinations as $string)
    {
        \Pulsestorm\Pestle\Library\output('$toCheck="'.$string.'";');
    }
    
    foreach($xmls as $file=>$xml)
    {        
        $columns = $xml->xpath('//'. $columnsSubNode);
        bailIfNonDataArgument($columns);
        
        foreach($columns as $column)
        {            
            foreach($column->argument->children() as $node)
            {
                if(!in_array($node['name'], ['options','config']))
                {
                    var_dump($node->asXml());
                    var_dump(__FUNCTION__);
                    exit;
                }                
                if((string)$node['name'] !== 'config')
                {
                    continue;
                }
                $names = [];
                foreach($node->children() as $item)
                {
                    $names[] = (string) $item['name'];                    
                }
                sort($names); 
                
                //START <columns>
                $toCheck = 'filter,label';                
                $toCheck = 'dataType,label';
                $toCheck = 'label,sortOrder';
                $toCheck = "filter,label,sorting";
                $toCheck="filter,label,visible";
                $toCheck="editor,filter,label";
                $toCheck="bodyTmpl,label,sortable";
                $toCheck="filter,label,sortOrder";
                $toCheck="label,sortOrder,visible";
                // $toCheck="label,sortOrder,sortable";
                // $toCheck="dataType,filter,label";
                // $toCheck="bodyTmpl,label,sortOrder";
                // $toCheck="bodyTmpl,filter,label,visible";
                // $toCheck="component,dataType,filter,label";
                // $toCheck="bodyTmpl,label,sortable,visible";
                // $toCheck="filter,label,sortOrder,sorting";
                $toCheck="add_field,filter,label,sortOrder";
                // $toCheck="editor,filter,label,visible";
                // $toCheck="add_field,label,sortOrder,visible";
                // $toCheck="editor,filter,label,sortOrder";
                // $toCheck="add_field,dataType,label,sortOrder";
                // $toCheck="dataType,filter,label,sortOrder";
                // $toCheck="dataType,label,sortOrder,visible";
                // $toCheck="component,dataType,filter,label,sortOrder";
                // $toCheck="component,dataType,filter,label,sorting";
                // $toCheck="component,dataType,filter,label,visible";
                // $toCheck="add_field,component,dataType,label,sortOrder";
                // $toCheck="editor,filter,label,sortOrder,visible";
                // $toCheck="component,dataType,editor,filter,label";
                // $toCheck="component,dataType,dateFormat,filter,label";
                $toCheck="escape,filter,label,nl2br,sortOrder,truncate";
                // $toCheck="component,dataType,filter,label,sortOrder,visible";
                // $toCheck="component,dataType,editor,filter,label,sortOrder";
                // $toCheck="add_field,component,dataType,filter,label,sortOrder";
                // $toCheck="component,dataType,editor,filter,label,visible";
                $toCheck="add_field,altField,component,has_preview,label,sortOrder,sortable";
                // $toCheck="add_field,component,dataType,filter,label,sortOrder,visible";
                // $toCheck="component,dataType,editor,filter,label,sortOrder,visible";
                $toCheck="add_field,align,altField,component,has_preview,label,sortOrder,sortable";
                $toCheck="component,dataType,dateFormat,editor,filter,label,timezone,visible";
                // $toCheck="component,dataType,dateFormat,filter,label,sortOrder,timezone,visible";
                // END   </columns>
                
                //START <actionsColumn>
                // $toCheck="indexField";
                // $toCheck="indexField,sortOrder";
                // $toCheck="editUrlPath,indexField";
                // $toCheck="indexField,urlEntityParamName,viewUrlPath";
                // $toCheck="indexField,resizeDefaultWidth,resizeEnabled";                
                //END   </actionsColumn>                 
                
                //START <selectionsColumn>                               
                $toCheck="indexField";
                $toCheck="indexField,sortOrder";
                $toCheck="indexField,preserveSelectionsOnFilter,sortOrder";
                $toCheck="indexField,resizeDefaultWidth,resizeEnabled";                
                //END   </selectionsColumn>
                if(implode(',', $names) === $toCheck)
                {
                    \Pulsestorm\Pestle\Library\output($file);
                    \Pulsestorm\Pestle\Library\output($column->asXml());
                    \Pulsestorm\Pestle\Library\output('+--------------------------------------------------+');
                }
            }            
        }        
    } 
}

function getUniqueCombinationsFromConfigs($configs)
{
    $uniqueConfigCombinations = array_values(
        array_unique(array_map(function($item){
            return implode(',', $item);
        }, $configs))
    );
    
    return $uniqueConfigCombinations;
}

function getAllConfigItemsFromConfigs($configs)
{
    $allConfigItems = array_values(array_unique(
        array_reduce($configs, function($carry, $item){
            $carry = $carry ? $carry : [];
            return array_merge($carry, $item);
        })
    ));
    return $allConfigItems;
}

function getUniqueCombinationsFromXmls($xmls, $columnsSubNode)
{
    $configs        = getConfigFieldNamesForColumnNodes($xmls, $columnsSubNode);
    $allConfigItems = getAllConfigItemsFromConfigs($configs);    
    $uniqueConfigCombinations = getUniqueCombinationsFromConfigs($configs);
    return $uniqueConfigCombinations;
}

function whenDidIBuy()
{

    $files = glob('/Users/alanstorm/Desktop/when-did-I-buy/*');
    foreach($files as $file)
    {
        \Pulsestorm\Pestle\Library\output($file);
        $handle = fopen($file, 'r');
        $del    = "\t";
        if(strpos($file, ".TXT") === false)
        {
            $del = ",";
        }
        while($row = fgetcsv($handle, 1024, $del))
        {
            \Pulsestorm\Pestle\Library\output($row[2]);
//             if(count($row) === 0){ continue;}
//             if(!isset($row[2]))
//             {
//                 var_dump($row);
//                 exit;
//             }

//             if(count($row) < 5)
//             {
//                 var_dump($file);
//                 var_dump($row);
//                 exit;
//             }            
        }        
    }
    exit;
}

function randomUiComponentStuff()
{

    $folder         = $arguments['folder'];
    $files          = getFilesArray($folder);
    //* @argument foobar @callback exampleOfACallback    
    $xmls           = loadXmlListingsFiles($files);
    $names          = getUniqueNameOfColumnsChildren($xmls);    
    
    
             
    // reportValidateDateComponents($xmls);     
    // reportOnOptionsArgumentAndDataTypes($xmls);         
    // reportOnNamedDataConfig($xmls, 'component');
    // reportOnNamedDataConfig($xmls, 'filter');
    // reportUniqueCombinations($xmls);
    // var_dump($names);
    
    // $uniqueConfigCombinations = getUniqueCombinationsFromXmls($xmls, 'column');
    // reportUniqueCombinations($xmls, $uniqueConfigCombinations);

    //$uniqueConfigCombinations = getUniqueCombinationsFromXmls($xmls, 'actionsColumn');
    //reportUniqueCombinations($xmls, $uniqueConfigCombinations, 'actionsColumn');
    
    $uniqueConfigCombinations = getUniqueCombinationsFromXmls($xmls, 'selectionsColumn');
    reportUniqueCombinations($xmls, $uniqueConfigCombinations, 'selectionsColumn');    
    exit;
    // var_dump($names);
    
    foreach($xmls as $file=>$xml)
    {
        $nodes = $xml->xpath('//actionsColumn/argument/item[@name="config"]/item');
        $nodes = $xml->xpath('//selectionsColumn/argument/item[@name="config"]/item');
        foreach($nodes as $node)
        {
            \Pulsestorm\Pestle\Library\output((string)$node['name']);            
        }
    } 
    //reportDataProviderToListingXmlFileMap($xmls);    
}

/**
* Test Command
* @command testbed
* @argument folder Which Folder?
*/
function pestle_cli($arguments, $options)
{
    \Pulsestorm\Pestle\Library\output("Hello World");
}
}
namespace Pulsestorm\Magento2\Cli\Xml_Template{
use Exception;

function getBlankXmlModule()
{
    return '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
</config>';

}

function getBlankXmlView()
{
    return '<view xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/view.xsd"></view>';
}

function getBlankXmlAcl()
{
    return '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
    <acl>
        <resources>
            <resource id="Magento_Backend::admin">        
            </resource>
        </resources>
    </acl>
</config>';
}

function getBlankXmlMenu()
{
    return '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Backend:etc/menu.xsd">
    <menu>
    </menu>
</config>';    
}

function getBlankXmlUiGrid()
{
    return '<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd"></listing>';
}

function getBlankXmlTheme()
{
    return '<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/theme.xsd"></theme>';
}

function getBlankXmlRoutes()
{
    $config_attributes = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd"';
    return trim('<?xml version="1.0"?><config '.$config_attributes.'></config>');

}

function getBlankXmlDi()
{
    return '<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
</config>';

}

function getBlankXml($type)
{
    $function = 'Pulsestorm\Magento2\Cli\Xml_Template';
    $function .= '\getBlankXml' . ucWords(strToLower($type));
    if(function_exists($function))
    {        
        return call_user_func($function);
    }
    throw new Exception("No such type, $type ($function)");
}

function getBlankXmlLayout_handle()
{
    return '<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" layout="1column" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
</page>';
}

/**
* Converts Zend Log Level into PSR Log Level
* @command library
*/
function pestle_cli($argv)
{
}    }
namespace Pulsestorm\Nofrills\Build_Book{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command build_book
*/
function pestle_cli($argv)
{
    $files = glob('src/*.md');
    if(count($files) === 0)
    {
        \Pulsestorm\Pestle\Library\output("No src/, bailing");
        exit;
    }
    
    $using = [
        'src/todo.md',
        'src/toc.md',
        'src/chapter0.md',
        'src/chapter1.md',
        'src/chapter2.md',            
        'src/chapter3.md',         
        'src/chapter4.md',         
        'src/chapter5.md',         
        'src/chapter6.md',         
        'src/chapter7.md',         
        'src/chapter8.md',         
        'src/chapter9.md',                                                         
    ];
    
    $raw = [];
    foreach($using as $file)
    {
        if(!in_array($file, $files))
        {
            \Pulsestorm\Pestle\Library\output($file . ' does not exist in src/, bailing');
            exit;
        }
        
        $raw[] = file_get_contents($file);
    }
    
    $raw = implode("\n\n", $raw);
    
    
    file_put_contents('/tmp/working.md', $raw);
    
    echo `mkdir -p output`;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.tex`;
    echo `pandoc output/in-progress-no-frills.tex -s -o output/in-progress-no-frills.pdf `;
    echo `pandoc /tmp/working.md-s -o output/in-progress-no-frills.html `;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.epub`;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.epub3`;                
    
    echo `tar -cvf output/Pulsestorm_Nofrillslayout.tar -C /Users/alanstorm/Sites/magento-2-1-0.dev/project-community-edition app/code/Pulsestorm/Nofrillslayout`;
    $date = date('Y-m-d-H-i-s',time());
    $zip = $date . '.zip';
    echo `zip -r $zip output`;
    
}
}
namespace Pulsestorm\Paypal\Csv_To_Iif{
use function Pulsestorm\Pestle\Importer\pestle_import;

use function extract;
use Exception;

function getProcessFunctionFromFirstLine($line)
{
    $line = array_map('trim', $line);
    if((strpos($line[0],'Name') !== false) && $line[1] === 'Storm, Alan')
    {
        return __NAMESPACE__ . '\processPaypal';
    }
    throw new Exception("Unknown Process Function");
}

function joinHeadersAndValue($headers, $values)
{
    if(count($headers) != count($values))
    {
        throw new Exception("Header and value coutn don't match");
    }
    
    $new = [];
    for($i=0;$i<count($headers);$i++)
    {
        $new[($headers[$i])] = $values[$i];
    }
    return $new;
}

function processPaypal($line)
{
    static $headers;
    $to_skip = ['Name','Email','Payer ID','Report Date','Available Balance'];
    foreach($to_skip as $key)
    {
        if((strpos($line[0],$key) !== false))
        {
            return;
        }    
    }
    if(!$headers && $line[0] === 'Date')
    {
        $headers = $line;
        return;
    }
    $row = joinHeadersAndValue($headers, $line);
    if(strpos($row['Type'],'Transfer to Bank') !== false)
    {
        return null;
    }
    $iif      = getIifTemplate();
    $iif      = str_replace('<$date$>',         $row['Date'],       $iif);
    $iif      = str_replace('<$entity$>',       $row['Name'],       $iif);
    $iif      = str_replace('<$amount$>',       trim($row['Net']),        $iif);
    
    $product_title = $row['Item Title'];
    if(!$product_title)
    {
        $product_title = $row['Subject'];
    }
    
    //dupe "no title" behavior
    if($product_title)
    {
        $iif      = str_replace('<$product_name$>', $product_title, $iif);
    }
    else
    {
        $iif      = str_replace('"<$product_name$>"' . "\t", '', $iif);
    }
    
    $iif      = str_replace('<$amount_full$>',  number_format(($row['Gross'] * -1),2), $iif);    
    $iif      = str_replace('<$amount_fee$>',   number_format(($row['Fee'] * -1),2), $iif);        

    if((int) $row['Fee'] === 0)
    {
        $parts = preg_split('%[\r\n]{1,2}%', $iif);
        $parts = array_filter($parts, function($item){
            return strpos($item, '"Bank Fee"') === false;
        });
        
        $iif = implode("\n",$parts);
        if(strpos($iif, '"Bank Fee"') === false)
        {
            $iif = str_replace('Express Checkout Payment Received', 'Payment Received', $iif);
        }                 
    }
    return $iif;    
}

function getIifTemplate()
{
    $template = 'TRNS	"<$date$>"	"Paypal"	"<$entity$>"	"Express Checkout Payment Received"	<$amount$>	"<$product_name$>"	
SPL	"<$date$>"	"Sales-Software"	"<$entity$>"	<$amount_full$>
SPL	"<$date$>"	"Bank Fee"	Fee	<$amount_fee$>
ENDTRNS';
    return $template;
}

/**
* One Line Description
*
* @command csv_to_iif
* @argument path_to_file CSV File
*/
function pestle_cli($argv)
{
    extract($argv);
    $handle = fopen($path_to_file, 'r');
    $process_function = false;
    $iifs = [];
    while($line = fgetcsv($handle))
    {
        if(!$process_function)
        {
            $process_function = getProcessFunctionFromFirstLine($line);
        }        
        $iifs[] = call_user_func($process_function, $line);
    }
    $iifs = array_filter($iifs);
    $iifs = array_reverse($iifs);
    \Pulsestorm\Pestle\Library\output('!TRNS	DATE	ACCNT	NAME	CLASS	AMOUNT	MEMO
!SPL	DATE	ACCNT	NAME	AMOUNT	MEMO
!ENDTRNS');
    \Pulsestorm\Pestle\Library\output(implode("\n", $iifs));
}
}
namespace Pulsestorm\Pestle\Library{
use ReflectionFunction;
use ReflectionClass;

function getShortClassNameFromClass($class)
{
    $parts = explode('\\', $class);
    return array_pop($parts);
}

function parseDocCommentAts($r)
{
    $comment = $r->getDocComment();
    $comment = preg_replace(['%^\*/%m', '%^/\*\*%m','%^\* %m','%^\*%m'], '', $comment);    
    $parts   = explode('@', $comment);
    array_shift($parts);
    $parsed  = [];
    foreach($parts as $part)
    {
        $part = trim($part);
        $parts2 = preg_split('%\s%', $part);
        $name   = array_shift($parts2);
        $parsed[$name] = implode('',$parts2);
    }
    $parsed = array_map(function($thing){
        return trim($thing);
    }, $parsed);
    
    return $parsed;
}

function getNewClassDeclaration($class, $extends, $include_start_bracket=true)
{
    $parts = [];
    $parts[] = 'namespace';
    $parts[] = $class['namespace'] . ';';
    $parts[] = "\n";

    if($extends['class'])
    {
        $parts[] = 'use';
        if($extends['class'] === 'Abstract')
        {
            $parts[] = $extends['full_class'] . ' as AbstractClass;';
            $extends['class'] = 'AbstractClass';
        }
        else
        {
            $parts[] = $extends['full_class'] .';';
        }
    }
        
    $parts[] = "\n";    
    
    $parts[] = 'class';
    $parts[] = $class['class'];
    if($extends['class'])
    {
        $parts[] = 'extends';    
        $parts[] = $extends['class'];      
    }
    $parts[] = "\n";    
    if($include_start_bracket)
    {
        $parts[] = "{";       
    }
    
    return preg_replace('%^ *%m', '', implode(' ', $parts));
}

function getClassFromDeclaration($class)
{
    return getPartFromDeclaration($class, 'class');
}

function getExtendsFromDeclaration($class)
{
    return getPartFromDeclaration($class, 'extends');
}

function getPartFromDeclaration($class, $part)
{
    $flag = false;
    foreach(explode(' ', $class) as $item)
    {
        if($flag)
        {
            return $item;
        }
        if($item === $part)
        {
            $flag = true;
        }
    }
    return null;
}

function writeStringToFile($path, $contents)
{
    if(!is_dir(dirname($path)))
    {
        mkdir(dirname($path),0755,true);
    }
    $path_backup = $path . '.' . uniqid() . '.bak.php';
    if(file_exists($path))
    {
        copy($path, $path_backup);
    }
    file_put_contents($path, $contents);
    return $path;
}

function bail($message)
{
    output($message);
    exit(1);
}

function getDocCommentAsString($function)
{
    $r = new ReflectionFunction($function);
    $lines = explode("\n", $r->getDocComment());
    
    if(count($lines) > 2)
    {
        array_shift($lines);
        array_pop($lines);
        $lines = array_map(function($line){
            return trim(trim($line),"* ");
        }, $lines);
    }
    
    return trim( implode("\n", $lines) );
}

function isAboveRoot($path)
{
    $parts = explode('..', $path);
    $real  = array_shift($parts);
    $parts_real = explode('/',$real);
    array_unshift($parts, '/');
    $parts      = array_filter($parts,      __NAMESPACE__ . '\notEmpty');
    $parts_real = array_filter($parts_real, __NAMESPACE__ . '\notEmpty');
    
    return count($parts) > count($parts_real);
}

function notEmpty($item)
{
    return (boolean) $item;
}

function inputRawPhp()
{    
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);        
    fclose($handle);
    return $line;
}

function inputReadline()
{
    return readline();
}

function input($string, $default='')
{
    echo $string . " (".$default.")] ";
    if(!function_exists('readline'))
    {
        $line = inputRawPhp();
    }
    else
    {
        $line = inputReadline();
    }
    if(trim($line))
    {
        return trim($line);
    }
    return $default;
    
}

function inputOrIndex($question, $default, $argv, $index)
{
    if(array_key_exists($index, $argv))
    {
        return $argv[$index];
    }
    
    return input($question, $default);
}

function getAtCommandFromDocComment($r)
{
    $props = parseDocCommentAts($r);
    if(array_key_exists('command', $props))
    {
        return $props['command'];
    }
    return null;
}

function output($string)
{
    foreach(func_get_args() as $arg)
    {
        if(is_object($string) || is_array($string))
        {
            var_dump($string);
            continue;
        }
        echo $arg;
    }    
    echo "\n";
}


/* moved stuff above this line */

function isOption($string)
{
    if(strlen($string) > 2 && $string[0] === '-' && $string[0] === '-')
    {
        return true;
    }
    return false;
}

function cleanDocBlockLine($line)
{
    $parts = explode('*', $line);
    array_shift($parts);
    $line = implode('', $parts);
    return trim($line);
}

function parseDocBlockIntoParts($string)
{
    $return = [
        'one-line'      => '',
        'description'   => '',      
    ];
    
    $lines = preg_split('%[\r\n]%', $string);
    $start_block = trim(array_shift($lines));
    if($start_block !== '/**')
    {
        return $return;
    }
    
    while($line = array_shift($lines))
    {
        $line = cleanDocBlockLine($line);
        if($line && $line[0] === '@')
        {
            array_unshift($lines, $line);
            break;
        }
        if(!$line) { continue;}
        
        if(!$return['one-line'])
        {
            $return['one-line'] = $line;
        }
        else
        {
            $return['description'] .= $line . ' ';
        }
    }
    $return['description'] = trim($return['description']);

    $all = implode("\n",$lines);
    preg_match_all('%^.*?@([a-z0-1]+?)[ ](.+?$)%mix', $all, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {        
        $return[$match[1]][] = trim($match[2]);
    }
    return $return;
}

function parseArgvIntoCommandAndArgumentsAndOptions($argv)
{
    $script  = array_shift($argv);
    $command = array_shift($argv);
     
    $arguments = [];
    $options   = [];
    $length = count($argv);
    for($i=0;$i<$length;$i++)
    {
        $arg = $argv[$i];
        if(isOption($arg))
        {
            $option = str_replace('--', '', $arg);
            
            if(preg_match('%=$%', $option))
            {
                $option = substr($option, 0, 
                    strlen($option)-1);
                $option_value = $argv[$i+1];                    
                $i++;                    
            }
            else if(preg_match('%=.%', $option))
            {   
                list($option, $option_value) = explode('=', $option, 2);
            }
            else
            {
                $option_value = '';
                if(array_key_exists($i+1, $argv))
                {
                    $option_value = $argv[$i+1];
                }
                $i++;                
            }
            
            
            $options[$option] = $option_value;
            
        }
        else
        {
            $arguments[] = $arg;
        }
    }
    
    return [
        'command'   => $command,
        'arguments' => $arguments,
        'options'   => $options
    ];
}

/**
* @command library
*/
function pestle_cli($argv)
{
    
}}
namespace Pulsestorm\Pestle\Runfile\Run_File{
/**
* One Line Description
*
* @command pestle_run_file
* @argument file Run which file?
*/
function pestle_cli($argv)
{
    require_once($argv['file']);
}
}
namespace Pulsestorm\Phpdotnet{
/**
* Function found on php.net.  
* @copyright original authors
*/


/**
* @command library
*/
function pestle_cli($argv)
{
}

if ( ! function_exists('glob_recursive'))
{

    /**
    * Does not support flag GLOB_BRACE
    * http://php.net/manual/en/function.glob.php#106595
    */    
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        
        return $files;
    }
}
}
namespace Pulsestorm\Wordpress\Export_Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;


class PestleXmlElement extends \SimpleXMLElement
{
    public function setText($text)
    {
        $this[0]   = $text;
    }
    
    public function addChild($name, $value=null, $namespace=null )
    {        
        if(strpos($value, '<![CDATA[') !== 0)
        {
            return parent::addChild($name, $value, $namespace);
        }
        
        $value = substr($value, 9);
        $value = substr($value, 0, strlen($value) -3);
        $child = parent::addChild($name, null, $namespace);
        
        $node = dom_import_simplexml($child); 
        $no   = $node->ownerDocument; 
        $node->appendChild($no->createCDATASection($value));         
    }
}

function getInitialNode()
{
    $xml                  = new PestleXmlElement('<rss/>');

    $xml['version']       = '2.0';
    $xml['xmlns:excerpt'] = 'http://wordpress.org/export/1.2/excerpt/';
    $xml['xmlns:content'] = 'http://purl.org/rss/1.0/modules/content/';
    $xml['xmlns:wfw']     = 'http://wellformedweb.org/CommentAPI/';
    $xml['xmlns:dc']      = 'http://purl.org/dc/elements/1.1/';
    $xml['xmlns:wp']      = 'http://wordpress.org/export/1.2/'; 
    return $xml;
}

function createItem($xml)
{
    $item                 = $xml->addChild('item');
    $title                = $item->addChild('title', 'This is a test');
    $link                 = $item->addChild('link','');
    $pubDate              = $item->addChild('pubDate','');
    $dc_creator           = $xml->addChild('dc:creator', '<![CDATA[astorm]]>');
    $guid                 = $item->addChild('guid','');
    $guid['isPermaLink']  ="false";
    $description          = $item->addChild('description','');
    $content_encoded      = $item->addChild('content:encoded','');
    $excerpt_encoded      = $item->addChild('excerpt:encoded','');
    $wp_post_id           = $item->addChild('wp:post_id','');
    $wp_post_date         = $item->addChild('wp:post_date','');
    $wp_post_date_gmt     = $item->addChild('wp:post_date_gmt','');
    $wp_comment_status    = $item->addChild('wp:comment_status','');
    $wp_ping_status       = $item->addChild('wp:ping_status','');
    $wp_post_name         = $item->addChild('wp:post_name','');
    $wp_status            = $item->addChild('wp:status','');
    $wp_post_parent       = $item->addChild('wp:post_parent','');
    $wp_menu_order        = $item->addChild('wp:menu_order','');
    $wp_post_type         = $item->addChild('wp:post_type','');
    $wp_post_password     = $item->addChild('wp:post_password','');
    $wp_is_sticky         = $item->addChild('wp:is_sticky','');
    $wp_postmeta          = $item->addChild('wp:postmeta','');
    $wp_meta_key          = $item->addChild('wp:meta_key','');
    $wp_meta_value        = $item->addChild('wp:meta_value','');
    $wp_postmeta          = $item->addChild('wp:postmeta',''); 
    return $item; 
}

/**
* @command wp_export_xml
*/
function pestle_cli($argv)
{
    $xml  = getInitialNode();
    $item = createItem($xml);		    
    \Pulsestorm\Pestle\Library\output(
        \Pulsestorm\Xml_Library\formatXmlString(
            $xml->asXml()
        )
    );
}}
namespace Pulsestorm\Xml_Library{
use DomDocument;
/**
* @command library
*/
function pestle_cli($argv)
{
    
}

function addSchemaToXmlString($xmlString, $schema=false)
{
    $schema = $schema ? $schema : 
        'urn:magento:framework:Module/etc/module.xsd';
        
    $xml = str_replace(
        '<config>',
        '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="'.$schema.'">', 
        $xmlString);
    return $xml;
}

function getXmlNamespaceFromPrefix($xml, $prefix)
{
    $namespaces = $xml->getDocNamespaces();
    if(array_key_exists($prefix, $namespaces))
    {
        return $namespaces[$prefix];
    }

    throw new Exception("Unkonwn namespace in " . __FILE__);
}

function simpleXmlAddNodesXpath($xml, $path)
{
    $path = trim($path,'/');
    $node = $xml;
    foreach(explode('/',$path) as $part)
    {
        $parts = explode('[', $part);
        $node_name = array_shift($parts);
        $is_new_node = true;
        if(isset($node->{$node_name}))
        {
            $is_new_node = false;
            $node = $node->{$node_name};        
        }
        else
        {
            $node = $node->addChild($node_name);
        }
        
        
        $attribute_string = trim(array_pop($parts),']');
        if(!$attribute_string) { continue; }
        $pairs = explode(',',$attribute_string);
        foreach($pairs as $pair)
        {
            if(!$is_new_node) { continue; }
            list($key,$value) = explode('=',$pair);
            if(strpos($key, '@') !== 0)
            {
                throw new Exception("Invalid Attribute Key");
            }
            $key = trim($key, '@');
            if(strpos($key, ':') !== false)
            {                
                list($namespace_prefix, $rest) = explode(':', $key);
                $namespace = getXmlNamespaceFromPrefix($xml, $namespace_prefix);
                $node->addAttribute($key, $value, $namespace);
            }
            else
            {
                $node->addAttribute($key, $value);
            }
            
        }
//         exit;
    }
    return $node;
}

function formatXmlString($string)
{
    $dom = new DomDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;        
    $dom->loadXml($string);
    $string = $dom->saveXml();
    
    $string = preg_replace('%(^\s*)%m', '$1$1', $string);
    
    return $string;
}}
