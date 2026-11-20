##
## PromptEdit — Docker helpers
## Usage: make <target>
##

.PHONY: help build up down restart logs shell migrate seed test prod-build

COMPOSE         = docker compose -f docker-compose.yml
COMPOSE_DEV     = docker compose -f docker-compose.dev.yml
APP_CONTAINER   = promptedit_app

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ── Production ──────────────────────────────────────────────

build: ## Build production image
	$(COMPOSE) build --no-cache

up: ## Start production stack (detached)
	$(COMPOSE) up -d

down: ## Stop production stack
	$(COMPOSE) down

restart: ## Restart app container
	$(COMPOSE) restart app

logs: ## Tail app logs
	$(COMPOSE) logs -f app

shell: ## Shell into app container
	$(COMPOSE) exec app sh

migrate: ## Run migrations in production
	$(COMPOSE) exec app php artisan migrate --force

seed: ## Run database seeders in production
	$(COMPOSE) exec app php artisan db:seed --force

test: ## Run test suite inside container
	$(COMPOSE) exec app php artisan test

cache-clear: ## Clear all Laravel caches
	$(COMPOSE) exec app php artisan optimize:clear

cache-warm: ## Warm all Laravel caches
	$(COMPOSE) exec app php artisan optimize

# ── Development ─────────────────────────────────────────────

dev-build: ## Build development image
	$(COMPOSE_DEV) build --no-cache

dev-up: ## Start development stack (detached)
	$(COMPOSE_DEV) up -d

dev-down: ## Stop development stack
	$(COMPOSE_DEV) down

dev-logs: ## Tail development logs
	$(COMPOSE_DEV) logs -f app vite

dev-shell: ## Shell into development app container
	$(COMPOSE_DEV) exec app sh
