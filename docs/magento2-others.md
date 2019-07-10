## generate:remove-named-node

    Usage:
        $ pestle.phar magento2:generate:remove-named-node

    Arguments:

    Options:

    Help:
        Removes a named node from a generic XML configuration file

        @command magento2:generate:remove-named-node
        @argument path_xml The XML file? []
        @argument node_name The <node_name/>? [block]
        @argument name The {node_name}="" value? []

The `magento2:generate:remove-named-node` command will _delete_ a named XML node from a configuration file.  This is most useful is scripts that automate more complex module generation.

**Interactive Invocation**

    $ pestle.phar magento2:generate:remove-named-node
    The XML file? ()] app/code/Pulsestorm/Pestle/etc/events.xml
    The <node_name/>? (block)] observer
    The {node_name}="" value? ()] pulsestorm_pestle_listener_before_execute
    Node Removed

**Argument Invocation**

    $ pestle.phar magento2:generate:remove-named-node app/code/Pulsestorm/Pestle/etc/events.xml observer pulsestorm_pestle_listener_before_execute

In the above examples, if `events.xml` looks like this

    <?xml version="1.0"?>
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
        <event name="controller_action_predispatch">
            <observer name="pulsestorm_pestle_listener_before_execute" instance="Pulsestorm\Pestle\Observer\Listener\Before\Execute" />
        </event>
    </config>

after invoking the command it will look like this

    <?xml version="1.0"?>
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
        <event name="controller_action_predispatch"/>
    </config>

In Magento's XML configuration, `name` often needs to be a unique ID.  If you attempt to remove a named node that isn't unique in the XML file, pestle will not remove the node.

    $ pestle.phar magento2:generate:remove-named-node ... ... ...
    Bailing: Found more than one node.

Also, pestle will not proceed if your named node has child nodes.

    $ pestle.phar magento2:generate:remove-named-node app/code/Pulsestorm/Pestle/etc/events.xml event controller_action_predispatch
    Bailing: Contains child nodes

While this command exists in the `magento2:generate:` namespace, it will work on _any_ XML file where `name` is a de-facto unique identifier.

## generate:psr-log-level

    Usage:
        $ pestle.phar magento2:generate:psr-log-level

    Arguments:

    Options:

    Help:
        For conversion of Zend Log Level into PSR Log Level

        This command generates a list of Magento 1 log levels,
        and their PSR log level equivalents.

        @command magento2:generate:psr-log-level

The `magento2:generate:psr-log-level` command prints out a hard coded list of `Zend_Log` constants and their `Psr\Log\LogLevel` equivalents.

**Invocation**

    $ pestle.phar magento2:generate:psr-log-level
    Zend_Log::EMERG     Psr\Log\LogLevel::EMERGENCY
    Zend_Log::ALERT     Psr\Log\LogLevel::ALERT
    Zend_Log::CRIT      Psr\Log\LogLevel::CRITICAL
    Zend_Log::ERR       Psr\Log\LogLevel::ERROR
    Zend_Log::WARN      Psr\Log\LogLevel::WARNING
    Zend_Log::NOTICE    Psr\Log\LogLevel::NOTICE
    Zend_Log::INFO      Psr\Log\LogLevel::INFO
    Zend_Log::DEBUG     Psr\Log\LogLevel::DEBUG

This command is most useful if you're converting some old PHP code to use newer PSR loggers, and you need a quick reminder on constant equivalents.

## generate:class-child

    Usage:
        $ pestle.phar magento2:generate:class-child

    Arguments:

    Options:

    Help:
        Generates a child class, pulling in __constructor for easier di

        @command magento2:generate:class-child
        @argument class_child New Class Name?
        [Pulsestorm\Helloworld\Model\Something]
        @argument class_parent Parent Class?
        [Magento\Framework\Model\AbstractModel]

The `magento2:generate:class-child` command will automatically generate a new child class for any parent class in your Magento 2 system. The child class will include a  constructor that is "type-hint compatible" with the provided parent class.

**Interactive Invocation**

    $ pestle.phar magento2:generate:class-child
    New Class Name? (Pulsestorm\Helloworld\Model\Something)] Pulsestorm\Pestle\Model\Thing
    Parent Class? (Magento\Framework\Model\AbstractModel)] Magento\Framework\Model\AbstractModel

**Argument Invocation**

    $ pestle.phar magento2:generate:class-child 'Pulsestorm\Pestle\Model\Thing' 'Magento\Framework\Model\AbstractModel'

While a lot of Magento architects lobby for "no inheritance" being the right way to do object oriented programming, many of Magento's systems still rely heavily on class inheritance.  However, Magento's newer automatic constructor dependency injection system means classes often have a large number of constructor arguments. This makes class inheritance a tedious task. The architects say the solution is no inheritance and we go around in a circle.

The `magento2:generate:class-child` command eases some of this tediousness for working Magento programmers.  If you had invoked the above commands you would have generated a `Pulsestorm\Pestle\Model\Thing` class and constructor that looked like the following.

    $ cat app/code/Pulsestorm/Pestle/Model/Thing.php
    <?php
    namespace Pulsestorm\Pestle\Model;

    class Thing extends \Magento\Framework\Model\AbstractModel
    {
        function __construct(
            \Magento\Framework\Model\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
            \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
            array $data = []
        ) {
            parent::__construct($context,$registry,$resource,$resourceCollection,$data);
        }
    }

i.e. the constructor arguments from `Magento\Framework\Model\AbstractModel` will be automatically copied over, and a `parent::__construct` call will be automatically generated.

## generate:install

    Usage:
        $ pestle.phar magento2:generate:install

    Arguments:

    Options:

    Help:
        BETA: Generates commands to install Magento via composer

        @command magento2:generate:install
        @argument id_key Identity Key? [magento_2_new]
        @argument umask Default Umask? [000]
        @argument repo Composer Repo [https://repo.magento.com/]
        @argument composer_package Starting Package?
        [magento/project-community-edition]
        @argument folder Folder? [magento-2-source]
        @argument admin_first_name Admin First Name? [Alan]
        @argument admin_last_name Admin Last Name? [Storm]
        @argument admin_password Admin Password? [password12345]
        @argument admin_email Admin Email? [astorm@alanstorm.com]
        @argument admin_user Admin Username? [astorm@alanstorm.com]
        @argument db_host Database Host? [127.0.0.1]
        @argument db_user Database User? [root]
        @argument db_pass Database Password? [password12345]
        @argument email Admin Email? [astorm@alanstorm.com]

The `magento2:generate:install` command will generate a small script for installing Magento 2 via composer and the command line.

**Interactive Invocation**

    $ pestle.phar magento2:generate:install
    Identity Key? (magento_2_new)] magento2_pestle
    Default Umask? (000)] 000
    Composer Repo (https://repo.magento.com/)] https://repo.magento.com/
    Starting Package? (magento/project-community-edition)] magento/project-community-edition
    Folder? (magento-2-source)] magento-2-source
    Admin First Name? (Alan)] Alan
    Admin Last Name? (Storm)] Storm
    Admin Password? (password12345)] password12345
    Admin Email? (astorm@alanstorm.com)] astorm@alanstorm.com
    Admin Username? (astorm@alanstorm.com)] astorm@alanstorm.com
    Database Host? (127.0.0.1)] 127.0.0.1
    Database User? (root)] root
    Database Password? (password12345)] password12345
    Admin Email? (astorm@alanstorm.com)] astorm@alanstorm.com

**Argument Invocation**

    $ pestle.phar magento2:generate:install magento2_pestle '000' 'https://repo.magento.com/' magento/project-community-edition magento-2-source Alan Storm password12345 astorm@alanstorm.com astorm@alanstorm.com 127.0.0.1 root password12345 astorm@alanstorm.com

Running the above command would result in output something like the following.

    composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition magento-2-source
    cd magento-2-source
    echo '000' >> magento_umask
    echo "We're about to ask for your MySQL password so we can create the database"
    echo 'CREATE DATABASE magento2_pestle' | mysql -uroot -p
    php bin/magento setup:install --admin-email astorm@alanstorm.com --admin-firstname Alan --admin-lastname Storm --admin-password password12345 --admin-user astorm@alanstorm.com --backend-frontname admin --base-url http://magento2-pestle.dev --db-host 127.0.0.1 --db-name magento2_pestle --db-password password12345 --db-user root --session-save files --use-rewrites 1 --use-secure 0 -vvv
    php bin/magento sampledata:deploy
    php bin/magento cache:enable

Piping this output to a shell script (or copy/pasting the code to your terminal/shell window) and then running it will result in Magento 2 being installed on your local system.

**IMPORTANT**: The commands generated include setting Magento's umask.  This was (and technically still is) a workaround for file permission issues in Magento 2 when running under the Apache `mod_php` module.  While Magento product management indicated that Magento 2 could run under `mod_php`, most folks have found that PHP-FPM (or another FastCGI method) is required to get decent performance from Magento 2.

**Further Reading**

- [Magento 2: Set a PHP umask](https://alanstorm.com/magento-2-set-a-php-umask/)
- [Install Magento using Composer](https://devdocs.magento.com/guides/v2.3/install-gde/composer.html)
