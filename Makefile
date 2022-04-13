setup:

dev:
	composer install
	yarn install
	yarn dev

db:
	./bin/console doctrine:migrations:migrate
