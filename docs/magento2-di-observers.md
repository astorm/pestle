## magento2:generate:di

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:di

    Arguments:

    Options:

    Help:
        Injects a dependency into a class constructor
        This command modifies a preexisting class, adding the provided
        dependency to that class's property list, `__construct` parameters
        list, and assignment list.

        pestle.phar magento2:generate:di
        app/code/Pulsestorm/Generate/Command/Example.php
        'Magento\Catalog\Model\ProductFactory'

        @command magento2:generate:di
        @argument file Which PHP class file are we injecting into?
        @argument class Which class to inject?
        [Magento\Catalog\Model\ProductFactory]

## magento2:generate:plugin-xml

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:plugin-xml

    Arguments:

    Options:

    Help:
        Generates plugin XML
        This command generates the necessary files and configuration
        to "plugin" to a preexisting Magento 2 object manager object.

        pestle.phar magento2:generate:plugin_xml Pulsestorm_Helloworld
        'Magento\Framework\Logger\Monolog'
        'Pulsestorm\Helloworld\Plugin\Magento\Framework\Logger\Monolog'

        @argument module_name Create in which module? [Pulsestorm_Helloworld]
        @argument class Which class are you plugging into?
        [Magento\Framework\Logger\Monolog]
        @argument class_plugin What's your plugin class name?
        [<$module_name$>\Plugin\<$class$>]
        @option use-type-hint Add type hint to subject?
        @command magento2:generate:plugin-xml


## magento2:generate:preference

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:preference

    Arguments:

    Options:

    Help:
        Generates a Magento 2.1 ui grid listing and support classes.

        @command magento2:generate:preference
        @argument module Which Module? [Pulsestorm_Helloworld]
        @argument for For which Class/Interface/Type?
        [Pulsestorm\Helloworld\Model\FooInterface]
        @argument type New Concrete Class?
        [Pulsestorm\Helloworld\Model\NewModel]


## magento2:generate:observer

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:observer

    Arguments:

    Options:

    Help:
        Generates Magento 2 Observer
        This command generates the necessary files and configuration to add
        an event observer to a Magento 2 system.

        pestle.phar magento2:generate:observer Pulsestorm_Generate
        controller_action_predispatch pulsestorm_generate_listener3
        'Pulsestorm\Generate\Model\Observer3'

        @command magento2:generate:observer
        @argument module Full Module Name? [Pulsestorm_Generate]
        @argument event_name Event Name? [controller_action_predispatch]
        @argument observer_name Observer Name? [<$module$>_listener]
        @argument model_name @callback getModelName


