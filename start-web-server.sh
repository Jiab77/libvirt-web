#!/usr/bin/env bash

# Minimal PHP server wrapper script
#
# Increasing local web server performances as possible
# https://www.php.net/manual/en/features.commandline.webserver.php

# Config
LISTEN_INTERFACE=${LISTEN_INTERFACE:-localhost}
LISTEN_PORT=${LISTEN_PORT:-8000}

# Detect server type to use
PHP_SRV_TYPE=$(php -r "if (version_compare(phpversion(), '7.4', '<')) { echo 'singlecore'; } else { echo 'multicore'; }")

# Run detected server type
if [[ $PHP_SRV_TYPE == 'multicore' ]]; then
	PHP_CLI_SERVER_WORKERS=$(nproc) php -S "${LISTEN_INTERFACE}:${LISTEN_PORT}" libvirtweb.php
	# PHP_CLI_SERVER_WORKERS=$(nproc) php -S "${LISTEN_INTERFACE}:${LISTEN_PORT}" -t .
else
	php -S "${LISTEN_INTERFACE}:${LISTEN_PORT}" libvirtweb.php
	# php -S ${LISTEN_INTERFACE}:${LISTEN_PORT} -t .
fi
