# Every command runs in Docker; a local PHP is not required.
export UID := $(shell id -u)
export GID := $(shell id -g)

COMPOSE := docker compose -f docker/compose.yaml
RUN     := $(COMPOSE) run --rm app

.PHONY: help build install qa test stan cs cs-fix demo shell

help:              ## List the commands
	@grep -E '^[a-z-]+:.*##' $(MAKEFILE_LIST) | sed 's/:.*##/ -/'

build:             ## Build the Docker image
	$(COMPOSE) build app

install: build     ## Install dependencies
	$(RUN) composer install --no-interaction

qa:                ## Coding style, static analysis with architecture rules, tests
	$(RUN) composer qa

test:              ## Tests only
	$(RUN) composer test

stan:              ## Static analysis only
	$(RUN) composer stan

cs:                ## Check coding style
	$(RUN) composer cs

cs-fix:            ## Fix coding style
	$(RUN) composer cs:fix

demo:              ## Play the reference scenario through the command bus and print the history
	$(RUN) php bin/earning-line demo

shell:             ## Shell inside the container
	$(RUN) bash
