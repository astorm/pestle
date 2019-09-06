<?php
namespace Pulsestorm\Magento2\Generate\Registerpackage;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');

pestle_import('Pulsestorm\Pestle\Library\exitWithErrorMessage');

/**
* This command will register a folder on your computers
* as the composer package for a particular module. This
* will tell pestle that files for this particular module
* should be generated in this folder.
*
* This command will also, if neccesary, create the module's
* registration.php file and composer.json file.
*
* If your module already has a composer.json, this command
* will look for a psr-4 autoload section for the module
* namespace.  If found, code will be generated in the
* configured folder. If not found, this command will add
* the `src/` folder as a psr-4 autoloader for your module
* namespace.
*
* @command magento2:generate:register-package
*/
function pestle_cli($argv)
{
    output("Hello Sailor");
}
