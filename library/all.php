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
* Builds the command list
* @command pestle:build-command-list
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
    
    if(strpos($command_name,':') !== false)
    {
        $parts = explode(':', $command_name);        
        $post_fix = implode(' ', $parts);
        $post_fix = ucwords($post_fix);
        $post_fix = str_replace(' ', '\\', $post_fix);
        $command_name = $post_fix;
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

function generateInstallSchemaTable($table_name='', $columns=[], $comment='',$indent=false)
{
    $indent = $indent ? $indent : '        ';
    $block = $indent . '$table = ' . generateInstallSchemaNewTable($table_name);
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
    $indent . '$installer->getConnection()->createTable($table);';
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
    $body = "\n";
    if($area === 'adminhtml')
    {
        $body .= '    
    const ADMIN_RESOURCE = \''.$acl.'\';       
        ';        
    }    
    $body .= '
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
//     if($area === 'adminhtml')
//     {
//         $body .= '    
//     protected function _isAllowed()
//     {
//         return $this->_authorization->isAllowed(\''.$acl.'\');
//     }            
//         ';        
//     }
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
* ALPHA: Experiments with a PHP formatter.
*
* @command php:format-php
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
* @command pulsestorm:md-to-say
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
namespace Pulsestorm\Cli\Monty_Hall_Problem{
use function Pulsestorm\Pestle\Importer\pestle_import;


function doorMontyReveals($winningDoor, $doorChosen)
{
    $doors        = [1=>1,2=>2,3=>3];
    unset($doors[$winningDoor]);
    unset($doors[$doorChosen]);
    return array_pop($doors);
}

function switchDoor($doorChosen, $montysDoor)
{
    if($doorChosen === $montysDoor)
    {
        exit("Error at " . __LINE__);
    }
    $doors        = [1=>1,2=>2,3=>3];
    unset($doors[$doorChosen]);
    unset($doors[$montysDoor]);
    return array_pop($doors);    
}

function vaidateStrategyAndShouldWeKeepOurDoor($strategy)
{
    $keepDoor = $strategy === 'keep_door'   ? true : false;
    if(!$keepDoor && $strategy !== 'change_door'){
        \Pulsestorm\Pestle\Library\output("Unknown Strategy Chosen");
        exit;
    }
    return $keepDoor;
}

function runStrategy($keepDoor, $doorChosen, $montysDoor, $strategy)
{
    if($keepDoor)
    {
        \Pulsestorm\Pestle\Library\output("You keep your door:             $doorChosen");
    }
    else
    {
        $doorChosen = switchDoor($doorChosen, $montysDoor);
        \Pulsestorm\Pestle\Library\output("You changed to door:            $doorChosen");
    }
    return $doorChosen;
}

/**
 * Runs end game state
 * @return boolean true if we own, false if we lost
 */
function runEndGame($winningDoor, $doorChosen)
{
    \Pulsestorm\Pestle\Library\output("The Winning Door:               $winningDoor");            
    //return true if won, false if lost
    if(($winningDoor === $doorChosen))
    {
        \Pulsestorm\Pestle\Library\output("You Win!");
        return true;
    }
    \Pulsestorm\Pestle\Library\output("You Lose!");
    return false;
}

function getStartingGameState()
{
    $start = [
        rand(1,3),  //'winningDoor'=>
        rand(1,3),  //'doorChosen' =>
    ];
    $start[] = doorMontyReveals(
        $start[0], $start[1]);
        
    return $start;        
}

function runGame($argv, $keepDoor)
{
    //game start
    list($winningDoor, $doorChosen, $montysDoor) = getStartingGameState();
    \Pulsestorm\Pestle\Library\output("You have chosen door:           $doorChosen");
    \Pulsestorm\Pestle\Library\output("Monty reveals the zonk door:   $montysDoor");
    
    //change or keep your door
    $doorChosen = runStrategy($keepDoor, $doorChosen, $montysDoor, $argv['strategy']);
    
    //run game end state, get won/loss
    $won = runEndGame($winningDoor, $doorChosen);
    \Pulsestorm\Pestle\Library\output('');
    return $won;
}

function outputResults($results)
{
    \Pulsestorm\Pestle\Library\output("Times Won:  " . $results['win']);
    \Pulsestorm\Pestle\Library\output("Times Lost: " . $results['lose']);
}

function runSimulation($argv, $results, $keepDoor, $times)
{
    for($i=0;$i<$times;$i++)
    {
        $won    = runGame($argv, $keepDoor);
        if($won)
        {
            $results['win']++;
            continue;
        }
        $results['lose']++;
    }        
    return $results;
}

/**
* Runs Simulation of "Monty Hall Problem"
*
* You have three doors.  One has a prize behind it.  The other
* two have no prizes behind it.  You pick a door.  The game 
* show host, Monty Hall, shows you that one of the remaining 
* doors has no prize behind it.  
* 
* Should you switch doors?
*
* Assumes there's only one winning door, and that Monty will always
* reveal a zonk door.  Also, The **New** Lets Make a Deal from the 80s (the 
* one I'm familiar with would sometimes change this up with a 
* "medium prize" door.  Also assumes that the door picking is completely 
* random, and that show producers aren't using cold reading or "door forcing" 
* techniques on the contestants.  Also assumes the producers had no access
* to the contestant to tell them which doors to pick or to not pick.  
*
* @command pulsestorm:monty-hall-problem
* @argument strategy Which Strategy (keep_door|change_door)? [keep_door]
* @argument times Run Game N Times [10000]
*/
function pestle_cli($argv)
{    
    $results = [
        'win'=>0,
        'lose'=>0,
    ];
    
    $keepDoor   = vaidateStrategyAndShouldWeKeepOurDoor($argv['strategy']);
    $times      = (int) $argv['times'];
    
    $results = runSimulation($argv, $results, $keepDoor, $times);
    outputResults($results);
}
}
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
* Updates the pestle.phar file to the latest version
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
    
    \Pulsestorm\Pestle\Library\output("Replaced $localPharPath");
    rename($tmpFile, $localPharPath);
    
    chmod($localPharPath, octdec($permissions));
}}
namespace Pulsestorm\Cli\Tax_Estimate{
use function Pulsestorm\Pestle\Importer\pestle_import;


function getFederalBrackets()
{
    //single filer, 2016
    //key order signifigant, lowest to highest
    return [
        '10'    =>'9275',    
        '15'    =>'37650',
        '25'    =>'91150',
        '28'    =>'190150',
        '33'    =>'413350',
        '35'    =>'415050',
        '39.6'  =>'415051',                                                
    ];

}

function getOregonBrackets()
{
    //oregon, single filer, 2016
    //key order signifigant, lowest to highest
    return [
        '5'    =>'3350',    
        '7'    =>'8400',
        '9'    =>'125000',
        '9.9'    =>'125001',
    ];
}

function calculateTaxWithBrackets($taxable, $brackets)
{
    $top      = false;
    $extra    = 0;
    //find top bracket
    $lastAmount = 0;
    foreach($brackets as $percent=>$amount)
    {
        if($taxable < $amount)
        {
            $top   = $percent;
            $extra = $taxable - $lastAmount;
            break;
        }
        $lastAmount = $amount;
    }

    //filter out non-applicable brackets
    $ourBrackets = [];
    foreach($brackets as $percent=>$amount)
    {
        // echo $top, '>=', $percent,"\n";
        if($percent < $top)
        {
            $ourBrackets[$percent] = $amount;
        }
    }    


    \Pulsestorm\Pestle\Library\output("\$taxes = $extra * ($top/100.00)");
    $taxes = $extra * ($top/100.00);
    
    $lastAmount = 0;
    foreach($ourBrackets as $percent=>$amount)
    {
        $amount = $amount - $lastAmount;
        \Pulsestorm\Pestle\Library\output("\$taxes += $amount * ($percent/100.00)"); 
        $taxes += $amount * ($percent/100.00);
               
        $lastAmount = $amount;
    }
    
    // output($top);
    // output($brackets);    
    // output($ourBrackets);
    \Pulsestorm\Pestle\Library\output("Tax: " .  $taxes);
}

/**
* BETA: Does some back of the electronic napkin tax calculations
*
* @command pulsestorm:tax-estimate
* @argument taxable Taxable, (not gross) income? [80000]
*/
function pestle_cli($argv)
{
    $taxable = $argv['taxable'];
    
    \Pulsestorm\Pestle\Library\output("On $taxable taxable income");

    calculateTaxWithBrackets($taxable, getFederalBrackets());
    calculateTaxWithBrackets($taxable, getOregonBrackets());
}
}
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
* BETA: Parses Citicard's CSV files into yaml
*
* @command parsing:citicard
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
namespace Pulsestorm\Generate\Pestle\Command{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates pestle command boiler plate
* This command creates the necessary files 
* for a pestle command
*
*     pestle.phar pestle:generate_command command_name
*
* @command pestle:generate-command
* @argument command_name New Command Name? [foo_bar]
* @argument namespace_module Create in PHP Namespace? [Pulsestorm\Magento2\Cli]
*/
function pestle_cli($argv, $options)
{
    return \Pulsestorm\Magento2\Cli\Generate\Mage2_Command\pestle_cli_exported($argv, $options);
}
}
namespace Pulsestorm\Magento2\Cli\Baz_Bar{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* Another Hello World we can probably discard
*
* @command pestle:baz-bar
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
* Scans modules for ACL rule ids, makes sure they're all used/defined
*
* @command magento2:scan:acl-used
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
* BETA: Scans a Magento 2 module for misnamed PHP classes
* @command magento2:scan:class-and-namespace
* @argument folder Which Folder? 
*/
function pestle_cli($argv)
{    
    // $path = inputOrIndex('Which folder?','/path/to/magento/app/code/Pulsestorm',$argv, 0);
    $path = $argv['folder'];
    
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
            \Pulsestorm\Pestle\Library\output("No Namespace: Skipping $file");
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
* ALPHA: Checks for missing Magento 2 HTACCESS files from a hard coded list
* @command magento2:scan:htaccess
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
* Scans Magento 2 directories for missing registration.php files
* Long
* Description
* @command magento2:scan:registration
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
* @command magento2:check-templates
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
* Turns a Magento file path into a PHP class
* Long
* Description
* @command magento2:class-from-path
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
* ALPHA: Partially converts Magento 1 class to Magento 2
* Long
* Description
* @command magento2:convert-class
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
* ALPHA: Partially converts Magento 1 config.xml to Magento 2
* Long
* Description
* @command magento2:convert-observers-xml
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
* @command codecept:convert-selenium-id-for-codecept
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
* ALPHA: Partially Converts Magento 1 system.xml into Magento 2 system.xml
* @command magento2:convert-system-xml
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
* Another Hello World we can probably discard
* @command pestle:dev-import
*/
function pestle_cli($argv)
{
    \Pulsestorm\Pestle\Library\output("test");
}}
namespace Pulsestorm\Magento2\Cli\Dev_Namespace{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* BETA: Used to move old pestle files to module.php -- still needed?
* @command pestle:dev-namespace
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
* ALPHA: Seems to be a start at exporting a pestle module as functions. 
* @command pestle:export-module
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
* @command magento2:extract-mage2-system-xml-paths
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
* ALPHA: Extracts data from a saved PHP session file
* @command php:extract-session
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
* ALPHA: Fixes direct use of PHP Object Manager
* argument foobar @callback exampleOfACallback
* @command magento2:fix-direct-om
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
* ALPHA: "Fixes" permissions for development boxes
* running mod_php by setting things to 777. 
* @command magento2:fix-permissions-modphp
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
* ALPHA: Another Hello World we can probably discard
*
* @command pestle:foo-bar
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
* Generates Magento 2 acl.xml
*
* Wrapped by magento2:foo:baz version of command
*
* @command generate-acl
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

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
namespace Pulsestorm\Magento2\Cli\Generate\Command{
use function Pulsestorm\Pestle\Importer\pestle_import;
use Exception;










function createPhpClass($module_dir, $namespace, $module_name, $command_name)
{
    $class_file_string = \Pulsestorm\Cli\Code_Generation\templateCommandClass($namespace, $module_name, $command_name);

    if(!is_dir($module_dir . '/Command'))
    {
        mkdir($module_dir . '/Command',0755,true);
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
*   pestle.phar generate_command Pulsestorm_Generate Example
* 
* Creates
* app/code/Pulsestorm/Generate/Command/Example.php
* app/code/Pulsestorm/Generate/etc/di.xml
*
* @command generate-command
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
        $xml_type_commandlist          = $xml_di->addChild('type');
        $xml_type_commandlist['name']  = 'Magento\Framework\Console\CommandList';        
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

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
namespace Pulsestorm\Magento2\Cli\Generate\Config_Helper{
use function Pulsestorm\Pestle\Importer\pestle_import;
use Exception;



/**
* Generates a help class for reading Magento's configuration
*
* This command will generate the necessary files and configuration 
* needed for reading Magento 2's configuration values.
* 
* @command generate-config-helper
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
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
namespace Pulsestorm\Magento2\Cli\Generate\Crud\Model{
use function Pulsestorm\Pestle\Importer\pestle_import;
















define('BASE_COLLECTION_CLASS'  , '\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection');
define('BASE_RESOURCE_CLASS'    , '\Magento\Framework\Model\ResourceModel\Db\AbstractDb');
define('BASE_MODEL_CLASS'       , '\Magento\Framework\Model\AbstractModel');

function getCollectionClassNameFromModuleInfo($moduleInfo, $modelName)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\Model\ResourceModel\\' . $modelName . '\Collection';
}

function getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\Model\ResourceModel\\' . $modelName;
}

function getModelClassNameFromModuleInfo($moduleInfo, $modelName)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\Model\\' . $modelName;
}

function templateUpgradeDataFunction()
{
    return "\n" . '    public function upgrade(\Magento\Framework\Setup\ModuleDataSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        //install data here
    }' . "\n";

}

function templateInstallDataFunction()
{
    return "\n" . '    public function install(\Magento\Framework\Setup\ModuleDataSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        //install data here
    }' . "\n";
}

function templateUpgradeFunction()
{
    return "\n" . '    public function upgrade(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        //START: install stuff
        //END:   install stuff
        $installer->endSetup();
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

function createRepositoryInterfaceContents($moduleInfo, $modelName, $interface)
{
    $modelInterface             = getModelInterfaceShortName($modelName);
    $modelInterfaceLongName     = getModelInterfaceName($moduleInfo, $modelName);
    
    $contents                   = \Pulsestorm\Cli\Code_Generation\templateInterface($interface,[]);   
    $contentsAbstractFunctions  = templateRepositoryInterfaceAbstractFunction($modelInterface);
    $contentsUse                = templateRepositoryInterfaceUse($modelInterfaceLongName);
    
    $contents = templateComplexInterface($contentsUse, $contentsAbstractFunctions, $contents);
    
    return $contents;
}

function getModelRepositoryName($modelName)
{
    return $modelName . 'Repository';    
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
        catch(\Exception $e)
        {
            throw new CouldNotSaveException(__($e->getMessage()));
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
        } catch (\Exception $exception) {
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

function createRepository($moduleInfo, $modelName)
{
    $classCollection    = getCollectionClassNameFromModuleInfo($moduleInfo, $modelName);
    $classModel         = getModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $modelInterface     = getModelInterfaceName($moduleInfo, $modelName);
    $repositoryName     = getModelRepositoryName($modelName);
    $repositoryFullName = getModelClassNameFromModuleInfo($moduleInfo, $repositoryName);
    $interface          = getModuleInterfaceName($moduleInfo, $repositoryName, 'Api');    
    $template           = \Pulsestorm\Cli\Code_Generation\createClassTemplate($repositoryFullName, false, '\\' . $interface, true);
    
    $body               = templateRepositoryFunctions($modelName);
    $use                = templateUseFunctions($interface, $modelInterface, $classModel, $classCollection);
    $contents           = $template;
    $contents           = str_replace('<$body$>', $body, $contents);
    $contents           = str_replace('<$use$>' , $use,  $contents);
    
    $path               = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($repositoryFullName);        
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
    
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);    
}

function createRepositoryInterface($moduleInfo, $modelName)
{    
    $repositoryName = getModelRepositoryName($modelName);
    $interface      = getModuleInterfaceName($moduleInfo, $repositoryName, 'Api');
    $path           = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($interface);    
    $contents       = createRepositoryInterfaceContents($moduleInfo, $modelName, $interface);
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
}

function createCollectionClass($moduleInfo, $modelName)
{
    $path                   = $moduleInfo->folder . "/Model/ResourceModel/$modelName/Collection.php";
    $class_collection       = getCollectionClassNameFromModuleInfo($moduleInfo, $modelName);
    $class_model            = getModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $class_resource         = getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName);
            
    $template               = \Pulsestorm\Cli\Code_Generation\createClassTemplate($class_collection, BASE_COLLECTION_CLASS);
    $construct              = templateConstruct($class_model, $class_resource);

    $class_contents         = str_replace('<$body$>', $construct, $template);
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $class_contents);
}

function createDbTableNameFromModuleInfoAndModelShortName($moduleInfo, $modelName)
{
    return strToLower($moduleInfo->name . '_' . $modelName);
}

function createDbIdFromModuleInfoAndModelShortName($moduleInfo, $modelName)
{
    return createDbTableNameFromModuleInfoAndModelShortName(
        $moduleInfo, $modelName) . '_id';
}

function createResourceModelClass($moduleInfo, $modelName)
{
    $path = $moduleInfo->folder . "/Model/ResourceModel/$modelName.php";
    // $db_table               = strToLower($moduleInfo->name . '_' . $modelName);
    $db_table               = createDbTableNameFromModuleInfoAndModelShortName($moduleInfo, $modelName);
    // $db_id                  = strToLower($db_table) . '_id';
    $db_id                  = createDbIdFromModuleInfoAndModelShortName($moduleInfo, $modelName);    
    $class_resource         = getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName);
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

function getModelInterfaceShortName($modelName)
{
    return $modelName . 'Interface';
}

function createModelClass($moduleInfo, $modelName)
{
    $path = $moduleInfo->folder . "/Model/$modelName.php";
    $cache_tag           = strToLower($moduleInfo->name . '_' . $modelName);
    $class_model         = getModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $class_resource      = getResourceModelClassNameFromModuleInfo($moduleInfo, $modelName);
    $implements          = getImplementsModelInterfaceName($moduleInfo, $modelName) . ', \Magento\Framework\DataObject\IdentityInterface';
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

function getModuleInterfaceName($moduleInfo, $modelName, $type)
{
    return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
        '\\' . $type .'\\' . getModelInterfaceShortName($modelName);

}

function getModelInterfaceName($moduleInfo, $modelName)
{
    return getModuleInterfaceName($moduleInfo, $modelName, 'Api\\Data');
//     return $moduleInfo->vendor . '\\' . $moduleInfo->short_name . 
//         '\Model\\' . getModelInterfaceShortName($modelName);
}

function getImplementsModelInterfaceName($moduleInfo, $modelName)
{
    return '\\' . getModelInterfaceName($moduleInfo, $modelName);
}


function createModelInterface($moduleInfo, $modelName)
{
    $interface = getModelInterfaceName($moduleInfo, $modelName);
    $path      = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($interface);
    $contents  = \Pulsestorm\Cli\Code_Generation\templateInterface($interface,[]);    
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
    \Pulsestorm\Pestle\Library\output("Creating: " . $path);
}

function createTableNameFromModuleInfoAndModelName($moduleInfo, $modelName)
{
    return strToLower($moduleInfo->name . '_' . $modelName);
}

function generateClassNameAndInterfaceNameForSchemaFromModuleInfoAndOptions(
    $moduleInfo, $options)
{
    //InstallSchema
    $className      = generateInstallSchemaClassName($moduleInfo);
    $interfaceName  = '\Magento\Framework\Setup\InstallSchemaInterface';

    //UpgradeSchema
    if(isUseUpgradeSchema($options))
    {
        $className      = generateUpgradeSchemaClassName($moduleInfo);        
        $interfaceName  = '\Magento\Framework\Setup\UpgradeSchemaInterface';    
    }
    
    
    return [
        $className, $interfaceName];
}    

function isUseUpgradeSchema($options)
{
    return array_key_exists('use-upgrade-schema', $options);
}

function generateSchemaBodyFromModuleInfoAndOptions($moduleInfo, $options)
{
    $body = templateInstallFunction();
    if(isUseUpgradeSchema($options))
    {
        $body = templateUpgradeFunction();
    }
    return $body;    
}

function conditionalWriteStringToFile($path, $contents)
{
    if(!file_exists($path))
    {
        \Pulsestorm\Pestle\Library\output("Creating: " . $path);        
        \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
    }
    else
    {
        \Pulsestorm\Pestle\Library\output("File Already Exists: " . $path);
    }
}

function prependInstallerCodeBeforeEndSetup($moduleInfo, $modelName, $contents, $path)
{
    $table_name = createTableNameFromModuleInfoAndModelName(
        $moduleInfo, $modelName);
    $install_code = \Pulsestorm\Cli\Code_Generation\generateInstallSchemaTable($table_name);
    $contents     = file_get_contents($path);
    $end_setup    = '        ' . '$installer->endSetup();';
    $contents     = str_replace($end_setup, 
        "\n        //START table setup\n" .
        $install_code .
        "\n        //END   table setup\n" .
        $end_setup, $contents);
    return $contents;
}

function createSchemaClass($moduleInfo, $modelName, $options)
{
    list($className, $interfaceName) = 
        generateClassNameAndInterfaceNameForSchemaFromModuleInfoAndOptions(
            $moduleInfo, $options);
            
    $path       = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($className);
    $template   = \Pulsestorm\Cli\Code_Generation\createClassTemplate($className, false, $interfaceName);        
        
    $classBody = generateSchemaBodyFromModuleInfoAndOptions($moduleInfo, $options);        
    $contents   = str_replace('<$body$>', $classBody, $template); 
    conditionalWriteStringToFile($path, $contents);   
    
    $contents = prependInstallerCodeBeforeEndSetup(
        $moduleInfo, $modelName, $contents, $path);
            
    \Pulsestorm\Pestle\Library\writeStringToFile($path, $contents);
}

function generateUpgradeDataClassName($moduleInfo)
{
    $className  = str_replace('_', '\\', $moduleInfo->name) . 
        '\Setup\UpgradeData';
    return $className;
}

function generateClassNameAndInterfaceNameForDataFromModuleInfoAndOptions($moduleInfo, $options)
{
    $className  = str_replace('_', '\\', $moduleInfo->name) . 
        '\Setup\InstallData';
    $interfaceName = '\Magento\Framework\Setup\InstallDataInterface';    
    
    if(isUseUpgradeSchema($options))
    {
        $className = generateUpgradeDataClassName($moduleInfo);

        $interfaceName = '\Magento\Framework\Setup\UpgradeDataInterface';        
    }
        
    return [$className, $interfaceName];
}

function generateDataBodyFromOptions($options)
{
    $body = templateInstallDataFunction();
    if(isUseUpgradeSchema($options))
    {
        $body = templateUpgradeDataFunction();        
    }
    
    return $body;
}

function createDataClass($moduleInfo, $modelName, $options)
{
    list($className, $interfaceName) = 
        generateClassNameAndInterfaceNameForDataFromModuleInfoAndOptions(
            $moduleInfo, $options);

    $path       = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($className);
    $template   = \Pulsestorm\Cli\Code_Generation\createClassTemplate($className, false, $interfaceName);        
    $classBody  = generateDataBodyFromOptions($options);
    $contents   = str_replace('<$body$>', $classBody, $template);        

    conditionalWriteStringToFile($path, $contents);       
}

function generateUpgradeSchemaClassName($moduleInfo)
{
    $className  = str_replace('_', '\\', $moduleInfo->name) . 
        '\Setup\UpgradeSchema';
    return $className;
}

function generateInstallSchemaClassName($moduleInfo)
{
    $className  = str_replace('_', '\\', $moduleInfo->name) . 
        '\Setup\InstallSchema';
    return $className;
}

function checkForUpgradeSchemaClass($moduleInfo, $modelName)
{
    $className = generateUpgradeSchemaClassName($moduleInfo);
    $path      = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($className);
    
    if(file_exists($path))
    {    
        $message = 
"\nERROR: The module {$moduleInfo->name} already has a 
defined {$className}.  

We can't proceed. If you're using upgrade scripts, try
the --use-upgrade-schema-with-scripts option.
";        
        \Pulsestorm\Pestle\Library\exitWithErrorMessage($message);
    }

}

function checkForInstallSchemaClass($moduleInfo, $modelName)
{
    $className = generateInstallSchemaClassName($moduleInfo);
    $path      = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($className);
    
    if(file_exists($path))
    {  
        $message = 
"\nIt looks like this module already has an InstallSchema class.  This means 
ee can't proceed.  If you're trying to add a second model to a module, 
try using --use-upgrade-schema or --use-upgrade-schema-with-scripts\n";                   

        \Pulsestorm\Pestle\Library\exitWithErrorMessage($message);    
    }  

}

function checkForNoInstallSchemaAndOurUpgrade($moduleInfo, $modelName)
{
    $className = generateInstallSchemaClassName($moduleInfo);
    $path      = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass($className);
    
    if(!file_exists($path))
    {    
        $message = 
"The --use-upgrade-schema-with-scripts options requires an InstallSchema
class to already be present.  

Try creating your first crud model without any options, and use the 
--use-upgrade-schema-with-scripts options for your 2nd, 3rd, ..., nth 
crud models. ";        
        \Pulsestorm\Pestle\Library\exitWithErrorMessage($message);
    }

    $path = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass(
        generateUpgradeSchemaClassName($moduleInfo));

    if(file_exists($path) && !\Pulsestorm\Magento2\Generate\SchemaUpgrade\classFileIsOurSchemaUpgrade($path))
    {
        $message =
"The module contains an UpgradeSchema class that is not compatible with
UpgradeSchema classes created via magento2:generate:schema-upgrade.

The --use-upgrade-schema-with-scripts relies on an UpgradeSchema class
that is compatible with magento2:generate:schema-upgrade.
";        
        \Pulsestorm\Pestle\Library\exitWithErrorMessage($message);
    }

    $path = \Pulsestorm\Magento2\Cli\Path_From_Class\getPathFromClass(
        generateUpgradeDataClassName($moduleInfo));        
    if(file_exists($path) && !\Pulsestorm\Magento2\Generate\SchemaUpgrade\classFileIsOurDataUpgrade($path))
    {
        $message =
"The module contains an UpgradeData class that is not compatible with
UpgradeSchema classes created via magento2:generate:schema-upgrade.

The --use-upgrade-schema-with-scripts relies on an UpgradeSchema class
that is compatible with magento2:generate:schema-upgrade.
";      
        \Pulsestorm\Pestle\Library\exitWithErrorMessage($message);
    }
    
}

function checkForSchemaOptions($keys)
{
    if( in_array('use-upgrade-schema',$keys) && 
        in_array('use-upgrade-schema-with-scripts',$keys))
    {
        $message = 'Can\'t use --use-upgrade-schema and --use-upgrade-schema-with-scripts together.';
        \Pulsestorm\Pestle\Library\exitWithErrorMessage($message);
    }
}

function checkForSchemaClasses($moduleInfo, $modelName, $options)
{
    if(array_key_exists('use-upgrade-schema', $options))
    {
        checkForUpgradeSchemaClass($moduleInfo, $modelName);
    }
    else if(array_key_exists('use-upgrade-schema-with-scripts', $options))    
    {
        checkForNoInstallSchemaAndOurUpgrade($moduleInfo, $modelName);
    }    
    else if(!array_key_exists('use-upgrade-schema-with-scripts', $options))
    {
        checkForInstallSchemaClass($moduleInfo, $modelName);
    }
}

function isUseUpgradeSchemaWithScripts($options)
{
    return array_key_exists('use-upgrade-schema-with-scripts', $options);
}

function bumpDottedVersionNumber($version)
{
    $parts = explode('.', $version);
    $last = array_pop($parts);
    if(!is_numeric($last))
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("I don't know what to do with a version number that looks like $version");
    }
    $last++;
    $parts[] = $last;
    return implode('.', $parts);
}

function invokeGenerateSchemaUpgrade($moduleInfo, $modelName, $options)
{
    $xml = simplexml_load_file(\Pulsestorm\Magento2\Generate\SchemaUpgrade\getModuleXmlPathFromModuleInfo($moduleInfo));
    $oldVersion = $xml->module['setup_version'];
    $version = bumpDottedVersionNumber($oldVersion);

    \Pulsestorm\Magento2\Generate\SchemaUpgrade\exportedSchemaUpgrade([
        'module_name'       => $moduleInfo->name,
        'upgrade_version'   => $version
    ],[]);
    
    $setupPath = \Pulsestorm\Magento2\Generate\SchemaUpgrade\getSetupScriptPathFromModuleInfo($moduleInfo, 'schema') . 
                    "/{$version}.php";
    
    $table_name = createTableNameFromModuleInfoAndModelName(
        $moduleInfo, $modelName);
    
    $contents = file_get_contents($setupPath);
    $contents .= "\n" . '$installer = $setup;' . "\n" . \Pulsestorm\Cli\Code_Generation\generateInstallSchemaTable($table_name);
    
    \Pulsestorm\Pestle\Library\writeStringToFile($setupPath, $contents);                
}

function createSchemaAndDataClasses($moduleInfo, $modelName, $options)
{
    if(isUseUpgradeSchemaWithScripts($options))
    {                   
        invokeGenerateSchemaUpgrade($moduleInfo, $modelName, $options);
        return;
    }
    createSchemaClass($moduleInfo, $modelName, $options);
    createDataClass($moduleInfo, $modelName, $options);
}

/**
 * Temp fix until: https://github.com/astorm/pestle/issues/212
 */
function validateModelName($modelName)
{
    $newModelName = preg_replace('%[^A-Za-z0-9]%','',$modelName);
    if($newModelName !== $modelName)
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Invalid (to us) model name -- try again with {$newModelName}?" . "\n" .
        "If this annoys you -- pull requests welcome at: https://github.com/astorm/pestle/issues/212");
    }
}

/**
* Generates Magento 2 CRUD model
*
* Wrapped by magento:foo:baz ... version of command
*
* 
* @command generate-crud-model
* @argument module_name Which module? [Pulsestorm_HelloGenerate]
* @argument model_name  What model name? [Thing]
* @option use-upgrade-schema Create UpgradeSchema and UpgradeData classes instead of InstallSchema
* @option use-upgrade-schema-with-scripts Same as use-upgrade-schema, but uses schema script helpers
*/
function pestle_cli($argv, $options)
{
    $options = array_filter($options, function($item){
        return !is_null($item);
    });

    $module_name = $argv['module_name'];
    $moduleInfo = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($argv['module_name']);    
    $modelName  = validateModelName($argv['model_name']);
    
    checkForSchemaOptions(array_keys($options));
    checkForSchemaClasses($moduleInfo, $modelName, $options);
    
    createRepositoryInterface($moduleInfo, $modelName);    
    createRepository($moduleInfo, $modelName);
    createModelInterface($moduleInfo, $modelName);
    createCollectionClass($moduleInfo, $modelName);
    createResourceModelClass($moduleInfo, $modelName);
    createModelClass($moduleInfo, $modelName);                    
    createSchemaAndDataClasses($moduleInfo, $modelName, $options);
}

function exported_pestle_cli($argv, $options)
{
    return pestle_cli($argv, $options);
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
* @command generate-di
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
        exit("Could not find " . $argv['file'] . ".\n");
    }     
    $class = $argv['class'];

    injectDependencyArgumentIntoFile($class, $file);       
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
namespace Pulsestorm\Magento2\Cli\Generate\Install{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* Generates Magento 2 install
*
* Wrapped by magento:doo:baz version of command
*
* @command generate-install
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

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
namespace Pulsestorm\Magento2\Cli\Generate\Layout_Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;




/**
* ALPHA: Is this needed/working anymore?
* This command will generate the layout handle XML 
* files needed to add a block to Magento's page 
* layout
*
* @command generate-layout-xml
* @todo implement me please
*/
function pestle_cli($argv)
{
    \Pulsestorm\Pestle\Library\output("Needs to be implemented");
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
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
* @command generate-pestle-command
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
        'pestle_import(\'Pulsestorm\Pestle\Library\exitWithErrorMessage\');' . "\n\n" .
        

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
}

function pestle_cli_exported($argv, $options=[])
{
    return pestle_cli($argv, $options);
}    }
namespace Pulsestorm\Magento2\Cli\Generate\Menu{
use function Pulsestorm\Pestle\Importer\pestle_import;
use stdClass;









function getMenuXmlFiles()
{
    $base = \Pulsestorm\Magento2\Cli\Library\getBaseMagentoDir();
    // $results = `find $base/vendor -name menu.xml`;
    // $results = explode("\n", $results);
    $results = \Pulsestorm\Phpdotnet\glob_recursive("$base/vendor/menu.xml");  
    if(file_exists("$base/app/code"))
    {
        $results = array_merge($results, \Pulsestorm\Phpdotnet\glob_recursive("$base/app/code/menu.xml"));
    }          
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
* Generates Magento 2 menu.xml
*
* Wrapped by magento:foo:baz ... version of command
*
* @command generate-menu
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

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
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
* @command generate-module
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
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
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
* @command generate-observer
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

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
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
* @command generate-plugin-xml
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

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
namespace Pulsestorm\Magento2\Cli\Generate\Psr_Log_Level{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* For conversion of Zend Log Level into PSR Log Level
* 
* This command generates a list of Magento 1 log levels, 
* and their PSR log level equivalents.
*
* @command generate-psr-log-level
*/
function pestle_cli($argv)
{   
    $map = \Pulsestorm\Cli\Code_Generation\getZendPsrLogLevelMap();
    foreach($map as $key=>$value)
    {
        \Pulsestorm\Pestle\Library\output($key . "\t\t" . $value);
    }
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
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
* @command generate-registration
* @argument module_name Which Module? [Vendor_Module] 
*/
function pestle_cli($argv)
{
    $module_name = $argv['module_name'];
    
    \Pulsestorm\Pestle\Library\output(\Pulsestorm\Cli\Code_Generation\templateRegistrationPhp($module_name));
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
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
* @command generate-route
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
}

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
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
* Generates Magento 2 theme configuration
*
* Wrapped by magento:foo:baz ... version of command
*
* @command generate-theme
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

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
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
* Generates a Magento 2 view
*
* Wrapped by magento:... version of command
*
* @command generate-view
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

function exported_pestle_cli($argv)
{
    return pestle_cli($argv);
}}
namespace Pulsestorm\Magento2\Cli\Hello_Argument{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* A demo of pestle argument and option configuration/parsing
*
* @command pestle:hello-argument
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
* A Hello World command.  Hello World!
*
* @command hello-world
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
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("No such path: $path" . "\n" . 
            "Please use magento2:generate:module to create module first");
        // throw new Exception("No such path: $path");
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
        mkdir(dirname($path), 0755, true);
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
* @command list-commands
*/
function pestle_cli($argv)
{
    \Pulsestorm\Cli\Build_Command_List\includeAllModuleFiles();
    
    $user = get_defined_functions()['user'];
    $executes = array_filter($user, function($function){
        $parts = explode('\\', $function);
        $function = array_pop($parts);
        return strpos($function, 'pestle_cli') === 0 &&
               strpos($function, 'pestle_cli_export') === false;
    });
    
        
    $commands = array_map(function($function){
        $r       = new ReflectionFunction($function);
        $command = \Pulsestorm\Pestle\Library\getAtCommandFromDocComment($r);
        return [
            'command'=>$command,
            'help'=>\Pulsestorm\Pestle\Library\getDocCommentAsString($r->getName()),
        ];
    }, $executes);

    // var_dump($commands);
    $command_to_check = array_shift($argv);        

    if($command_to_check)
    {
        $commands = array_filter($commands, function($s) use ($command_to_check){
            return $s['command'] === $command_to_check || 
                $s['command'] === str_replace('_','-',$command_to_check);
        });
    }
    \Pulsestorm\Pestle\Library\output('');
    
    if(count($commands) > 1)
    {
        outputTitle();
        outputCredits();
        outputUsage();
        \Pulsestorm\Pestle\Library\output('');
        outputAvaiableCommands($commands);
        return;    
    }
    
    //only single commands left
    foreach($commands as $command)
    {
        \Pulsestorm\Pestle\Library\output("Usage: ");
        \Pulsestorm\Pestle\Library\output("    $ pestle.phar ", $command['command']);
        \Pulsestorm\Pestle\Library\output('');
        \Pulsestorm\Pestle\Library\output('Arguments:');
        \Pulsestorm\Pestle\Library\output('');        
        \Pulsestorm\Pestle\Library\output('Options:');
        \Pulsestorm\Pestle\Library\output('');
        
        \Pulsestorm\Pestle\Library\output("Help:");
        \Pulsestorm\Pestle\Library\output(preg_replace('%^%m','    $0',wordwrap($command['help'],70)));
        \Pulsestorm\Pestle\Library\output('');
        \Pulsestorm\Pestle\Library\output('');
    }
}

function getWhitespaceForCommandList($commands, $command_name)
{
    static $longest;
    if(!$longest)
    {
        $longest = 0;
        foreach($commands as $command)
        {
            $length = strlen($command['command']);
            if($length > $longest)
            {
                $longest = $length;
            }
        }
    }
    
    $numberOfSpaces = ($longest - strlen($command_name)) + 2;
    return str_repeat(' ', $numberOfSpaces);
}

/**
 * We started pestle without the magento2:generate namespace
 * These commands were the original generation commands. We
 * eventually replaced them with magento2:generate:module style
 * commands by having the magento2:generate:module command
 * call into the original generate_module module's pestle_cli
 * function.  The generate_module style commands still exist, 
 * for backwards compatability with code and docs, but we hide
 * them from the list.  
 */
function getCommandsToHide()
{
    return [
        'generate_module',
        'generate_acl',
        'generate_command',
        'generate_config_helper',
        'generate_crud_model',
        'generate_di',
        'generate_install',
        'generate_layout_xml',
        'generate_menu',
        'generate_observer',
        'generate_plugin_xml',
        'generate_psr_log_level',
        'generate_registration',
        'generate_route',
        'generate_theme',
        'generate_view',     
        'wp_export_xml',         
        'wp_urls',
        'generate_pestle_command',
        'pestle_clear_cache',
        'generate-module',
        'generate-acl',
        'generate-command',
        'generate-config-helper',
        'generate-crud-model',
        'generate-di',
        'generate-install',
        'generate-layout-xml',
        'generate-menu',
        'generate-observer',
        'generate-plugin-xml',
        'generate-psr-log-level',
        'generate-registration',
        'generate-route',
        'generate-theme',
        'generate-view',     
        'wp-export-xml',         
        'wp-urls',
        'generate-pestle-command',
        'pestle-clear-cache',        
    ];
}

function sortCommandsIntoSection($commands)
{
    $toHide = getCommandsToHide();
    $commandSections = [];    
    foreach($commands as $command)
    {                
        if(in_array($command['command'], $toHide))
        {
            continue;
        }    
        $section = 'Uncategorized';
        if(strpos($command['command'], ':') !== false)
        {
            $parts = explode(':', $command['command']);
            $section = ucWords(array_shift($parts));
            if(count($parts) > 1)
            {
                $section .= ' ' . ucWords(array_shift($parts));
            }
        }        
        $commandSections[$section][] = $command;
    }
    ksort($commandSections);
    return $commandSections;
}

function outputWithShellColor($toOutput, $colorCode=33)
{
    \Pulsestorm\Pestle\Library\output(
        getStringWrappedWithShellColor($toOutput, $colorCode)
    );
}

function getStringWrappedWithShellColor($string, $colorCode)
{
    return "\033[{$colorCode}m" . $string . "\033[0m";
}

function outputSectionHeader($section)
{
    \Pulsestorm\Pestle\Library\output('');
    outputWithShellColor($section, 33);
}

function outputCommandListing($command, $commands)
{
    $lines = preg_split('%[\r\n]%',$command['help']);
    $firstLine = array_shift($lines);
    if(!$firstLine)
    {
        $firstLine = 'NULL Command?  Fix this pls.';
    }    
    \Pulsestorm\Pestle\Library\output( '  ', 
            getStringWrappedWithShellColor($command['command'], 32),
            getWhitespaceForCommandList($commands, $command['command']), 
            $firstLine);
}

function shouldSkipShowingCommand($command)
{
    $toHide = getCommandsToHide();
    if(in_array(trim($command['command']), ['library']))
    {
        return true;
    }

    if(in_array($command['command'], $toHide))
    {
        return true;
    }    
    
    return false;
}

function outputAvaiableCommandsBySection($commandSections, $commands)
{
   
    foreach($commandSections as $section=>$commandsSorted)
    {
        $commandsSorted = $commandSections[$section];
        outputSectionHeader($section);
        foreach($commandsSorted as $command)
        {
            if(shouldSkipShowingCommand($command))
            {
                continue;
            }        
            outputCommandListing($command, $commands);
        }
    }
}

function outputAvaiableCommands($commands)
{    
    \Pulsestorm\Pestle\Library\output('Available commands:');
    $commandSections = [
        'Uncategorized'=>$commands
    ];
    $commandSections = sortCommandsIntoSection($commands);
    outputAvaiableCommandsBySection($commandSections, $commands);
}

function outputUsage()
{
    \Pulsestorm\Pestle\Library\output('Usage:');
    \Pulsestorm\Pestle\Library\output('  pestle command_name [options] [arguments]');
}

function outputCredits()
{
    \Pulsestorm\Pestle\Library\output('pestle by Pulse Storm LLC');
    \Pulsestorm\Pestle\Library\output('');
}

function outputTitle()
{
    $logo = <<<LOGO
                  _   _      
                 | | | |     
  _ __   ___  ___| |_| | ___ 
 | '_ \ / _ \/ __| __| |/ _ \
 | |_) |  __/\__ \ |_| |  __/
 | .__/ \___||___/\__|_|\___|
 | |                         
 |_|    
LOGO;
    \Pulsestorm\Pestle\Library\output($logo);
}}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Acl\Change_Title{
use function Pulsestorm\Pestle\Importer\pestle_import;





/**
* Changes the title of a specific ACL rule in a Magento 2 acl.xml file
*
* @command magento2:generate:acl:change-title
* @argument path_acl Path to ACL file? 
* @argument acl_rule_id ACL Rule ID? 
* @argument title New Title? 
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['path_acl']);
    
    $nodes = \Pulsestorm\Xml_Library\getByAttributeXmlBlockWithNodeNames(
        'id', $xml, $argv['acl_rule_id'], ['resource']);
    
    if(count($nodes) > 1)
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Found more than one node with {$argv['acl_rule_id']}");
    }

    $node = array_pop($nodes);            
    $node['title'] = $argv['title'];   
    
    \Pulsestorm\Pestle\Library\writeStringToFile($argv['path_acl'], $xml->asXml());
    \Pulsestorm\Pestle\Library\output("Changed Title");
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Acl{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates a Magento 2 acl.xml file. 
*
* @command magento2:generate:acl
* @argument module_name Which Module? [Pulsestorm_HelloWorld]
* @argument rule_ids Rule IDs? [<$module_name$>::top,<$module_name$>::config,]
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Acl\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Command{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates bin/magento command files
* This command generates the necessary files and configuration 
* for a new command for Magento 2's bin/magento command line program.
*
*   pestle.phar magento2:generate:command Pulsestorm_Generate Example
* 
* Creates
* app/code/Pulsestorm/Generate/Command/Example.php
* app/code/Pulsestorm/Generate/etc/di.xml
*
* @command magento2:generate:command
* @argument module_name In which module? [Pulsestorm_Helloworld]
* @argument command_name Command Name? [Testbed]
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Command\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Config_Helper{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates a help class for reading Magento's configuration
*
* This command will generate the necessary files and configuration 
* needed for reading Magento 2's configuration values.
* 
* @command magento2:generate:config-helper
* @todo needs to be implemented
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Config_Helper\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Controller_Edit_Acl{
use function Pulsestorm\Pestle\Importer\pestle_import;




class TokenParser
{
    protected $position=0;
    protected $tokens;
    
    protected function replaceCurrentToken($token)
    {
        $this->tokens[$this->position] = $token;
    }
    
    public function setStringContents($contents)
    {
        $this->tokens   = \Pulsestorm\Cli\Token_Parse\pestle_token_get_all($contents);
    }
    
    public function getCurrentToken()
    {
        return $this->tokens[$this->position];
    }
    
    public function isAtEnd()
    {
        return count($this->tokens) === ($this->position + 1);
    }
    
    public function goNext()
    {
        $this->position++;
        if(array_key_exists($this->position, $this->tokens))
        {
            return $this->getCurrentToken();
        }
        $this->position--;
        return null;
    }
    
    public function getClassString()
    {
        $values = array_map(function($token){
            if(isset($token->token_value))
            {
                return $token->token_value;
            }
            return '';
        }, $this->tokens);
        return implode('',  $values);            
    }
}

class EditConstantTokenParser extends TokenParser
{
    private function scanToString($string)
    {
        while($token=$this->goNext())
        {
            if($token->token_value === $string)
            {            
                return;
            }
        }
    
    }
    
    private function isPositionAtClassConstant()
    {
        for($i=$this->position;$i--;$i>0)
        {
            $token = $this->tokens[$i];
            if($token->token_name === 'T_WHITESPACE') { continue; }
            return $token->token_name === 'T_CONST';
        }
        return null;
    }
    
    private function scanToNamedConstant($constantName)
    {
        $this->scanToString($constantName);
        if($this->isPositionAtClassConstant())
        {
            return true;
        }
        
        if($this->isAtEnd())
        {
            return false;
        }
        return $this->scanToNamedConstant($constantName);
    }
    
    private function getSingleQuotedPhpString($string)
    {
        $string = str_replace("'", "\\'", $string);
        if($string[strlen($string) -1] === '\\')
        {
            $string .= '\\';
        }
        
        return "'$string'";
        
    }
    
    public function replaceConstantStringValue($constantName, $value)
    {
        $this->scanToNamedConstant($constantName);
        $token = $this->getCurrentToken();
        if($token->token_value !== $constantName)
        {
            return false;
        }
        
        while($token = $this->goNext())
        {
            // if($token->token_name === 'T_WHITESPACE') { continue; }
            if($token->token_value !== ';')
            {
                $this->replaceCurrentToken(null);
                continue;
            }
            
            //splice in new tokens
            $equalsToken = new \stdClass;
            $equalsToken->token_value = '=';
            $equalsToken->token_name  = 'T_SINGLE_CHAR';
            
            $replacementToken = new \stdClass;
            $replacementToken->token_value = $this->getSingleQuotedPhpString($value);
            $replacementToken->token_name = 'T_CONSTANT_ENCAPSED_STRING';
            
            array_splice($this->tokens, $this->position, 0, [
                $equalsToken, $replacementToken
            ]);
            break; //hit the ;, break out
        }
                
        return true;
    }    
}

/**
* Edits the const ADMIN_RESOURCE value of an admin controller
*
* @command magento2:generate:controller-edit-acl
* @argument path_controller Path to Admin Controller
* @argument acl_rule Path to Admin Controller
*/
function pestle_cli($argv)
{
    $contents = file_get_contents($argv['path_controller']);    
    $parser = new EditConstantTokenParser;
    $parser->setStringContents($contents);
    if($parser->replaceConstantStringValue('ADMIN_RESOURCE', $argv['acl_rule']))
    {
        \Pulsestorm\Pestle\Library\writeStringToFile($argv['path_controller'], $parser->getClassString());
        \Pulsestorm\Pestle\Library\output("ADMIN_RESOURCE constant value changed");
    }
    else
    {
        \Pulsestorm\Pestle\Library\output("No ADMIN_RESOURCE constant in class file");
    }
    
    
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Crud_Model{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates a Magento 2 CRUD/AbstractModel class and support files
*
* @command magento2:generate:crud-model
* @argument module_name Which module? [Pulsestorm_HelloGenerate]
* @argument model_name  What model name? [Thing]
* @option use-upgrade-schema Create UpgradeSchema and UpgradeData classes instead of InstallSchema
* @option use-upgrade-schema-with-scripts Same as use-upgrade-schema, but uses schema script helpers
*/
function pestle_cli($argv, $options)
{
    return \Pulsestorm\Magento2\Cli\Generate\Crud\Model\exported_pestle_cli($argv, $options);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Di{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* Injects a dependency into a class constructor
* This command modifies a preexisting class, adding the provided 
* dependency to that class's property list, `__construct` parameters 
* list, and assignment list.
*
*    pestle.phar magento2:generate:di app/code/Pulsestorm/Generate/Command/Example.php 'Magento\Catalog\Model\ProductFactory' 
*
* @command magento2:generate:di
* @argument file Which PHP class file are we injecting into?
* @argument class Which class to inject? [Magento\Catalog\Model\ProductFactory]
*
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Di\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Full_Module{
use function Pulsestorm\Pestle\Importer\pestle_import;



function getShellScript($argv, $options)
{
    $packageName     = $argv['package_name'];//'Pulsestorm5';
    $moduleName      = $argv['module_name'];//'Pestleform5';
    $modelName       = $argv['model_name'];//'Thing5';
    $modelNamePlural = \Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Form\createShortPluralModelName(implode('\\', 
        [$packageName, $moduleName, 'Model',$modelName]));
    
    $modelNamePluralLowerCase = strToLower($modelNamePlural);
    $packageNameLowerCase     = strToLower($packageName);
    $moduleNameLowerCase      = strToLower($moduleName);
    $modelNameLowerCase       = strToLower($modelName);
    $modelNamePluralLowerCase = strToLower($modelNamePlural);

    $pharName = 'pestle.phar';
    if(array_key_exists('use-phar-name', $options) && $options['use-phar-name'])
    {
        $pharName = 'pestle_dev';
    }
    
    $pathModule = 'app/code/'.$packageName . '/' . $moduleName;        
    return '
#!/bin/bash
' . $pharName . ' magento2:generate:module ' . $packageName . ' ' . $moduleName . ' 0.0.1
' . $pharName . ' generate_crud_model ' . $packageName . '_' . $moduleName . ' ' . $modelName . '
' . $pharName . ' magento2:generate:acl ' . $packageName . '_' . $moduleName . ' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . '
' . $pharName . ' magento2:generate:menu ' . $packageName . '_' . $moduleName . ' "" ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . ' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . ' "' . $moduleName . ' ' . $modelNamePlural . '" ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '/index/index 10
' . $pharName . ' magento2:generate:menu ' . $packageName . '_' . $moduleName . ' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . ' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . '_list ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . ' "' . $modelName . ' Objects" ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '/index/index 10
' . $pharName . ' generate_route ' . $packageName . '_' . $moduleName . ' adminhtml ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '    
' . $pharName . ' generate_view ' . $packageName . '_' . $moduleName . ' adminhtml ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '_index_index Main content.phtml 1column
' . $pharName . ' magento2:generate:ui:grid ' . $packageName . '_' . $moduleName . ' ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . ' \'' . $packageName . '\\' . $moduleName . '\Model\ResourceModel\\' . $modelName . '\Collection\' ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNameLowerCase . '_id
' . $pharName . ' magento2:generate:ui:form ' . $packageName . '_' . $moduleName . ' \'' . $packageName . '\\' . $moduleName . '\Model\\' . $modelName . '\' ' . $packageName . '_' . $moduleName . '::' . $modelNamePluralLowerCase . '

' . $pharName . ' magento2:generate:ui:add_to_layout '    . $pathModule.'/view/adminhtml/layout/'.$packageNameLowerCase . '_' . $moduleNameLowerCase.'_'.$modelNamePluralLowerCase.'_index_index.xml content ' . $packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '
' . $pharName . ' magento2:generate:acl:change_title '    . $pathModule.'/etc/acl.xml '.$packageName.'_'.$moduleName.'::'.$modelNamePluralLowerCase.' "Manage '.$modelNamePluralLowerCase.'"
' . $pharName . ' magento2:generate:controller_edit_acl ' . $pathModule.'/Controller/Adminhtml/Index/Index.php ' . $packageName.'_'.$moduleName.'::'.$modelNamePluralLowerCase . '

' . $pharName . ' magento2:generate:remove-named-node '   . $pathModule . '/view/adminhtml/layout/'.$packageNameLowerCase . '_' . $moduleNameLowerCase . '_' . $modelNamePluralLowerCase . '_index_index.xml block '.$packageNameLowerCase . '_' . $moduleNameLowerCase.'_block_main

php bin/magento module:enable '.$packageName . '_' . $moduleName.'
php bin/magento setup:upgrade

';    

}

function replaceTemplateVars($template, $argv)
{
    return $template;
}

/**
* Creates shell script with all pestle commands needed for full module output
*
* @command magento2:generate:full-module
* @argument package_name Package Name? [Pulsestorm]
* @argument module_name Module Name? [Helloworld]
* @argument model_name One Word Model Name? [Thing]
* @option use-phar-name Change pestle.phar to something like pestle_dev
*/
function pestle_cli($argv, $options)
{
    $script = getShellScript($argv, $options);
    \Pulsestorm\Pestle\Library\output($script);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Install{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* BETA: Generates commands to install Magento via composer
*
* @command magento2:generate:install
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
    return \Pulsestorm\Magento2\Cli\Generate\Install\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Menu{
use function Pulsestorm\Pestle\Importer\pestle_import;





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
        $parent = \Pulsestorm\Magento2\Cli\Generate\Menu\choseMenuFromTop();
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
    return \Pulsestorm\Magento2\Cli\Generate\Menu\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Module{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates new module XML, adds to file system
* This command generates the necessary files and configuration
* to add a new module to a Magento 2 system.
*
*    pestle.phar magento2:generate:module Pulsestorm TestingCreator 0.0.1
*
* @argument namespace Vendor Namespace? [Pulsestorm]
* @argument name Module Name? [Testbed]
* @argument version Version? [0.0.1]
* @command magento2:generate:module
*/
function pestle_cli($argv)
{
    \Pulsestorm\Magento2\Cli\Generate\Module\exported_pestle_cli($argv);
}

function test()
{
    \Pulsestorm\Pestle\Library\output("Hello There. " . __FILE__);
}}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Observer{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates Magento 2 Observer
* This command generates the necessary files and configuration to add 
* an event observer to a Magento 2 system.
*
*    pestle.phar magento2:generate:observer Pulsestorm_Generate controller_action_predispatch pulsestorm_generate_listener3 'Pulsestorm\Generate\Model\Observer3'
*
* @command magento2:generate:observer
* @argument module Full Module Name? [Pulsestorm_Generate]
* @argument event_name Event Name? [controller_action_predispatch]
* @argument observer_name Observer Name? [<$module$>_listener]
* @argument model_name Class Name? [<$module$>\Model\Observer]
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Observer\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Plugin_Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* Generates plugin XML
* This command generates the necessary files and configuration 
* to "plugin" to a preexisting Magento 2 object manager object. 
*
*     pestle.phar magento2:generate:plugin_xml Pulsestorm_Helloworld 'Magento\Framework\Logger\Monolog' 'Pulsestorm\Helloworld\Plugin\Magento\Framework\Logger\Monolog'
* 
* @argument module_name Create in which module? [Pulsestorm_Helloworld]
* @argument class Which class are you plugging into? [Magento\Framework\Logger\Monolog]
* @argument class_plugin What's your plugin class name? [<$module_name$>\Plugin\<$class$>]
* @command magento2:generate:plugin-xml
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Plugin_Xml\exported_pestle_cli($argv);
}
}
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
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Psr_Log_Level{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* For conversion of Zend Log Level into PSR Log Level
* 
* This command generates a list of Magento 1 log levels, 
* and their PSR log level equivalents.
*
* @command magento2:generate:psr-log-level
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Psr_Log_Level\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Registration{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates registration.php
* This command generates the PHP code for a 
* Magento module registration.php file.
* 
*     $ pestle.phar magento2:generate:registration Foo_Bar
*     <?php
*         \Magento\Framework\Component\ComponentRegistrar::register(
*             \Magento\Framework\Component\ComponentRegistrar::MODULE,
*             'Foo_Bar',
*             __DIR__
*         );
* 
* @command magento2:generate:registration
* @argument module_name Which Module? [Vendor_Module] 
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Registration\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Route{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Creates a Route XML
* generate_route module area id 
* @command magento2:generate:route
* @argument module_name Which Module? [Pulsestorm_HelloWorld]
* @argument area Which Area (frontend, adminhtml)? [frontend]
* @argument frontname Frontname/Route ID? [pulsestorm_helloworld]
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Route\exported_pestle_cli($argv);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Theme{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates Theme Configuration
*
* @command magento2:generate:theme
* @argument package Theme Package Name? [Pulsestorm]
* @argument theme Theme Name? [blank]
* @argument area Area? (frontend, adminhtml) [frontend]
* @argument parent Parent theme (enter 'null' for none) [Magento/blank]
*
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\Theme\exported_pestle_cli($argv);
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
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Add_To_Layout{
use function Pulsestorm\Pestle\Importer\pestle_import;





function exitWithErrorMessage($message)
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function validateNoSuchComponent($xml, $name)
{
    $nodes = \Pulsestorm\Xml_Library\getNamedXmlBlockWithNodeNames($xml, $name, ['uiComponent']);
    if(count($nodes) === 0)
    {
        return;
    }
    exitWithErrorMessage("Bailing: uiComponent Node Already Exists");
}

function getContentBlockOrContainerOrReference($xml, $name)
{
    return \Pulsestorm\Xml_Library\getNamedXmlBlockWithNodeNames($xml, $name, 
        ['container', 'block', 'referenceContainer','referenceBlock']);
}

function getContentNode($xml,$argv)
{
    $nodes = getContentBlockOrContainerOrReference($xml, $argv['block_name']);    
    if(count($nodes) > 1)
    {
        exitWithErrorMessage("BAILING: Found more than one name=\"".$argv['block_name']."\" node.\n");
    }
    return array_pop($nodes);
}

/**
* Adds a <uiComponent/> node to a named node in a layout update XML file
*
* @command magento2:generate:ui:add-to-layout
* @argument path_layout Layout XML File?
* @argument block_name Block or Reference Name?
* @argument ui_component_name UI Component Name?
*/
function pestle_cli($argv)
{
    $xml    = simplexml_load_file($argv['path_layout']);    
    validateNoSuchComponent($xml, $argv['ui_component_name']);
    $node   = getContentNode($xml, $argv);
    
    $node->addChild('uiComponent')
        ->addAttribute('name', $argv['ui_component_name']);
    $xmlString    = \Pulsestorm\Xml_Library\formatXmlString($xml->asXml());        
    \Pulsestorm\Pestle\Library\writeStringToFile($argv['path_layout'], $xmlString);        
    \Pulsestorm\Pestle\Library\output("Added Component");
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Ui\Form{
use function Pulsestorm\Pestle\Importer\pestle_import;








function getModelShortName($modelClass)
{
    $parts = explode('\\', $modelClass);
    $parts = array_slice($parts, 3);
    return implode('_', $parts);
}

function getModuleNameFromClassName($modelClass)
{
    $parts = explode('\\', $modelClass);
    $parts = array_slice($parts, 0,2);
    return implode('_', $parts);
}

function getPersistKeyFromModelClassName($modelClass)
{
    $key = strToLower(getModuleNameFromClassName($modelClass) 
        . '_' 
        . getModelShortName($modelClass));
    
    return $key;        
}

function createControllerClassBodyForIndexRedirect($module_info, $modelClass, $aclRule)
{
    return '
    const ADMIN_RESOURCE = \''.$aclRule.'\';  
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath(\'*/index/index\');
        return $resultRedirect;
    }     
';
}

function createControllerClassBodyForDelete($module_info, $modelClass, $aclRule)
{    
    $dbID       = \Pulsestorm\Magento2\Cli\Generate\Crud\Model\createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));    
    return '  
    const ADMIN_RESOURCE = \''.$aclRule.'\';   
          
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam(\'object_id\');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            $title = "";
            try {
                // init model and delete
                $model = $this->_objectManager->create(\''.$modelClass.'\');
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__(\'You have deleted the object.\'));
                // go to grid
                return $resultRedirect->setPath(\'*/*/\');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath(\'*/*/edit\', [\''.$dbID.'\' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__(\'We can not find an object to delete.\'));
        // go to grid
        return $resultRedirect->setPath(\'*/*/\');
        
    }    
    
';    
}

function createControllerClassBodyForSave($module_info, $modelClass, $aclRule)
{
    $dbID       = \Pulsestorm\Magento2\Cli\Generate\Crud\Model\createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));
    $persistKey = getPersistKeyFromModelClassName($modelClass);
    return '
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = \''.$aclRule.'\';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param Action\Context $context
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Action\Context $context,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            if (isset($data[\'is_active\']) && $data[\'is_active\'] === \'true\') {
                $data[\'is_active\'] = '.$modelClass.'::STATUS_ENABLED;
            }
            if (empty($data[\''.$dbID.'\'])) {
                $data[\''.$dbID.'\'] = null;
            }

            /** @var '.$modelClass.' $model */
            $model = $this->_objectManager->create(\''.$modelClass.'\');

            $id = $this->getRequest()->getParam(\''.$dbID.'\');
            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccess(__(\'You saved the thing.\'));
                $this->dataPersistor->clear(\''.$persistKey.'\');
                if ($this->getRequest()->getParam(\'back\')) {
                    return $resultRedirect->setPath(\'*/*/edit\', [\''.$dbID.'\' => $model->getId(), \'_current\' => true]);
                }
                return $resultRedirect->setPath(\'*/*/\');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __(\'Something went wrong while saving the data.\'));
            }

            $this->dataPersistor->set(\''.$persistKey.'\', $data);
            return $resultRedirect->setPath(\'*/*/edit\', [\''.$dbID.'\' => $this->getRequest()->getParam(\''.$dbID.'\')]);
        }
        return $resultRedirect->setPath(\'*/*/\');
    }    
';
}

function createControllerClassBody($module_info, $aclRule)
{
    return '
    const ADMIN_RESOURCE = \''.$aclRule.'\';       
    protected $resultPageFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;        
        return parent::__construct($context);
    }
    
    public function execute()
    {
        return $this->resultPageFactory->create();  
    }    
';
}

function createControllerFiles($module_info, $modelClass, $aclRule)
{
    $shortName = getModelShortName($modelClass);
    // $moduleBasePath = getModuleBasePath();
    $prefix = $module_info->vendor . '\\' . $module_info->short_name;
    $classes = [
        'controllerEditClassname' => $prefix . '\Controller\Adminhtml\\'.$shortName.'\Edit',
        'controllerNewClassName'  => $prefix . '\Controller\Adminhtml\\'.$shortName.'\NewAction',
        'controllerSaveClassName' => $prefix . '\Controller\Adminhtml\\'.$shortName.'\Save'
    ];
    foreach($classes as $desc=>$className)
    {        
        $contents = createClassWithUse($className, '\Magento\Backend\App\Action', '', 
            createControllerClassBody($module_info, $aclRule));
        if($desc === 'controllerSaveClassName')
        {
            $useString = '
use Magento\Backend\App\Action;
use '.$prefix.'\Model\Page;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
            ';
            $contents = createClassWithUse($className, '\Magento\Backend\App\Action', $useString,
                createControllerClassBodyForSave($module_info, $modelClass, $aclRule));   
        }       
        \Pulsestorm\Pestle\Library\output("Creating: $className");
        \Pulsestorm\Magento2\Cli\Library\createClassFile($className,$contents);        
    }
    
    $deleteClassName = $prefix . '\Controller\Adminhtml\\'.$shortName.'\Delete';
    $useString       = '';
    $contents = createClassWithUse(
        $deleteClassName, '\Magento\Backend\App\Action', $useString,
        createControllerClassBodyForDelete($module_info, $modelClass, $aclRule));
    \Pulsestorm\Pestle\Library\output("Creating: $deleteClassName");
    \Pulsestorm\Magento2\Cli\Library\createClassFile($deleteClassName,$contents); 
        
    $indexRedirectClassName = $prefix . '\Controller\Adminhtml\\'.$shortName.'\Index';
    $useString       = '';
    $contents = createClassWithUse(
        $indexRedirectClassName, '\Magento\Backend\App\Action', $useString,
        createControllerClassBodyForIndexRedirect($module_info, $modelClass, $aclRule));
    \Pulsestorm\Pestle\Library\output("Creating: $deleteClassName");
    \Pulsestorm\Magento2\Cli\Library\createClassFile($indexRedirectClassName,$contents);             


}

function createCollectionClassNameFromModelName($modelClass)
{
    $parts = explode('\\', $modelClass);
    if($parts[2] !== 'Model')
    {
        throw new \Exception("Model name that, while valid, doesn't conform to what we expect");
    }
    $first      = array_slice($parts, 0, 3);
    $first[]    = 'ResourceModel';
    
    $second     = array_slice($parts, 3);
    $second[]   = 'CollectionFactory';
    $new        = array_merge($first, $second);
    return implode('\\', $new);
}

function createDataProviderUseString($module_info, $modelClass)
{
    $collectionClassName = createCollectionClassNameFromModelName($modelClass);
        
    return 'use '.$collectionClassName.';
use Magento\Framework\App\Request\DataPersistorInterface;';
}

function createDataProviderClassBodyString($module_info, $modelClass)
{
    $persistKey = getPersistKeyFromModelClassName($modelClass);
    return '

    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();

        foreach ($items as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }

        $data = $this->dataPersistor->get(\''.$persistKey.'\');
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId()] = $item->getData();
            $this->dataPersistor->clear(\''.$persistKey.'\');
        }

        return $this->loadedData;
    }
';        
}

function createClassWithUse($className, $parentClass, $useString, $bodyString)
{
    $contents           = \Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse($className, $parentClass);
    $contents           = str_replace('<$use$>', $useString, $contents);
    $contents           = str_replace('<$body$>', $bodyString, $contents);
    return $contents;
}

function createDataProviderClassNameFromModelClassName($modelClass)
{
    return $modelClass . '\DataProvider';
}

function createDataProvider($module_info, $modelClass)
{
    // $moduleBasePath = getModuleBasePath();
    $dataProviderClassName = createDataProviderClassNameFromModelClassName($modelClass);
    $contents           = createClassWithUse(
        $dataProviderClassName, 
        '\Magento\Ui\DataProvider\AbstractDataProvider',
        createDataProviderUseString($module_info, $modelClass),
        createDataProviderClassBodyString($module_info, $modelClass)        
    );        
    \Pulsestorm\Pestle\Library\output("Creating: $dataProviderClassName");
    $return             = \Pulsestorm\Magento2\Cli\Library\createClassFile($dataProviderClassName,$contents);        
    
}

function createShortPluralModelName($modelClass)
{
    $parts = [];
    $flag  = false;
    foreach(explode('\\', $modelClass) as $part)
    { 
        if($part === 'Model')
        {
            $flag = true;
            continue;
        }
        if(!$flag) { continue;}
        $parts[] = $part;
    }          
          
    $parts = array_map('strToLower', $parts);
    $name  = implode('_', $parts);
    
    if(preg_match('%ly$%',$name))
    {
        $name = preg_replace('%ly$%', 'lies',$name);
    }
    else
    {
        $name = $name . 's';
    }
    return $name;
}

function createEmptyXmlTree()
{
    $xml = simplexml_load_string(
        '<page  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd"></page>');
    return $xml;
}

function addUiComponentToXml($xml, $uiComponentName)
{
    $nodes = $xml->xpath("//uiComponent[@name='$uiComponentName']");
    if(count($nodes) === 0)
    {
        $referenceBlock = $xml->addChild('referenceBlock');
        $referenceBlock->addAttribute('name', 'content');
        $uiComponent = $referenceBlock->addChild('uiComponent');
        $uiComponent->addAttribute('name', $uiComponentName);
    }
    return $xml;
}

function createLayoutXmlFiles($module_info, $modelClass)
{
    $moduleBasePath     = $module_info->folder;
    $layoutBasePath     = $moduleBasePath . '/view/adminhtml/layout'; 
    
    $uiComponentName    = createUiComponentNameFromModuleInfoAndModelClass(
        $module_info, $modelClass);
    
    $prefixFilename = implode('_', [
        strToLower($module_info->name),
        createShortPluralModelName($modelClass),
        strToLower(getModelShortName($modelClass))
        // 'index'
    ]);;
    
    $names = ['edit', 'new', 'save' ];
    
    foreach($names as $name)
    {
        $fileName = $layoutBasePath . '/' . $prefixFilename . '_' . $name . '.xml';
        
        $xml = createEmptyXmlTree();
        if(file_exists($fileName))
        {
            $xml = simplexml_load_file($fileName);
        }
        $xml = addUiComponentToXml($xml, $uiComponentName);
        
        \Pulsestorm\Pestle\Library\output("Creating $fileName");
        \Pulsestorm\Pestle\Library\writeStringToFile($fileName, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
    }        
}

function createUiComponentNameFromModuleInfoAndModelClass($module_info, $modelClass)
{
    return implode('_', [
        strToLower($module_info->name),
        createShortPluralModelName($modelClass),
        'form'
    ]);

}

function generateGenericButtonClassAndReturnName($prefix, $dbID, $aclRule)
{

    $genericButtonClassName     = $prefix . '\\GenericButton';
    $genericButtonClassContents = \Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse($genericButtonClassName);
    $genericButtonClassContents = str_replace('<$use$>' ,'', $genericButtonClassContents);
    
    $genericContents = '
    //putting all the button methods in here.  No "right", but the whole
    //button/GenericButton thing seems -- not that great -- to begin with
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context
    ) {
        $this->context = $context;    
    }
    
    public function getBackUrl()
    {
        return $this->getUrl(\'*/*/\');
    }    
    
    public function getDeleteUrl()
    {
        return $this->getUrl(\'*/*/delete\', [\'object_id\' => $this->getObjectId()]);
    }   
    
    public function getUrl($route = \'\', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }    
    
    public function getObjectId()
    {
        return $this->context->getRequest()->getParam(\''.$dbID.'\');
    }     
';    
    $genericButtonClassContents = str_replace('<$body$>',$genericContents, $genericButtonClassContents);    
    \Pulsestorm\Magento2\Cli\Library\createClassFile($genericButtonClassName,$genericButtonClassContents);                   
    return $genericButtonClassName;
}

function generateButtonClassPrefix($modelClass)
{
    $prefix = str_replace('_','\\',getModuleNameFromClassName($modelClass)) . '\\Block\\Adminhtml\\' .
        getModelShortName($modelClass) . '\\Edit';
    return $prefix;
}

function getAllButtonDataStrings()
{        
    $singleQuoteForJs = "\\''";
    return [
        'back'=> '[
            \'label\' => __(\'Back\'),
            \'on_click\' => sprintf("location.href = \'%s\';", $this->getBackUrl()),
            \'class\' => \'back\',
            \'sort_order\' => 10    
        ]',
        'delete'=> '[
                \'label\' => __(\'Delete Object\'),
                \'class\' => \'delete\',
                \'on_click\' => \'deleteConfirm( '.$singleQuoteForJs.' . __(
                    \'Are you sure you want to do this?\'
                ) . \''.'\\'.'\', ' . $singleQuoteForJs . ' . $this->getDeleteUrl() . \''.'\\'.'\')\',
                \'sort_order\' => 20,
            ]',
        'reset'=> '[
            \'label\' => __(\'Reset\'),
            \'class\' => \'reset\',
            \'on_click\' => \'location.reload();\',
            \'sort_order\' => 30
        ]',        
        'save'=> '[
            \'label\' => __(\'Save Object\'),
            \'class\' => \'save primary\',
            \'data_attribute\' => [
                \'mage-init\' => [\'button\' => [\'event\' => \'save\']],
                \'form-role\' => \'save\',
            ],
            \'sort_order\' => 90,
        ]',                
        'save_and_continue'=> '[
            \'label\' => __(\'Save and Continue Edit\'),
            \'class\' => \'save\',
            \'data_attribute\' => [
                \'mage-init\' => [
                    \'button\' => [\'event\' => \'saveAndContinueEdit\'],
                ],
            ],
            \'sort_order\' => 80,
        ]',                        
    ];    
}

function getButtonDataStringForButton($buttonName)
{
    $buttons = getAllButtonDataStrings();
    if(!isset($buttons[$buttonName]))
    {
        \Pulsestorm\Pestle\Library\output("Bailing -- I don't know how to create a [$buttonName] button");
        exit;
    }
    return $buttons[$buttonName];
}

function createButtonClassContents($buttonName)
{
    $buttonData = getButtonDataStringForButton($buttonName);
    $extra = '';
    if($buttonName === 'delete')
    {
        $extra = 'if(!$this->getObjectId()) { return []; }';
    }
    $contents = '     
    public function getButtonData()
    {
        '.$extra.'
        return '. $buttonData .';
    }' . "\n";
    return $contents;
    // return '//implement me for ' . $buttonName;
}
function generateButtonClassAndReturnName($modelClass, $buttonName)
{            
    $prefix = generateButtonClassPrefix($modelClass);
    $buttonClassName = $prefix .= '\\' . str_replace(' ', '', 
        ucWords(str_replace('_', ' ', $buttonName))) . 'Button';        
    
    $contents = \Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse($buttonClassName, 'GenericButton', 
        'ButtonProviderInterface');
    $contents   = str_replace('<$use$>','use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;',$contents);
    $contents   = str_replace('<$body$>',createButtonClassContents($buttonName),$contents);
    \Pulsestorm\Magento2\Cli\Library\createClassFile($buttonClassName,$contents);            
    
    return $buttonClassName;
}

function createButtonXml($module_info, $modelClass, $aclRule)
{
    //handle generic button
    $prefix = generateButtonClassPrefix($modelClass);
    $dbID   = \Pulsestorm\Magento2\Cli\Generate\Crud\Model\createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));    
    generateGenericButtonClassAndReturnName($prefix, $dbID, $aclRule);    
    
    $buttons = [        
        'back'              => generateButtonClassAndReturnName($modelClass,'back'),
        'delete'            => generateButtonClassAndReturnName($modelClass,'delete'),
        'reset'             => generateButtonClassAndReturnName($modelClass,'reset'),
        'save'              => generateButtonClassAndReturnName($modelClass,'save'),
        'save_and_continue' => generateButtonClassAndReturnName($modelClass,'save_and_continue')                                        
//         'back'              => $prefix . '\BackButton',
//         'delete'            => $prefix . '\DeleteButton',
//         'reset'             => $prefix . '\ResetButton',
//         'save'              => $prefix . '\SaveButton',
//         'save_and_continue' => $prefix . '\SaveAndContinueButton',                                
    ];
    $buttonXml = "\n";
    foreach($buttons as $name=>$class)
    {
        $buttonXml .= '<item name="'.$name.'" xsi:type="string">'.$class.'</item>' . "\n";
    }
    
    return $buttonXml;
}

function createUiComponentXmlFile($module_info, $modelClass, $aclRule)
{    
    $moduleBasePath      = $module_info->folder;
    $uiComponentBasePath = $moduleBasePath . '/view/adminhtml/ui_component';     
    $uiComponentName     = createUiComponentNameFromModuleInfoAndModelClass($module_info, $modelClass);
    $uiComponentFilePath = $uiComponentBasePath . '/' . $uiComponentName . '.xml';        
    $dbID       = \Pulsestorm\Magento2\Cli\Generate\Crud\Model\createDbIdFromModuleInfoAndModelShortName($module_info, getModelShortName($modelClass));
    $dataProviderClassName = createDataProviderClassNameFromModelClassName($modelClass);          
    
    $buttonXml = createButtonXml($module_info, $modelClass, $aclRule);
         
    $xml = simplexml_load_string(
'<?xml version="1.0" encoding="UTF-8"?>
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">'.$uiComponentName.'.'.$uiComponentName.'_data_source</item>
            <item name="deps" xsi:type="string">'.$uiComponentName.'.'.$uiComponentName.'_data_source</item>
        </item>
        <item name="label" xsi:type="string" translate="true">Object Information</item>
        <item name="config" xsi:type="array">
            <item name="dataScope" xsi:type="string">data</item>
            <item name="namespace" xsi:type="string">'.$uiComponentName.'</item>
        </item>
        <item name="template" xsi:type="string">templates/form/collapsible</item>
        <item name="buttons" xsi:type="array">
            '.$buttonXml.'
        </item>
    </argument>
    <dataSource name="'.$uiComponentName.'_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">'.$dataProviderClassName.'</argument>
            <argument name="name" xsi:type="string">'.$uiComponentName.'_data_source</argument>
            <argument name="primaryFieldName" xsi:type="string">'.$dbID.'</argument>
            <argument name="requestFieldName" xsi:type="string">'.$dbID.'</argument>
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="submit_url" xsi:type="url" path="*/*/save"/>
                </item>
            </argument>
        </argument>
        <argument name="data" xsi:type="array">
            <item name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">Magento_Ui/js/form/provider</item>
            </item>
        </argument>
    </dataSource>
    <fieldset name="general">
        <argument name="data" xsi:type="array">
            <item name="config" xsi:type="array">
                <item name="label" xsi:type="string">Form Data</item>
                <item name="collapsible" xsi:type="boolean">true</item>                
            </item>
        </argument>
        <field name="'.$dbID.'">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="visible" xsi:type="boolean">false</item>
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="formElement" xsi:type="string">input</item>                    
                    <item name="dataScope" xsi:type="string">'.$dbID.'</item>
                </item>
            </argument>
        </field>
        <field name="title">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="label" xsi:type="string" translate="true">Title</item>
                    <item name="formElement" xsi:type="string">input</item>
                    <item name="sortOrder" xsi:type="number">20</item>
                    <item name="dataScope" xsi:type="string">title</item>
                    <item name="validation" xsi:type="array">
                        <item name="required-entry" xsi:type="boolean">true</item>
                    </item>
                </item>
            </argument>
        </field>
    </fieldset>
</form>'    
    );        
    \Pulsestorm\Pestle\Library\writeStringToFile($uiComponentFilePath, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
}
/**
* Generates a Magento 2 UI Component form configuration and PHP boilerplate
*
* @command magento2:generate:ui:form
* @argument module Which Module? [Pulsestorm_Formexample]
* @argument model Model Class? [Pulsestorm\Formexample\Model\Thing]
* @argument aclRule ACL Rule for Controllers? [Pulsestorm_Formexample::ruleName]
*/
function pestle_cli($argv)
{
    $module_info      = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($argv['module']);
    createControllerFiles($module_info, $argv['model'], $argv['aclRule']);
    createDataProvider($module_info, $argv['model']);
    createLayoutXmlFiles($module_info, $argv['model']);
    createUiComponentXmlFile($module_info, $argv['model'], $argv['aclRule']);
}
}
namespace Pulsestorm\Magento2\Cli\Magento2_Generate_Ui_Grid{
use function Pulsestorm\Pestle\Importer\pestle_import;


    









function generateArgumentNode($xml, $gridId, $dataSourceName, $columnsName, $collection)
{
    $shortName = getShortModelNameFromResourceModelCollection(
        $collection);    
    $fullIdentifier = $gridId . '.' . $dataSourceName;
    
    $argument   = \Pulsestorm\Magento2\Cli\Library\addArgument($xml, 'data', 'array');
    $js_config  = \Pulsestorm\Magento2\Cli\Library\addItem($argument,'js_config','array');
    $provider   = \Pulsestorm\Magento2\Cli\Library\addItem($js_config, 'provider', 'string', $fullIdentifier);      
    $deps       = \Pulsestorm\Magento2\Cli\Library\addItem($js_config, 'deps', 'string', $fullIdentifier);      
    $spinner    = \Pulsestorm\Magento2\Cli\Library\addItem($argument, 'spinner', 'string', $columnsName);         
    
    $buttons    = \Pulsestorm\Magento2\Cli\Library\addItem($argument, 'buttons', 'array');
    $add        = \Pulsestorm\Magento2\Cli\Library\addItem($buttons, 'add', 'array');
    \Pulsestorm\Magento2\Cli\Library\addItem($add, 'name', 'string', 'add');
    \Pulsestorm\Magento2\Cli\Library\addItem($add, 'label', 'string', 'Add New');
    \Pulsestorm\Magento2\Cli\Library\addItem($add, 'class', 'string', 'primary');
    \Pulsestorm\Magento2\Cli\Library\addItem($add, 'url', 'string', '*/'.$shortName.'/new');

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

function generateUiComponentXmlFile($gridId, $databaseIdName, $module_info, $collection)
{
    $pageActionsClassName = generatePageActionClassNameFromPackageModuleAndGridId(
        $module_info->vendor, $module_info->short_name, $gridId);
    $requestIdName    = generateRequestIdName();
    $providerClass    = generateProdiverClassFromGridIdAndModuleInfo($gridId, $module_info);    
    $dataSourceName   = generateDataSourceNameFromGridId($gridId);    
    $columnsName      = generateColumnsNameFromGridId($gridId);

    $xml             = simplexml_load_string(\Pulsestorm\Magento2\Cli\Xml_Template\getBlankXml('uigrid'));        
    $argument        = generateArgumentNode($xml, $gridId, $dataSourceName, $columnsName, $collection);        
    $dataSource      = generateDatasourceNode($xml, $dataSourceName, $providerClass, $databaseIdName, $requestIdName);
    generateListingToolbar($xml);
    $columns         = generateColumnsNode($xml, $columnsName, $databaseIdName, $pageActionsClassName);
     
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

function generatePageActionClass($moduleInfo, $gridId, $idColumn, $collection)
{
    $pageActionsClassName = generatePageActionClassNameFromPackageModuleAndGridId(
        $moduleInfo->vendor, $moduleInfo->short_name, $gridId);
        
    // $editUrl              = 'adminhtml/'.$gridId.'/viewlog';        
    
    
    // $editUrl              = $gridId . '/index/edit';        
    $shortName = getShortModelNameFromResourceModelCollection(
        $collection);    
    $editUrl = $gridId . '/' . strToLower($shortName) . '/edit';   
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
                        "'.$editUrl.'",["'.$idColumn.'"=>$id]),
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

function getShortModelNameFromResourceModelCollection($collection)
{
    $parts = explode('\\', $collection);
    if($parts[3] !== 'ResourceModel' || $parts[(count($parts)-1)] !== 'Collection')
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Collection model name does not conform to the arbitrary naming convention we chose.  We're bailing.");
    }
    $parts = array_slice($parts, 4);
    array_pop($parts);
    $shortName =  implode('_', $parts);
    return $shortName;
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
        $argv['grid_id'], $argv['db_id_column'], $module_info, $argv['collection_resource']);                                        
        
    generateDataProviderClass(
        $module_info, $argv['grid_id'], $argv['collection_resource'] . 'Factory');
        
    generatePageActionClass(
        $module_info, $argv['grid_id'], $argv['db_id_column'], $argv['collection_resource']);                    
        
    \Pulsestorm\Pestle\Library\output("Don't forget to add this to your layout XML with <uiComponent name=\"{$argv['grid_id']}\"/> ");        
}
}
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\View{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* Generates view files (layout handle, phtml, Block, etc.)
*
* @command magento2:generate:view
* @argument module_name Which Module? [Pulsestorm_HelloGenerate]
* @argument area Which Area? [frontend]
* @argument handle Which Handle? [<$module_name$>_index_index]
* @argument block_name Block Name? [Main]
* @argument template Template File? [content.phtml]
* @argument layout Layout (ignored for adminhtml) ? [1column]
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Magento2\Cli\Generate\View\exported_pestle_cli($argv);
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
* BETA: Used to scan my old pre-Wordpress archives for missing pages. 
*
* @command pulsestorm:orphan-content
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
* BETA: Uses pandoc to converts a markdown file to pdf, epub, epub3, html, txt 
*
* @command pulsestorm:pandoc-md
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
* Turns a PHP class into a Magento 2 path
* Long
* Description
* @command magento2:path-from-class
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
* BETA: Clears the pestle cache
*
* @command pestle-clear-cache
*/
function pestle_cli($argv)
{
    $cache_dir = \Pulsestorm\Pestle\Importer\getCacheDir();
    rename($cache_dir, $cache_dir . '.' . time());
    \Pulsestorm\Pestle\Importer\getCacheDir();
}

function pestle_cli_exported($argv, $options=[])
{
    return pestle_cli($argv, $options);
}    
}
namespace Pulsestorm\Magento2\Cli\Read_Rest_Schema{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* BETA: Magento command, reads the rest schema on a Magento system
*
* @command magento2:read-rest-schema
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
* @command magento2:search:search-controllers
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
* ALPHA: Tests the "namespace integrity?  Not sure what this is anymore. 
*
* @command php:test-namespace-integrity
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
* A test command for the output function that should probably be pruned
*
* @command test-output
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
* @Xargument folder Which Folder?
*/
function pestle_cli($arguments, $options)
{
    $files = glob('/Users/alanstorm/Documents/tumblr-backup/2017-02-03/*');
    foreach($files as $file)
    {
        $xml = simplexml_load_file($file);
        foreach($xml->posts->post as $post)
        {
            $title = (string)$post->{'regular-title'};
            $title = $title ? $title : (string)$post->{'link-text'};
            // output((string)$post['title'] . "\t" . (string)$post->url);
            \Pulsestorm\Pestle\Library\output( 
                $title                  . "\t"  .
                (string)$post['url']    . "\t"  .
                (string) $post['unix-timestamp']
            );
        }
    }
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
namespace Pulsestorm\Magento2\Generate\Remove_Named_Node{
use function Pulsestorm\Pestle\Importer\pestle_import;






/**
* Removes a named node from a generic XML configuration file
*
* @command magento2:generate:remove-named-node
* @argument path_xml The XML file? []
* @argument node_name The <node_name/>? [block]
* @argument name The {node_name}="" value? []
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file($argv['path_xml']);
    $nodes = \Pulsestorm\Xml_Library\getByAttributeXmlBlockWithNodeNames(
        'name', $xml, $argv['name'], [$argv['node_name']]);    

    if(count($nodes) === 0)
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Bailing: No such node.");
    }

    if(count($nodes) > 1)
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Bailing: Found more than one node.");
    }
            
    $node = $nodes[0];            
    
    if(count($node->children()) > 0)
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Bailing: Contains child nodes");
    }

    unset($node[0]); //http://stackoverflow.com/questions/262351/remove-a-child-with-a-specific-attribute-in-simplexml-for-php/16062633#16062633
        
    \Pulsestorm\Pestle\Library\writeStringToFile(
        $argv['path_xml'],\Pulsestorm\Xml_Library\formatXmlString($xml->asXml())
    );
    \Pulsestorm\Pestle\Library\output("Node Removed");
}
}
namespace Pulsestorm\Magento2\Generate\SchemaUpgrade{
use function Pulsestorm\Pestle\Importer\pestle_import;








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
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Bailing: Upgrade Data already exists and it not pestle's");
    }
}

function checkForUpgradeSchema($moduleInfo)
{
    if(!moduleHasOrNeedsOurUpgradeSchema($moduleInfo))
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Bailing: Upgrade Schema already exists and is not pestle's");
    }
}

function checkForSchemaInstall($moduleInfo)
{
    if(!file_exists($moduleInfo->folder . '/Setup/InstallSchema.php'))
    {
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Bailing: Module needs an InstallSchema first.");
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
            \Pulsestorm\Pestle\Library\exitWithErrorMessage("A $upgradeVersion.php $type script already exists");
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
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("New module version ({$upgradeVersion}) " .
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
        \Pulsestorm\Pestle\Library\exitWithErrorMessage("Version does not appear to be in numeric X.X.X format.");
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

function getDataClassBody($moduleInfo, $useScripts)
{
    $constructor          = '';
    if($useScripts)
    {
        $constructor = getConstructorForUpgradeClassesFromModuleInfo($moduleInfo);
    }
    
    $scriptHelperCall    = '';
    if($useScripts)
    {
        $scriptHelperCall = '$this->scriptHelper->run($setup, $context, \'data\');';
    }
        
    $setupScriptClassName = getSetupScriptClassNameFromModuleInfo($moduleInfo);
    return '
    ' . $constructor . '
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup, 
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();        
        ' . $scriptHelperCall . '
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

function getConstructorForUpgradeClassesFromModuleInfo($moduleInfo)
{
    $setupScriptClassName = getSetupScriptClassNameFromModuleInfo($moduleInfo);    
    $constructor = '
    protected $scriptHelper;    
    public function __construct(
        '.$setupScriptClassName.' $scriptHelper
    )
    {
        $this->scriptHelper = $scriptHelper;
    }        
';        
    return $constructor;
}
function getSchemaClassBody($moduleInfo, $useScripts)
{
    
    $constructor          = '';
    if($useScripts)
    {
        $constructor = getConstructorForUpgradeClassesFromModuleInfo($moduleInfo);
    }
    
    $scriptHelperCall    = '';
    if($useScripts)
    {
        $scriptHelperCall = '$this->scriptHelper->run($setup, $context, \'schema\');';
    }
        
    return '
    ' . $constructor . '
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup, 
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();        
        ' . $scriptHelperCall . '
        $setup->endSetup();
    }      
';    
}

function generateUpgradeSchemaClass($moduleInfo, $useScripts)
{
    $path = getUpgradeSchemaPathFromModuleInfo($moduleInfo);
    $className = getSchemaClassNameFromModuleInfo($moduleInfo);

    $contents = \Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse($className, false, 'UpgradeSchemaInterface');
    $contents = str_replace('<$use$>', getSchemaUseStatements(), $contents);
    $contents = str_replace('<$body$>', getSchemaClassBody($moduleInfo, $useScripts), $contents);
    $contents = prefacePhpStringWithMitLicense($contents);
        
    \Pulsestorm\Pestle\Library\output("Creating $className");
    \Pulsestorm\Magento2\Cli\Library\createClassFile($className, $contents);        
}

function generateUpgradeDataClass($moduleInfo, $useScripts)
{
    $path = getUpgradeDataPathFromModuleInfo($moduleInfo);
    $className = getDataClassNameFromModuleInfo($moduleInfo);

    $contents = \Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse($className, false, 'UpgradeDataInterface');
    $contents = str_replace('<$use$>', getDataUseStatements(), $contents);
    $contents = str_replace('<$body$>', getDataClassBody($moduleInfo, $useScripts), $contents);
    $contents = prefacePhpStringWithMitLicense($contents);    
    \Pulsestorm\Pestle\Library\output("Creating $className");    
    \Pulsestorm\Magento2\Cli\Library\createClassFile($className, $contents); 
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
    $contents = \Pulsestorm\Cli\Code_Generation\createClassTemplateWithUse($setupScriptClassName, false);
    $contents = str_replace('<$use$>', '', $contents);
    $contents = str_replace('<$body$>', getScriptsClassBody($moduleInfo), $contents);
    $contents = prefacePhpStringWithMitLicense($contents);    
    \Pulsestorm\Magento2\Cli\Library\createClassFile($setupScriptClassName, $contents);         
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
    \Pulsestorm\Pestle\Library\output("Incrementing module.xml to {$upgradeVersion}");
    $path = getModuleXmlPathFromModuleInfo($moduleInfo);
    $xml = simplexml_load_file($path);
    $xml->module['setup_version'] = $upgradeVersion;    
    \Pulsestorm\Pestle\Library\writeStringToFile($path, \Pulsestorm\Xml_Library\formatXmlString($xml->asXml()));
}

function generateUpgradeScripts($moduleInfo, $upgradeVersion)
{    
    $setupPath = getSetupScriptPathFromModuleInfo($moduleInfo, 'schema');
    \Pulsestorm\Pestle\Library\output("Creating {$upgradeVersion} Upgrade Scripts in {$setupPath}");    
    \Pulsestorm\Pestle\Library\writeStringToFile($setupPath . '/' . $upgradeVersion . '.php', 
        getSchemaUpgradeScriptBody());    
            
    $setupPath = getSetupScriptPathFromModuleInfo($moduleInfo, 'data');        
    \Pulsestorm\Pestle\Library\output("Creating {$upgradeVersion} Upgrade Scripts in {$setupPath}");    
    \Pulsestorm\Pestle\Library\writeStringToFile($setupPath . '/' . $upgradeVersion . '.php', 
        getDataUpgradeScriptBody());
}        

/**
* BETA: Generates a migration-based UpgradeSchema and UpgradeData classes
*
* @command magento2:generate:schema-upgrade
* @argument module_name Module Name? [Pulsestorm_Helloworld]
* @argument upgrade_version New Module Version? [0.0.2]
* @option use-simple-upgrade Option to skip creating script helpers
*/
function pestle_cli($argv, $options)
{
    $options = array_filter($options, function($item){
        return !is_null($item);
    });
    $useScripts = isset($options['use-simple-upgrade']) ? false : true;
    $moduleInfo = \Pulsestorm\Magento2\Cli\Library\getModuleInformation($argv['module_name']);
    
    checkForSchemaInstall($moduleInfo);
    checkForUpgradeSchema($moduleInfo);
    checkForUpgradeData($moduleInfo);
    checkUpgradeVersionValidity($moduleInfo, $argv['upgrade_version']);
    
    generateUpgradeSchemaClass($moduleInfo, $useScripts);
    generateUpgradeDataClass($moduleInfo, $useScripts);
    incrementModuleXml($moduleInfo, $argv['upgrade_version']);
    
    if($useScripts)
    {
        generateScriptHelperClass($moduleInfo);
        generateUpgradeScripts($moduleInfo, $argv['upgrade_version']);
    }    
}

function exportedSchemaUpgrade($argv, $options)
{
    return pestle_cli($argv, $options);
}}
namespace Pulsestorm\Nexmo\Sendtext{
use function Pulsestorm\Pestle\Importer\pestle_import;



use Exception;

function handleException($e)
{
    \Pulsestorm\Pestle\Library\output(get_class($e));
    \Pulsestorm\Pestle\Library\output($e->getMessage());
}
/**
* Sends a text message
*
* @command nexmo:send-text
* @argument to Send to phone number? 
* @argument from From phone number? [12155167753]
* @argument text Text to send? [You are the best!]
*/
function pestle_cli($argv)
{
    $client     = \Pulsestorm\Nexmo\Storecredentials\getClient();
    $message    = false;
    try
    {
        $message = $client->message()->send([
            'to'   => $argv['to'],
            'from' => $argv['from'],
            'text' => $argv['text']
        ]);    
    }
    catch(Exception $e)
    {
        handleException($e);
    }        
    if($message)
    {
        \Pulsestorm\Pestle\Library\output($message->getResponseData());
    }
}
}
namespace Pulsestorm\Nexmo\Storecredentials{
use function Pulsestorm\Pestle\Importer\pestle_import;


use stdClass;

function getClient()
{
    $data = readFromCredentialFile();
    $key = $data->key;
    $secret = $data->secret;    
    $client = new \Nexmo\Client(new \Nexmo\Client\Credentials\Basic($key, $secret));    
    return $client;
}

function getCredentialFilePath()
{
    return '/tmp/.nexmo';
}

function readFromCredentialFile()
{
    $path = getCredentialFilePath();
    $o = false;
    if(file_exists(getCredentialFilePath()))
    {
        $o = json_decode(
            file_get_contents(
                $path
            )
        );
    }
    if(!$o)
    {
        $o = new stdClass;
    }
    
    return $o;
}

function writeToCredentialFile($data)
{
    $path = getCredentialFilePath();
    $result = file_put_contents($path, json_encode($data));
    chmod($path, 0600);
    return $result;
}

/**
* One Line Description
*
* @command nexmo:store-credentials
* @argument key Key? []
* @argument password Secret/Password? []
*/
function pestle_cli($argv)
{
    $data           = readFromCredentialFile();
    $data->key      = $argv['key'];
    $data->secret   = $argv['password'];
    writeToCredentialFile($data);    
}
}
namespace Pulsestorm\Nexmo\Verifyrequest{
use function Pulsestorm\Pestle\Importer\pestle_import;



use Exception;

function handleException($e)
{
    \Pulsestorm\Pestle\Library\output(get_class($e));
    \Pulsestorm\Pestle\Library\output($e->getMessage());
}

function sendVerifyRequest($client, $number, $brand)
{    
    $clientVerify   = $client->verify();    
    $verification   = [
        'number' => $number,
        'brand'  => $brand        
    ];        
    
    $response = false;
    try
    {
        $response = $clientVerify->start($verification);
    }
    catch(Exception $e)
    {
        handleException($e);
    }
    return $response;
}

/**
* Sends initial request to verify user's phone number
*
* @command nexmo:verify-request
* @argument to Phone number to verify?
* @argument brand Brand/Prefix string for code message? [MyApp]
*/
function pestle_cli($argv)
{
    $client = \Pulsestorm\Nexmo\Storecredentials\getClient();
    $verifyRequestResponse = sendVerifyRequest(
        $client, $argv['to'], $argv['brand']);    
    if($verifyRequestResponse)
    {
        $json = $verifyRequestResponse->getResponseData();    
        \Pulsestorm\Pestle\Library\output($json);    
    }        
}
}
namespace Pulsestorm\Nexmo\Verifysendcode{
use function Pulsestorm\Pestle\Importer\pestle_import;



use Exception;

function sendVerifyVerification($client, $verificationRequestId, $code)
{
    $clientVerify = $client->verify();
    $result = false;
    try
    {
        $result = $clientVerify->check(
            $verificationRequestId,
            $code
        );    
    }
    catch(\Exception $e)
    {
        \Pulsestorm\Pestle\Library\output(get_class($e));
        \Pulsestorm\Pestle\Library\output($e->getMessage());
    }
    return $result;
}

/**
* One Line Description
*
* @command nexmo:verify-sendcode
* @argument request_id Request ID? (from nexmo:verify-request) []
* @argument code The four or six digit code? []
*/
function pestle_cli($argv)
{
    $client = \Pulsestorm\Nexmo\Storecredentials\getClient();
    $result = sendVerifyVerification($client, $argv['request_id'], $argv['code']);
    if($result)
    {
        \Pulsestorm\Pestle\Library\output($result->getResponseData());
    }
}
}
namespace Pulsestorm\Nofrills\Build_Book{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* BETA: Command for building No Frills Magento 2 Layout
*
* @command pulsestorm:build-book
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
        'src/chapter1b.md',                
        'src/chapter1.md',
        'src/chapter-layouthandles.md',        
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
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.html `;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.epub`;
    echo `pandoc /tmp/working.md -s -o output/in-progress-no-frills.epub3`;                
    
    echo `tar -cvf output/Pulsestorm_Nofrillslayout.tar -C /Users/alanstorm/Sites/magento-2-1-0.dev/project-community-edition app/code/Pulsestorm/Nofrillslayout`;
    $date = date('Y-m-d-H-i-s',time());
    $zip = $date . '.zip';
    echo `zip -r $zip output`;
    
    $result     = `wc -w /tmp/working.md`;
    $parts      = preg_split('%\s{1,1000}%', trim($result));
    $word_count = array_pop($parts);
    
    $word_count = preg_split('%\s{1,100}%', trim($result));
        
    $word_count = array_shift($word_count);
    
    $cmd = "echo \"$date $word_count\" >> wordcount.txt";
    `$cmd`;
    readfile('wordcount.txt');
    // echo `cat wordcount.txt`;
}
}
namespace Pulsestorm\Parsing\Wf{
use function Pulsestorm\Pestle\Importer\pestle_import;


/**
* One Line Description
*
* @command parsing:wf
* @argument path Path to File?
* @argument starting_num Starting Number? [1]
*/
function pestle_cli($argv)
{
    $handle = fopen($argv['path'],'r');
    
    $c = $argv['starting_num'];
    \Pulsestorm\Pestle\Library\output('Due on X/XX/XX:    ');
    while($data = fgetcsv($handle))
    {    
        if(count($data) !== 5)
        {
            continue;
        }
        
        if($data[1] > 0 && $data[4] === 'BILL PAY PAYMENT')
        {
            continue;
        }
        
        if($data[1] > 0 && $data[4] !== 'BILL PAY PAYMENT')
        {
            exit("Unknown Thing?");
            \Pulsestorm\Pestle\Library\output($data);
        }
        $memo = str_replace('#','', $data[4]);
        \Pulsestorm\Pestle\Library\output('    ' . $c . '-Paid On ' . $data[0] . ':' . abs($data[1]) . ' ' . $memo);
        
        $c++;  
    }
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
* BETA: Converts a CSV file to .iif
*
* @command parsing:csv-to-iif
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
namespace Pulsestorm\Pestle\Clear_Cache{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* BETA: Clears the pestle cache
*
* @command pestle:clear-cache
*/
function pestle_cli($argv, $options)
{
    return \Pulsestorm\Magento2\Cli\Pestle_Clear_Cache\pestle_cli_exported($argv, $options);
}
}
namespace Pulsestorm\Pestle\Library{
use ReflectionFunction;
use ReflectionClass;

function exitWithErrorMessage($message)
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

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
            //the boolean options
            else if(preg_match('%^(use|is)-%', $option))
            {
                $option_value = '';
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
* ALPHA: Stub for running a single PHP file in a pestle context
*
* @command pestle:pestle-run-file
* @argument file Run which file?
*/
function pestle_cli($argv)
{
    require_once($argv['file']);
}
}
namespace Pulsestorm\Pestle\Version{
use function Pulsestorm\Pestle\Importer\pestle_import;

define('PULSESTORM_PESTLE_VERSION', '1.1.2');
/**
* One Line Description
*
* @command version
*/
function pestle_cli($argv)
{
    \Pulsestorm\Pestle\Library\output('pestle Ver ' . PULSESTORM_PESTLE_VERSION);
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
namespace Pulsestorm\Twitter\Twitter\Api\Oauth{
use function Pulsestorm\Pestle\Importer\pestle_import;
use Abraham\TwitterOAuth\TwitterOAuth;
use DomDocument;



function getUserDir()
{
    return trim(`echo ~`);
}

function getConfigPath()
{
    return getUserDir() . '/.pestle.twitter.json';    
}

function getTwitterConfig()
{
    $path = getConfigPath();
    
    if(!file_exists($path))
    {
        file_put_contents($path, '{}');
    }
    
    return (array) json_decode(file_get_contents($path));
}

function saveTwitterConfig($config)
{
    $path = getConfigPath();
    return file_put_contents($path, json_encode($config)); 
}

function mergeConfigWithOptions($config, $options)
{
    $options = array_filter($options);
    $config = array_merge($config, $options);
    return $config;
}

function getRateLimitRemainingMessage($connection)
{
    $headers = $connection->getLastXHeaders();
    $message = 'No Rate Limit Header';
    if(isset($headers['x_rate_limit_remaining'])){ 
        $message = $headers['x_rate_limit_remaining'];
    }
    return $message;
}

function deleteTweet($connection, $id)
{
    //delete it
    $content = $connection->post('statuses/destroy',['id'=>$id]);
    // var_dump($url);
    // var_dump($content);
    // $content = $connection->get('statuses/show', ['id'=>$id]);
    \Pulsestorm\Pestle\Library\output('Deleted:              ' . $id);        
    
    $message = getRateLimitRemainingMessage($connection);
    \Pulsestorm\Pestle\Library\output('Rate Limit Remaining: ' . $message);        
}

function getPathToIdFile()
{
    return '/Users/alanstorm/Documents/twitter-archive/615493_779e4ae7ef168ed562e8906c02252460bf4f9b67/ids.csv';    
}

function getConsumerAndoAuthAuthenticatedConnectionFromConfig($config)
{
    $connection = new TwitterOAuth(
        $config['consumer_key'], 
        $config['consumer_secret'],
        $config['oauth_token'],
        $config['oauth_token_secret']
    );
    return $connection;
}

function deleteNTweetsFromArrayOfIds($n, $ids, $config)
{
    $connection = getConsumerAndoAuthAuthenticatedConnectionFromConfig($config);
    $n          = $n;
    $first_n    = array_slice($ids, 0, $n);
    $n_removed  = array_slice($ids, $n);

    array_map(function($id) use ($connection){      
        $id      = trim($id);  
        deleteTweet($connection, $id);
        return $id;
    }, $first_n);
    // var_dump(var_dump($connection->getLastXHeaders()));
    return $n_removed;        
}

function commandTestbed($config)
{
    $connection = getConsumerAndoAuthAuthenticatedConnectionFromConfig($config);
    $tweets = $connection->get('statuses/user_timeline',[
        'count'=>200,
        'max_id'=>'809863258391846912'
    ]);
    foreach($tweets as $tweet)
    {
//         var_dump($tweet->created_at);
//         var_dump($tweet->id);
//         var_dump($tweet->text);
        // exit;
    }
    exit;
    commandDeleteN($config);
}

function commandDeleteN($config)
{
    $pathToIds  = getPathToIdFile();
    $lines      = file($pathToIds);        
    $idsLeft    = deleteNTweetsFromArrayOfIds(10, $lines, $config);
    file_put_contents($pathToIds, implode($idsLeft));    
}

function run($command, $config)
{
    switch($command)
    {
        case 'testbed':
            return commandTestbed($config);
        case 'delete-n':
            return commandDeleteN($config);            
        case 'oauth_url':
            return commandOauthUrl($config);
        case 'oauth_post':
            return commandOauthPost($config);
        default:
            \Pulsestorm\Pestle\Library\output("Unknown Command");
            exit;
    }
    \Pulsestorm\Pestle\Library\output("How did you get here?");
    exit;
}

function postToUrl($url, $params)
{
    $fieldsString = http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);           
    // curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);           
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    
    
    $server_output = curl_exec($ch);    
    curl_close($ch);

    return $server_output;
}

function fetchUrl($url)
{
    $contents = file_get_contents($url);
    return $contents;
}

function fetchUrlAsSimpleXml($url)
{
    $contents = fetchUrl($url);
    $dom = new DomDocument;
    $dom->loadHtml($contents);
    $xml = simplexml_load_string($dom->saveXml());
    return $xml;
}

function commandOauthPost($config)
{
    $url = getOauthUrl($config);
    \Pulsestorm\Pestle\Library\output($url);
    $xml = fetchUrlAsSimpleXml($url);    
    
    $nodes = $xml->xpath('//form[@id="oauth_form"]');
    $form = array_pop($nodes);    
    $url = (string) $form['action'];
    
    $nodes = $xml->xpath('//form[@id="oauth_form"]//input');
    $params = [];
    foreach($nodes as $node)
    {
        $name = (string) $node['name'];
        if(in_array($name, ['authenticity_token','redirect_after_login','oauth_token']))
        {
            $params[$name] = (string) $node['value'];
        }
    }    
    // $params['auth_token'] = '4c5528bcc8b0d4fcc2ec1061f49b6c493b247c65';
    $results = postToUrl($url, $params);
    
    $headers = [];
    $lines   = preg_split('%[\r\n]%',$results);
    foreach($lines as $line)
    {
        $parts  = explode(':', $line);
        $name   = array_shift($parts);
        $value  = trim(implode(':', $parts));
        $headers[$name] = $value;
    }    
    $url = $headers['location'];
    \Pulsestorm\Pestle\Library\output($url);
}

function commandOauthUrl($config)
{
    $url = getOauthUrl($config);
    \Pulsestorm\Pestle\Library\output($url);
}

function getOauthUrl($config)
{
    $connection = new TwitterOAuth(
        $config['consumer_key'], 
        $config['consumer_secret'] 
    );
    
    $request_token = $connection->oauth(
        'oauth/request_token', 
        array('oauth_callback' => null)
    );

    var_dump($request_token);

    $url = $connection->url(
        'oauth/authorize', 
        array('oauth_token' => $request_token['oauth_token'])
    );
    return $url;    
}

/**
* ALPHA: Experiments with the twitter oauth based REST API
*
* @command twitter:api:oauth
* @argument command Which Command? [testbed]
* @option consumer_key Twitter Application Consumer Key
* @option consumer_secret Twitter Application Consumer Secret
* @option oauth_token Authorized Token
* @option oauth_token_secret Authorized Secret
*/
function pestle_cli($argv, $options)
{
    $config = getTwitterConfig($options);   
    $config = mergeConfigWithOptions($config, $options);    
    
    run($argv['command'], $config);
    
    saveTwitterConfig($config);    
    // var_dump($config);
}
}
namespace Pulsestorm\Wordpress\Export\Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* ALPHA: WordPress export script
*
* @command wordpress:export:xml
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Wordpress\Export_Xml\pestle_cli_exported($argv);
}
}
namespace Pulsestorm\Wordpress\Export_Xml{
use function Pulsestorm\Pestle\Importer\pestle_import;







use Michelf\Markdown;
use DomDocument;
use Exception;

class PestleXmlElement extends \SimpleXMLElement
{
    public function setText($text)
    {
        $this[0]   = $text;
    }
    
    public function addChildCdata($name, $value=null, $namespace=null )
    {
        return $this->addChild($name, '<![CDATA[' . $value . ']>', $namespace);
    }
    
    public function addChild($name, $value=null, $namespace=null )
    {        
        if(strpos($value, '<![CDATA[') !== 0)
        {
            $node = parent::addChild($name, $value, $namespace);
        }
        else
        {
        
            $value = substr($value, 9);
            $value = substr($value, 0, strlen($value) -2);
            $child = parent::addChild($name, null, $namespace);
        
            $node = dom_import_simplexml($child); 
            $no   = $node->ownerDocument; 
            $node->appendChild($no->createCDATASection($value));         
            $node = simplexml_import_dom($node);
        }
        return $node;
    }
    
    public function addMetaKey($key, $value)
    {
        $this->addChildCdata('wp:meta_key', $key, 'wp');
        $this->addChildCdata('wp:meta_value', $value, 'wp');
        return $this;
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

function cleanContent($content)
{
    $content = str_replace('<p><promo></promo></p>',   '', $content);
    $content = str_replace('<p><series /></p>',        '', $content);

    if(strpos($content, '<' . '?' . 'php') !== false)
    {
        echo($content);
        var_dump(__FUNCTION__);
        exit;
    }         
    return $content;
}

function createItem($xml, $post)
{
    $item                 = $xml->addChild('item');
    $title                = $item->addChild('title', $post->title);
    $pubDate              = $item->addChild('pubDate', date('D, d M Y H:i:s +0000', strToTime($post->date)));
    $post_date            = $item->addChild('wp:post_date', date('Y-m-d H:i:s', strToTime($post->date)), 'wp');
    $post_date_gmt        = $item->addChild('wp:post_date_gmt', date('Y-m-d H:i:s', strToTime($post->date)), 'wp');
    $creator              = $item->addChildCdata('dc:creator','astorm','dc');
    $post_name            = $item->addChild('wp:post_name', trim($post->slug,'/'),'wp');
    $post_type            = $item->addChild('wp:post_type', 'post','wp');                     
    $status               = $item->addChild('wp:status', 'publish', 'wp');
    
    $content_encoded      = $item->addChildCdata('content:encoded',cleanContent($post->content),'content');
    $excerpt_encoded      = $item->addChildCdata('excerpt:encoded',$post->excerpt,'excerpt');
    
    if(isset($post->category))
    {
        $category               = $item->addChildCdata('category', 
            getCategoryNameFromNiceName($post->category));
        $category['domain']     = 'category';
        $category['nicename']   = $post->category;    
        
        if($post->series && $post->series['seriesSlug'] === 'magento-2-object-system')
        {
            $category               = $item->addChildCdata('category', 
                getCategoryNameFromNiceName('magento-2'));
            $category['domain']     = 'category';
            $category['nicename']   = 'magento-2';            
        }
    }
    
    if(isset($post->series))
    {        
        $nodeSeries               = $item->addChildCdata('category', $post->series['title']);
        $nodeSeries['domain']     = 'series';
        $nodeSeries['nicename']   = $post->series['seriesSlug'];

        $wp_postmeta          = $item->addChild('wp:postmeta','','wp');         
        $series_meta_callback = function($slug, $info){
            $filtered = array_filter($info['articles'], function($array) use ($slug){
                return $slug === $array['link'];
            });
            if(count($filtered) > 1)
            {
                throw new Exception("Whhhhhhhhhat?");
            }
            $keys = array_keys($filtered);
            return [
                'place_in_series'=>array_shift($keys),
                'info'=>array_shift($filtered)
            ];
        };                
        $series_meta          = call_user_func($series_meta_callback,$post->slug, $post->series);
        
        $wp_postmeta->addMetaKey('_series_part',$series_meta['place_in_series']);
        
    }
//     $link                 = $item->addChild('link','');
//     $pubDate              = $item->addChild('pubDate','');
//     $dc_creator           = $xml->addChild('dc:creator', '<![CDATA[astorm]]>');
//     $guid                 = $item->addChild('guid','');
//     $guid['isPermaLink']  ="false";
//     $description          = $item->addChild('description','');
//     $content_encoded      = $item->addChild('content:encoded','');
//     $excerpt_encoded      = $item->addChild('excerpt:encoded','');
//     $wp_post_id           = $item->addChild('wp:post_id','');
//     $wp_post_date         = $item->addChild('wp:post_date','');
//     $wp_post_date_gmt     = $item->addChild('wp:post_date_gmt','');
//     $wp_comment_status    = $item->addChild('wp:comment_status','');
//     $wp_ping_status       = $item->addChild('wp:ping_status','');
//     $wp_post_name         = $item->addChild('wp:post_name','');
//     $wp_status            = $item->addChild('wp:status','');
//     $wp_post_parent       = $item->addChild('wp:post_parent','');
//     $wp_menu_order        = $item->addChild('wp:menu_order','');
//     $wp_post_type         = $item->addChild('wp:post_type','');
//     $wp_post_password     = $item->addChild('wp:post_password','');
//     $wp_is_sticky         = $item->addChild('wp:is_sticky','');
//     $wp_postmeta          = $item->addChild('wp:postmeta','');
//     $wp_meta_key          = $item->addChild('wp:meta_key','');
//     $wp_meta_value        = $item->addChild('wp:meta_value','');
//     $wp_postmeta          = $item->addChild('wp:postmeta',''); 
    return $item; 
}

function loadSeriesInformation()
{
    $series = [];
    $series['magento2-advanced-javascript'] = 'Magento 2: Advanced Javascript';    
    $files = glob('/Users/alanstorm/Documents/github_private/pair-websites/public_html/alanstorm.com/content-store-local/series/*.php');
    foreach($files as $file)
    {
        $info = include($file);
        $parts = explode('#',$info->link);
        $slug = str_replace('_','-',array_pop($parts));
        $series[$slug] = (array)$info;
    }
    return $series;
}

function taskGenerateSeries()
{
    $series = loadSeriesInformation();
    foreach($series as $slug=>$info)
    {
        $term = $channel->addChild('wp:term',null,'wp');
        $term->addChild('wp:term_taxonomy', '<![CDATA[series]]>','wp');
        $term->addChild('wp:term_slug',     '<![CDATA['.$slug.']]>','wp');
        $term->addChild('wp:term_parent',   '<![CDATA[]]>','wp');
        $term->addChild('wp:term_name',     '<![CDATA['.$info['title'].']]>','wp');
        $term->addChild('wp:term_description',     '<![CDATA['.$info['descriptionFull'].']]>','wp');
// 		<wp:term_taxonomy><![CDATA[series]]></wp:term_taxonomy>
// 		<wp:term_slug><![CDATA[magento-2-mvc]]></wp:term_slug>
// 		<wp:term_parent><![CDATA[]]></wp:term_parent>
// 		<wp:term_name><![CDATA[Magento 2 for PHP MVC Developers]]></wp:term_name>        
    }
}

function getTitleFromContents($markdownContents)
{
    $parts = explode('==================================================',
        $markdownContents);
        	
    $title = trim(strip_tags(array_shift($parts)));
    if(!$title)
    {
        exit(__FUNCTION__);
    }
    
    if(count(preg_split('%[\r\n]%', $title)) > 1)
    {
        var_dump(__FUNCTION__);
        var_dump($title);
        exit;    
    }
    
    return $title;
}

function getSlugFromContents($markdownContents)
{
    $parts = explode('==================================================',
        $markdownContents);
    
    $anchor = array_shift($parts);
    preg_match('%href="(.+?)"%i',$anchor, $matches);
    $slug = $matches[1];
    if(!$slug)
    {
        exit(__FUNCTION__);
    }
    return $slug;
}

function getPostContentsFromContents($htmlContents)
{
    $parts = explode('</h1>', $htmlContents);
    array_shift($parts);
    $contents = implode('</h1>', $parts);
            
    if(!$contents)
    {
        var_dump(__FUNCTION__);
        exit;
    }
    
    return $contents;
}

function getExcerptFromPost($post)
{
    $post = strip_tags($post);
    $wordCount = 45;
    //From: http://stackoverflow.com/a/12445298/4668
    $excerpt = implode( 
        '', 
        array_slice( 
            preg_split(
                '/([\s,\.;\?\!]+)/', 
                $post, 
                $wordCount*2+1, 
                PREG_SPLIT_DELIM_CAPTURE
            ),
            0,
            $wordCount*2-1
        )
    );    
    
    $parts = preg_split('%[\r\n]%', trim($excerpt));
    $excerpt = array_shift($parts);
    if(!$excerpt)
    {
        var_dump(__FUNCTION__);
        exit;
    }
    return $excerpt;
}

function getPostToSkip()
{
    $prefix = '/Users/alanstorm/Documents/github_private/pair-websites/public_html/content-store/alanstorm.com/';
    return [
//             $prefix . 'objective_c_selector_part_2.php',
//             $prefix . 'objective_c_selector_part_3.php',
//             $prefix . 'objective_c_selector_part_4.php',
            
            //weird slug vs. filename vs. a problem.  Leave this magneto_listener_lifecycle_block.php skip in.
            $prefix .'magneto_listener_lifecycle_block.php',
            
            //stuff below this line is ok to skip
            $prefix . 'atom.php',            
            $prefix . 'home.php',            
            $prefix . 'css.php',
            $prefix . 'copyright.php',
            $prefix . 'magento-articles.php',
            $prefix . 'category/drupal.php',
            $prefix . 'category/magento.php',
            $prefix . 'category/python.php',
            $prefix . 'category/webos.php',
            $prefix . 'category/zend-framework.php',            
            $prefix . 'categorypython.php',
            $prefix . 'archives.php',
            $prefix . 'php_prototype.php',
            $prefix . 'projects.php', 
            $prefix . 'stage.php' ,           
            $prefix . 'webos.php',
            $prefix . 'zend-framework.php',                                   
        ];
}

function getPostDateFromConetnts($htmlContents)
{
    $parts = explode('<div class="date">Originally published ', $htmlContents);
    $string = array_pop($parts);
    $parts = explode('</div>', $string);
    $date = array_shift($parts);
    if(!$date)
    {
        exit(__METHOD__);
    }
    return $date;   
} //Originally published February  2, 2006

function deTab($markdownContents)
{
    return str_replace("\t", '    ', $markdownContents);
}

function taskGetPostInformation()
{
    $information = taskGetPostInformationContentCourier();
    $information = array_merge($information, taskGetPostInformationTtf());    
    return $information;
}

function taskGetPostInformationTtf()
{
    $files = glob('/Users/alanstorm/Documents/github_private/pair-websites' . 
        '/public_html/alanstorm.com/2004/includes/*.php');
        
    $toSkip = ['projects','404','about','archive','archives','contact', 
        'whatismyip1', 'next_time__I_ll_pay_attention', 'dot_com_envy', 'test',
        'testing','asdfasd','test1','test2','test3', 'iebank1','testing1',
        'linklog','Testing_Markdown','home',
        
        //damaged utf8
        ];        
    $posts = [];       
    foreach($files as $file)        
    {
        // output($file);        
        $pathInfo = pathInfo($file);
        if(in_array($pathInfo['filename'], $toSkip)) { continue; }
        // var_dump($pathInfo);
        $tmp = [];
        $lines    = file($file);
        $contents = implode('', $lines);
        if(strpos($contents, '<h2>') === false) { continue; }
        
        $tmp['title']   = trim(strip_tags(array_shift($lines)));
        // output($tmp['title']);
        $tmp['slug']    = $pathInfo['filename'];        
        $tmp['date']    = trim((
            str_replace(
                'Originally published ',
                '',
                strip_tags(array_pop($lines))
            )
        ));    
        $tmp['content'] = contentFilterForOldTtf(implode('',$lines));
        $tmp['excerpt'] = getExcerptFromPost($tmp['content']);                
        $posts[] = (object) $tmp;
    }
    
    return $posts;
}

function contentFilterForOldTtf($string)
{
    $string = preg_replace(
        '%\<\?php.+?Your IP Address is: </strong>.+?\?>%si',
        'Your IP Adress is: <strong><em>An older feature, disabled in this age of rugged, clumsy CMS systems.</em></strong>',
        $string
    );
    return $string;
}

function taskGetPostInformationContentCourier()
{
    $items = \Pulsestorm\Phpdotnet\glob_recursive(
        '/Users/alanstorm/Documents/github_private/pair-websites' . 
            '/public_html/content-store/*.php'
    );

    $toSkip       = getPostToSkip();    
    $series       = loadSeriesInformation();
    $categories   = loadCategoryInformation();
    
    $information = [];                
    foreach($items as $item)
    {        
        if(in_array($item,$toSkip))
        {
            continue;
        }
        $tmp = (object) [];
        // output('Parsing: ' .  $item);
        $contents = file_get_contents($item);
        $contents = deTab($contents);
        
        ob_start();
        $_GET['widget'] = false;
        eval('?>'.$contents);           
        $markdownContents = deTab( ob_get_clean() );        
        unset($_GET['widget']);
        
        $htmlContents     = Markdown::defaultTransform($markdownContents);                    
        
        $title    = getTitleFromContents($markdownContents);
        // output("    TITLE: $title");
        $tmp->title = $title;
        
        $slug     = getSlugFromContents($markdownContents);

        // output("    SLUG:  $slug");  
        $tmp->slug = $slug;
        
        $post     = getPostContentsFromContents($htmlContents);
        
        $tmp->content = $post;
        
        $excerpt  = getExcerptFromPost($post);
        // output("    EXCERPT: $excerpt");
        $tmp->excerpt = $excerpt;
        
        $date     = getPostDateFromConetnts($htmlContents);
        // output("    DATE: $date");
        $tmp->date = $date;
        
        $category_callback = function($slug) use ($categories){
            foreach($categories as $category=>$urls)
            {
                if(!in_array($slug, $urls))
                {
                    continue;
                }
                return $category;
            }
            return null;
        };
        
        $tmp->category = call_user_func($category_callback, $slug);
        
        $closure = function($slug) use ($series){
            foreach($series as $seriesSlug=>$info)
            {            
                $links = array_map(function($array){
                    return $array['link'];
                }, $info['articles']);
                if(in_array($slug, $links))
                {
                    $info['seriesSlug'] = $seriesSlug;
                    return $info;
                }                
            }
        };
        $tmp->series = $closure($slug);
        
        $information[] = $tmp;
    }

    return $information;
}

function loadCategoryInformation()
{
    $filename = '/tmp' . '/alanstorm_dot_com_categories_' . date('Y-m-d',time()) . '.ser';
    if(!file_exists($filename))
    {    
        $urls = \Pulsestorm\Magento2\Cli\Orphan_Content\fetchAllUrls();
        $urls = \Pulsestorm\Magento2\Cli\Orphan_Content\normalizeUrls($urls);
        $urls = \Pulsestorm\Magento2\Cli\Orphan_Content\removeIrrelevantDataFromUrls($urls);
        unset($urls['archive']);
        $urls = json_encode($urls);        
        file_put_contents($filename, $urls);
    }   
    return json_decode(file_get_contents($filename));
}

function getCategoryNameFromNiceName($nicename)
{
    $names = [
        'magento'=>'Magento',
        'magento-2'=>'Magento 2',
        'laravel'=>'Laravel',
        'oro'=>'OroCRM',
        'modern_php'=>'Modern PHP',  
        'sugarcrm'=>'SugarCRM',      
        'applescript'=>'AppleScript',       
        'drupal'=>'Drupal',
        'webos'=>'WebOS',
        'python'=>'Python',        
    ];
    if(!isset($names[$nicename]))
    {
        throw new Exception("Need nice name label for $nicename at " . __LINE__);
    }
    return $names[$nicename];
}

/**
 * Dupe slugs from renames, etc.
 */
function getEditedNotPublishedSlugs()
{
    return ['/improving_bbedits_go_to_line'      , 
        '/magento_admin_hello_world_revisited '  ,
        '/magento_commerce_bug_access_controle'  ,
        '/magento_config_a_critiqu_and_caching'  ,
        '/magento_connect_validate '             ,
        '/magento_layout_diectory_climbing'      ,
        '/magento_ultimate_module_creator_review_1',
        '/magneto_2_object_manager_instance_objects',
        '/magento_module_creator_ultimate_review',
//         '/magneto_listener_lifecycle_block',
//         '/magento_listener_lifecycle_block'
        ];
}

function addPostsItemsToXml($xml)
{
    $information = taskGetPostInformation();
    $slugsToSkip = getEditedNotPublishedSlugs();
    foreach($information as $post)
    {
        if(in_array($post->slug, $slugsToSkip)) { continue; }
        createItem($xml, $post);
    }	
}

function addCategoriesToXml($xml)
{
    $categories   = loadCategoryInformation();
    foreach($categories as $category=>$urls)
    {
// 	<wp:category>
// 		<wp:term_id>11</wp:term_id>
// 		<wp:category_nicename><![CDATA[applescript]]></wp:category_nicename>
// 		<wp:category_parent><![CDATA[]]></wp:category_parent>
// 		<wp:cat_name><![CDATA[AppleScript]]></wp:cat_name>
// 	</wp:category>
	    $xmlCategory = $xml->addChild('wp:category',null,'wp');
        $xmlCategory->addChildCdata('wp:category_nicename', $category, 'wp');
        $xmlCategory->addChildCdata('wp:cat_name', getCategoryNameFromNiceName($category), 'wp');

    }
}

function checkForDupes($series)
{
    foreach($series as $key=>$info)
    {
        \Pulsestorm\Pestle\Library\output("Starting $key");
        $slugs = array_map(function($info){
            return $info['link'];
        }, $info['articles']);
        
        foreach($slugs as $slug)
        {
            $filtered = array_filter($series, function($info) use ($slug){
                $slugs = array_map(function($article){
                    return $article['link'];
                }, $info['articles']);
                
                return in_array($slug, $slugs);
            });
            if(count($filtered) > 1)
            {
                \Pulsestorm\Pestle\Library\output("$slug appears in " . count($filtered) ." series");
                $titles = array_map(function($item){
                    \Pulsestorm\Pestle\Library\output('    Series: ' . $item['title']);            
                }, $filtered);
            }
        }        
        \Pulsestorm\Pestle\Library\output("Processed $key");
    }
}

function addSeriesToXml($xml)
{
    $series = loadSeriesInformation();
    checkForDupes($series);
    
    foreach($series as $info)
    {
        $term           = $xml->addChild('wp:term', null, 'wp');
        $tax            = $term->addChildCdata('wp:term_taxonomy', 'series', 'wp');
        $slug           = $term->addChildCdata('wp:term_slug', (explode('#', $info['link']))[1], 'wp');
        $parent         = $term->addChildCdata('wp:term_parent', '', 'wp');
        $name           = $term->addChildCdata('wp:term_name', $info['title'], 'wp');
        $description    = $term->addChildCdata('wp:term_description', $info['descriptionFull'], 'wp');
    }
}

/**
* ALPHA: WordPress export script
* @command wp-export-xml
*/
function pestle_cli($argv)
{

    // output("2004 articles");
//     output("\$toSkip articles");
//     output("Articles without structure category information. Apple Script, Drupal, Python, Web OS articles");
// output("DONE: Add Categories to XML");
// output("DONE: Add Series to XML");
//     output("DONE: Remove <promo>");
//     output('DONE: Post "Improving BBEdit\'s "Go To Line"" already exists.
// DONE: Post "Magento 2: KnockoutJS Integration" already exists.
// DONE: Post "Magento Admin Hello World Revisited" already exists.
// DONE: Post "Commerce Bug Tutorial: Access Control" already exists.
// DONE: Post "Magento Config: A Critique and Caching" already exists.
// DONE: Post "Validating a Magento Connect Extension" already exists.
// DONE: Post "Directory Climbing in Magento Layout" already exists.
// DONE: Post "Magento Ultimate Module Creator Review" already exists.
// DONE: Post "Magento Ultimate Module Creator Review" already exists.
// DONE: Post "Magento Block Lifecycle Methods" already exists.');

    $xml  = getInitialNode();
    $channel = $xml->addChild('channel');
    $channel->addChild('wp:wxr_version','1.2', 'wp');
    
    addPostsItemsToXml($channel);
    addCategoriesToXml($channel);
    addSeriesToXml($channel);        

    $xmlString = $xml->asXml();

    $xmlString = str_replace(' xmlns:wp="wp"','', $xmlString);
    $xmlString = str_replace(' xmlns:excerpt="excerpt"','', $xmlString);
    $xmlString = str_replace(' xmlns:dc="dc"','', $xmlString);     
    $xmlString = str_replace(' xmlns:content="content"','', $xmlString);         
     
    \Pulsestorm\Pestle\Library\output(
        // formatXmlString(
            $xmlString
        // )
    );
    
}

function pestle_cli_exported($argv)
{
    return pestle_cli($argv);
}}
namespace Pulsestorm\Wordpress\Parse\Urls{
use function Pulsestorm\Pestle\Importer\pestle_import;



/**
* ALPHA: WordPress file parser
* @command wordpress:parse:urls
*/
function pestle_cli($argv)
{
    return \Pulsestorm\Wordpress\Urls\pestle_cli_exported($argv);
}
}
namespace Pulsestorm\Wordpress\Urls{
use function Pulsestorm\Pestle\Importer\pestle_import;








function getUrlType($url)
{
    $parts = parse_url($url);
    
    //homepage
    if($parts['path'] === '/')
    {
        return 'homepage';
    }
    
    //static content
    $pathParts = explode('/', $parts['path']);
    $pathParts = array_values(array_filter($pathParts));
    if($pathParts[0] === 'wp-content')
    {
        return 'static-wp-content';
    }
    
    if($pathParts[0] === 'wp-includes')
    {
        return 'static-wp-includes';
    }

    if($pathParts[0] === 'wp-json')
    {
        return 'wp-json';
    }
     
    if($pathParts[0] === 'category')
    {
        return 'listing-category';
    }
     
    if($pathParts[0] === 'author')
    {
        return 'listing-author';
    }

    if($pathParts[0] === 'series')
    {
        return 'listing-series';
    }
                        
    //an RSS feed
    $last = array_pop($pathParts);
    $pathParts[] = $last;
    if($last === 'feed')
    {
        return 'feed';
    }
    
    if(count($pathParts) === 1)
    {
        return 'page-or-post';
    }
    
    if( count($pathParts) === 2     && 
        is_numeric($pathParts[0])   &&
        is_numeric($pathParts[1]))
    {
        return 'listing-date';
    }
    
    throw new \Exception("Unknown URL type");
}

function taskUrlTypes()
{

    $array = include '/private/tmp/export.urls.php';
    $report = [];    
    foreach($array as $url=>$boolean)
    {
        $tmp = array();
        $type = getUrlType($url);        
        \Pulsestorm\Pestle\Library\output($type . '::' . $url);
        
//         $parts = preg_split('https?://', $url);
//         var_dump($parts);
        // $report[$url] = $tmp;
    }
    
    // var_dump($report);
    // exit;

}

/**
* ALPHA: WordPress file parser
* @command wp-urls
*/
function pestle_cli($argv)
{
    $xml = simplexml_load_file('/Users/alanstorm/Desktop/import.xml');
    
    $children = [];
    $namespaces = $xml->getNameSpaces(true);
    foreach($xml->channel->children() as $item)
    {
        if($item->getName() !== 'item') { continue; }
        foreach($item->children($namespaces['wp']) as $child)
        {
            if($child->getName() !== 'post_name'){ continue;}
            $children[] = (string) $child;
        }
    }
    
    $children = array_filter($children, function($item){
        return strToLower($item) !== $item;
    });
    var_dump($children);
    
}

function pestle_cli_exported($argv, $options=[])
{
    return pestle_cli($argv);
}    }
namespace Pulsestorm\Xml_Library{
use DomDocument;
use Exception;
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

function simpleXmlAddNodesXpathReturnOriginal($xml, $path)
{
    $result = simpleXmlAddNodesXpathWorker($xml, $path);
    return $result['original'];
}

function simpleXmlAddNodesXpath($xml, $path)
{
    $result = simpleXmlAddNodesXpathWorker($xml, $path);
    return $result['child'];
}

function simpleXmlAddNodesXpathWorker($xml, $path)
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

    return [
        'original'=>$xml,
        'child'=>$node
    ];
}

function getByAttributeXmlBlockWithNodeNames($attributeName, $xml, $value, $names=null)
{
    $search = '//*[@'.$attributeName.'="' . $value . '"]';
    $nodes = $xml->xpath($search);
    $nodes = array_filter($nodes, function($node) use ($names){
        if(!$names) { return true; }
        return in_array($node->getName(), $names);
    });
    return $nodes;
}

function getNamedXmlBlockWithNodeNames($xml, $name, $names)
{
    return getByAttributeXmlBlockWithNodeNames('name', $xml, $name, $names);
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
