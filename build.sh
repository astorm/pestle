#!/bin/sh

php -d phar.readonly=0 vendor/bin/phing package_phar
chmod +x pestle.phar
#mv pestle.phar ~/bin/pestle.phar
