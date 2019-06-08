## generate:remove-named-node

TODO: WRITE THE DOCS!

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
