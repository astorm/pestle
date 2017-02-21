#!/bin/sh

VER_FROM_TAG=`git tag | tail -n 1 | xargs echo 'pestle Ver'`
VER_FROM_PESTLE=`pestle_dev version`

if [ "$VER_FROM_TAG" != "$VER_FROM_PESTLE" ]
then
  echo "pestle version and latest tag do not match, bailing"
  exit
fi

php -d phar.readonly=0 vendor/bin/phing package_phar
chmod +x pestle.phar
#mv pestle.phar ~/bin/pestle.phar
