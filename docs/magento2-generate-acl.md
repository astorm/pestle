## generate:acl

    Usage:
        $ pestle.phar magento2:generate:acl

    Arguments:

    Options:

    Help:
        Generates a Magento 2 acl.xml file.

        @command magento2:generate:acl
        @argument module_name Which Module? [Pulsestorm_HelloWorld]
        @argument rule_ids Rule IDs?
        [<$module_name$>::top,<$module_name$>::config,]

The `magento2:generate:acl` command will generate a new *Access Control Rule* for your module.

**Interactive Invocation**

    $ pestle.phar magento2:generate:acl
    Which Module? (Pulsestorm_HelloWorld)] Pulsestorm_Pestle
    Rule IDs? (Pulsestorm_Pestle::top,Pulsestorm_Pestle::config,)]
    Created /path/to/m2/app/code/Pulsestorm/Pestle/etc/acl.xml

**Argument Invocation**

    $ pestle.phar magento2:generate:acl Pulsestorm_Pestle "Pulsestorm_Pestle::top,Pulsestorm_Pestle::config"
    Created /path/to/m2/app/code/Pulsestorm/Pestle/etc/acl.xml

The first argument is is module you want to create the access control rule for.  The second argument is a list of rules, each representing one of level of the access control tree.  Pestle will create any nodes needed to reach the bottom of the tree, starting from the `<resource id="Magento_Backend::admin">` node.

For example, when you run the following command

    pestle.phar magento2:generate:acl Pulsestorm_Pestle "Pulsestorm_Pestle::top,Pulsestorm_Pestle::config"

pestle will create a tree that looks like this.

    /path/to/m2/app/code/Pulsestorm/Pestle/etc/acl.xml
    <?xml version="1.0"?>
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
        <acl>
            <resources>
                <resource id="Magento_Backend::admin">
                    <resource id="Pulsestorm_Pestle::top" title="TITLE HERE FOR">
                        <resource id="Pulsestorm_Pestle::config" title="TITLE HERE FOR"/>
                    </resource>
                </resource>
            </resources>
        </acl>
    </config>

**Further Reading**

- [Magento 2: Understanding Access Control List Rules](https://alanstorm.com/magento_2_understanding_access_control_list_rules/)

## generate:acl:change-title

    Usage:
        $ pestle.phar magento2:generate:acl:change-title

    Arguments:

    Options:

    Help:
        Changes the title of a specific ACL rule in a Magento 2 acl.xml file

        @command magento2:generate:acl:change-title
        @argument path_acl Path to ACL file?
        @argument acl_rule_id ACL Rule ID?
        @argument title New Title?

The  `generate:acl:change-title` will edit the value in a an ACL rule's title field

**Interactive Invocation**

    $ pestle.phar magento2:generate:acl:change-title
    Path to ACL file? ()] app/code/Pulsestorm/Pestle/etc/acl.xml
    ACL Rule ID? ()] Pulsestorm_Pestle::config
    New Title? ()] Configuration Settings
    Changed Title

**Argument Invocation**

    $ pestle.phar magento2:generate:acl:change-title app/code/Pulsestorm/Pestle/etc/acl.xml Pulsestorm_Pestle::config "Configuration Settings"
    Changed Title

In the above scenarios, running pestle would change the following access control node from

    <resource id="Pulsestorm_Pestle::config" title="TITLE HERE FOR"/>

to

    <resource id="Pulsestorm_Pestle::config" title="Configuration Settings"/>

This command is most useful for changing the default titles after using the [`magento2-generate-acl`](https://pestle.readthedocs.io/en/latest/magento2-generate-acl/#generateacl) command.

**Further Reading**

- [Magento 2: Understanding Access Control List Rules](https://alanstorm.com/magento_2_understanding_access_control_list_rules/)

## generate:controller-edit-acl

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:controller-edit-acl

    Arguments:

    Options:

    Help:
        Edits the const ADMIN_RESOURCE value of an admin controller

        @command magento2:generate:controller-edit-acl
        @argument path_controller Path to Admin Controller
        @argument acl_rule Path to Admin Controller

## generate:menu

TODO: WRITE THE DOCS!

    Usage:
        $ pestle.phar magento2:generate:menu

    Arguments:

    Options:

    Help:
        Generates configuration for Magento Adminhtml menu.xml files

        @command magento2:generate:menu
        @argument module_name Module Name? [Pulsestorm_HelloGenerate]
        @argument parent @callback selectParentMenu
        @argument id Menu Link ID [<$module_name$>::unique_identifier]
        @argument resource ACL Resource [<$id$>]
        @argument title Link Title [My Link Title]
        @argument action Three Segment Action [frontname/index/index]
        @argument sortOrder Sort Order? [10]





