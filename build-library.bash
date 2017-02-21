#!/bin/bash
echo "<?php" > library/all.php
find modules/ -name module.php | xargs pestle_dev pestle:export_module >> library/all.php