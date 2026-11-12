# Every command runs in Docker; a local PHP is not required.
export UID := $(shell id -u)
export GID := $(shell id -g)

COMPOSE := docker compose -f docker/compose.yaml
RUN     := $(COMPOSE) run --rm app

.PHONY: help build install qa test test-integration stan cs cs-fix demo artisan shell down

help:              ## List the commands
	@grep -E '^[a-z-]+:.*##' $(MAKEFILE_LIST) | sed 's/:.*##/ -/'

build:             ## Build the Docker image
	$(COMPOSE) build app

install: build     ## Install dependencies
	$(RUN) composer install --no-interaction

qa:                ## Coding style, static analysis with architecture rules, tests
	$(RUN) composer qa

test:              ## Tests that need no database
	$(RUN) composer test

test-integration:  ## Tests of the Laravel adapters against MySQL
	$(COMPOSE) up -d --wait mysql
	$(RUN) composer test:integration

stan:              ## Static analysis only
	$(RUN) composer stan

cs:                ## Check coding style
	$(RUN) composer cs

cs-fix:            ## Fix coding style
	$(RUN) composer cs:fix

demo:              ## Play the reference scenario through the command bus and print the history
	$(RUN) php bin/earning-line demo

artisan:           ## Run an artisan command against MySQL: make artisan cmd="about"
	$(COMPOSE) up -d --wait mysql
	$(RUN) php app/artisan $(cmd)

shell:             ## Shell inside the container
	$(RUN) bash

down:              ## Stop MySQL
	$(COMPOSE) down
