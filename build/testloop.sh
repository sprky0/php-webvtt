#!/bin/bash
while :
do
	./vendor/phpunit/phpunit/phpunit --bootstrap ./vendor/autoload.php ./tests
	sleep 1
done
