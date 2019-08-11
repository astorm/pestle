#!/bin/bash
sudo printf '#!/usr/bin/env php \n<?php\nrequire "' > /usr/bin/pestle_dev
sudo printf `pwd` >> /usr/bin/pestle_dev
sudo printf '/runner.php";' >> /usr/bin/pestle_dev
sudo chmod +x /usr/bin/pestle_dev
