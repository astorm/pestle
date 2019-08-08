<?php
namespace Pulsestorm\Cli\Code_Generation;
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

    $namespace = str_replace('-', '', $namespace);
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
    $attrs = substr($attrs, 0, -3) . ' ]';

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

function generateInstallSchemaGetDefaultColumnId($id_prefix)
{
    $id = [
        'name'          => strtolower($id_prefix) . '_id',
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
function generateInstallSchemaGetDefaultColumns($id_prefix)
{
    $id            = generateInstallSchemaGetDefaultColumnId($id_prefix);
    $title         = generateInstallSchemaGetDefaultColumnTitle();
    $creation_time = generateInstallSchemaGetDefaultColumnCreationTime();
    $update_time   = generateInstallSchemaGetDefaultColumnUpdateTime();
    $is_active     = generateInstallSchemaGetDefaultColumnIsAction();
    return [$id, $title, $creation_time, $update_time, $is_active];
}

function generateInstallSchemaTable($table_name='', $id_prefix='', $columns=[], $comment='',$indent=false)
{
    $indent = $indent ? $indent : '        ';
    $block = $indent . '$table = ' . generateInstallSchemaNewTable($table_name);
    $default_columns = generateInstallSchemaGetDefaultColumns($id_prefix);
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
    $phpDoc = '/**'. "\n" .
    ' * Interface ' . $name . "\n" .
    ' *' . "\n" .
    ' * @api' . "\n" .
    ' */';

    $template   = '<' . '?' . 'php' . "\n" .
    'namespace ' . implode('\\',$parts) . ";\n" .
    "\n" .$phpDoc . "\n" .
    "interface $name\n{\n";
    foreach($functions as $function)
    {
        $template .=
'    function '.$function.'();' . "\n";
    }
    $template   .= "}";

    return $template;
}

function templateMethod($accessLevel, $name, $docBlock='')
{
    return $docBlock . '
    '.$accessLevel.' function '.$name.'(<$params$>)
    {
<$methodBody$>
    }
';
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
    $phpDoc = '/**'. "\n" .
    ' * Class ' . $name . "\n" .
    ' */';

    $template = '<' . '?' . 'php' . "\n" .
    'namespace ' . implode('\\',$parts) . ";\n";
    $template .= "\n";

    if($includeUse)
    {
        $template .= '<$use$>' . "\n\n";
    }
    $template .= $phpDoc . "\n";
    $template .= "class $name";
    if($extends)
    {
        $template .= " extends $extends";
    }
    if($implements)
    {
        if (strpos($implements, ',') !== false) {
            $implements = explode(',', $implements);
            $i = count($implements);
            $template .= " implements" . "\n";
            foreach ($implements as $implement) {
                $template .= '    ' . $implement;
                if ($i - 1 > 0) {
                    $template .= ',' . "\n";
                    $i--;
                }
            }
        }
        else {
            $template .= " implements $implements";
        }
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
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->resultPageFactory->create();
    }';

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
}
