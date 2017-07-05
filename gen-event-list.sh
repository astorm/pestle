#!/bin/bash
#requires pcre2grep
#run this command from a magento root directory to generate a (incomplete?) list of emitted events
#to be used with observer generate autocomplete

MAGE_ROOT=app/code/Magento

pcre2grep  --buffer-size=50M  -M -r  -o1 "eventManager->dispatch\s*\(\s*([\'\"][^\'\"]*[\'\"])"  $MAGE_ROOT  \
| sed -e 's/.*://g' \
| xargs --replace=EVENT echo 'echo "EVENT"'

#-M is for multiline match
#-r recusrive search
#-o1 outputs only the first capture group (our event)

#regex matches all dispatched events that match regex
#regex matches eventManager->dispatch 
#followed by white space then a literal (
#then any number of white space
#then we capture the first string that appears
#    capture a ' or a "
#    followed by any number of anything that's not a ' or a "
#    followed by the  matching ' or a "

#this regex will also capture php code within "" that's meant to be evaluated
#    such as "admin_system_config_changed_section_{$this->getSection()}" (so far the only match)
#it will also capture strings that begin with a ' and end with a " or vice versa (minor todo: we don't want that, but it shouldn't matter)

#the sed erases the directory paths
#the xargs generates the final echo command list to be used