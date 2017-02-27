#!/bin/bash
VER_FROM_TAG=`git tag | tail -n 1 | xargs echo 'pestle Ver'`
VER_FROM_PESTLE=`pestle_dev version`

#if [ "$VER_FROM_TAG" != "$VER_FROM_PESTLE" ]
#then
#  echo "pestle version and latest tag do not match, bailing"
#  exit
#fi

echo "<?php" > library/all.php
find modules/ -name module.php | xargs pestle_dev pestle:export_module >> library/all.php