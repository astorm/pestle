#!/bin/bash

## Our travis CI/CD will run this script from the root of a Magento
## folder.  Put pestle commands here to generate code in the
## Pulsestorm_Travis module.  At the end of the CI we'll run phpcs
## against this folder to ensure generate code


## putting some basic files in place so we can merge and tackle
## the "phpcs-ification" of things in stages
mkdir -p app/code/Pulsestorm/Travis
printf "<?php\nnamespace Pulsestorm\\Travis;\n\nclass Foo\n{\n}\n"  > app/code/Pulsestorm/Travis/Test.php

## test generate:crud-model, rm InstallSchema backup file
pestle.phar magento2:generate:crud-model Pulsestorm_Travis Thing
find app/code/Pulsestorm/Travis -type f -name '*.bak*' -exec rm '{}' \;