#!/bin/bash -x

# Increasing local web server performances as possible
# https://stackoverflow.com/questions/39842170/load-balancing-php-built-in-server/47103758#47103758

# get a random port -- this could be improved
port=$(shuf -i 2048-65000 -n 1)

# start the PHP server in the background
if [[ -d "$(realpath ${1:?Missing path to serve})" ]]; then
	php -S localhost:"${port}" -t "$(realpath ${1:?Missing path to serve})" &
else
	php -S localhost:"${port}" "$(realpath ${1:?Missing path to serve})" &
fi
pid=$!
sleep 0.2

# proxy standard in to nc on that port
nc localhost "${port}"

# kill the server we started
kill "${pid}"
