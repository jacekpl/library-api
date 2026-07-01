.DEFAULT_GOAL := help
COMPOSE := docker compose

.PHONY: help
help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-12s\033[0m %s\n", $$1, $$2}'

.PHONY: up
up: ## Build (if needed) and start the whole stack
	$(COMPOSE) up -d --build

.PHONY: down
down: ## Stop the stack
	$(COMPOSE) down

.PHONY: destroy
destroy: ## Stop the stack and delete the database volume
	$(COMPOSE) down -v

.PHONY: build
build: ## Build the application image
	$(COMPOSE) build

.PHONY: logs
logs: ## Follow the application logs
	$(COMPOSE) logs -f app

.PHONY: sh
sh: ## Open a shell in the application container
	$(COMPOSE) exec app sh

.PHONY: migrate
migrate: ## Run database migrations against the running stack
	$(COMPOSE) exec app php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: test
test: ## Run the test suite (unit + functional) in a throwaway container
	$(COMPOSE) run --rm -e APP_ENV=test app sh -lc "\
		php bin/console doctrine:database:create --if-not-exists && \
		php bin/console doctrine:migrations:migrate --no-interaction && \
		php vendor/bin/phpunit"
