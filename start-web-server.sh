#!/bin/bash

# Increasing local web server performances as possible
# https://stackoverflow.com/questions/39842170/load-balancing-php-built-in-server/47103758#47103758
# https://www.php.net/manual/en/features.commandline.webserver.php

# Config
LISTEN_INTERFACE="localhost"
LISTEN_PORT=8000

# Detect server type to use
PHP_SRV_TYPE=$(php -r "if (version_compare(phpversion(), '7.4', '<')) { echo 'tcpserver'; } else { echo 'embedded'; }")

# Run detected server type
if [[ $PHP_SRV_TYPE == 'embedded' ]]; then
	PHP_CLI_SERVER_WORKERS=$(nproc) php -S ${LISTEN_INTERFACE}:${LISTEN_PORT} libvirtweb.php
else
	tcpserver -v -1 0 ${LISTEN_PORT} ./proxy-to-php-server.sh ./libvirtweb.php
fi
