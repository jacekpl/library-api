#!/bin/sh
set -e

if [ -z "$1" ] || [ "${1#-}" != "$1" ] || [ "$1" = 'frankenphp' ]; then
	until php bin/console dbal:run-sql 'SELECT 1' >/dev/null 2>&1; do
		echo 'Waiting for the database to be ready...'
		sleep 2
	done

	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec docker-php-entrypoint "$@"
