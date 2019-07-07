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
## generate:service-contract

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:service-contract

    Arguments:

    Options:

    Help:
        ALPHA: Service Contract Generator

        @command magento2:generate:service-contract
        @option skip-warning Allows user to skip experimental warning


## generate:psr-log-level

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:psr-log-level

    Arguments:

    Options:

    Help:
        For conversion of Zend Log Level into PSR Log Level

        This command generates a list of Magento 1 log levels,
        and their PSR log level equivalents.

        @command magento2:generate:psr-log-level


## generate:config-helper

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:config-helper

    Arguments:

    Options:

    Help:
        Generates a help class for reading Magento's configuration

        This command will generate the necessary files and configuration
        needed for reading Magento 2's configuration values.

        @command magento2:generate:config-helper
        @todo needs to be implemented


## generate:class-child

TODO: WRITE THE DOCS!

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


## generate:install

TODO: WRITE THE DOCS!

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
