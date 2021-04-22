docker_start:
	docker-compose --env-file=.env.local -f docker-compose.yaml up -d

docker_stop:
	docker-compose --env-file=.env.local -f docker-compose.yaml stop

docker_restart:
	docker-compose --env-file=.env.local -f docker-compose.yaml restart

docker_php:
	docker-compose exec php bash

rr_dev:
	./bin/rr serve -c .rr.dev.yaml --dotenv=.

rr_prod:
	./bin/rr serve -c .rr.yaml --dotenv=.