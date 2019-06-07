Magento's backend admin application has hierarchical Access Control List rules that determine a user's rights in the system.  Users are assigned a role, roles are assigned specific rules.

The `magento2:generate:acl` and `magento2:generate:acl:change-title` commands generate the XML needed to add new rules to the system.

[Learn more about Magento 2 Access Control Rules](https://alanstorm.com/magento_2_understanding_access_control_list_rules/)

## magento2:generate:acl

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

The `magento2:generate:acl` command will generate an acl.xml file for your module. A typical run looks like this.

    $ pestle.phar magento2:generate:acl
    Which Module? (Pulsestorm_HelloWorld)] Pulsestorm_Pestle
    Rule IDs? (Pulsestorm_Pestle::top,Pulsestorm_Pestle::config,)]
    Created /path/to/magento/app/code/Pulsestorm/Pestle/etc/acl.xml

The first argument is the full name of the module where you want to create an access control rule.
## magento2:generate:acl:change-title

TODO: WRITE THE DOCS!

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

## magento2:generate:controller-edit-acl

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

## magento2:generate:menu

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





