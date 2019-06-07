## magento2:generate:remove-named-node

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


## magento2:generate:service-contract

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:service-contract

    Arguments:

    Options:

    Help:
        ALPHA: Service Contract Generator

        @command magento2:generate:service-contract
        @option skip-warning Allows user to skip experimental warning


## magento2:generate:psr-log-level

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


## magento2:generate:config-helper

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


## magento2:generate:class-child

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


