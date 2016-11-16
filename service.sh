#!/bin/sh

php swoole_im_server.php
php -S 127.0.0.1:8080

ps -ef | grep swoole_im_server.php | grep -v grep
ps -ef | grep swoole_im_server.php | grep -v grep | awk '{print $2}' | xargs kill -9