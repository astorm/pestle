#!/bin/sh

vendor/bin/phing package_phar
chmod +x pestle.phar
#mv pestle.phar ~/bin/pestle.phar