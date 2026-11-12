# Every command runs in Docker; a local PHP is not required.
export UID := $(shell id -u)
export GID := $(shell id -g)

COMPOSE := docker compose -f docker/compose.yaml
RUN     := $(COMPOSE) run --rm app

.PHONY: help build install qa test test-integration mutation stan cs cs-fix demo artisan serve shell down

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

mutation:          ## Mutation testing of the core; the report is written to build/infection
	$(RUN) composer mutation

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

PORT ?= 8000

serve:             ## Serve the HTTP API on localhost:8000; another port: make serve PORT=8080
	$(COMPOSE) up -d --wait mysql
	$(COMPOSE) run --rm -p $(PORT):8000 app php app/artisan serve --host=0.0.0.0 --port=8000

shell:             ## Shell inside the container
	$(RUN) bash

down:              ## Stop MySQL
	$(COMPOSE) down
