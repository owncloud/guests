SHELL := /bin/bash

YARN := $(shell command -v yarn 2> /dev/null)
NODE_PREFIX=$(shell pwd)

PHPUNIT="$(PWD)/lib/composer/phpunit/phpunit/phpunit"
KARMA=$(NODE_PREFIX)/node_modules/.bin/karma
JSDOC=$(NODE_PREFIX)/node_modules/.bin/jsdoc

app_name=$(notdir $(CURDIR))
doc_files=LICENSE README.md CHANGELOG.md
src_dirs=appinfo controller js l10n lib settings templates
all_src=$(src_dirs) $(doc_files)
build_dir=$(CURDIR)/build
dist_dir=$(build_dir)/dist
tests_acceptance_directory=$(CURDIR)/tests/acceptance

nodejs_deps=node_modules


occ=$(CURDIR)/../../occ
private_key=$(HOME)/.owncloud/certificates/$(app_name).key
certificate=$(HOME)/.owncloud/certificates/$(app_name).crt
sign=$(occ) integrity:sign-app --privateKey="$(private_key)" --certificate="$(certificate)"
sign_skip_msg="Skipping signing, either no key and certificate found in $(private_key) and $(certificate) or occ can not be found at $(occ)"
ifneq (,$(wildcard $(private_key)))
ifneq (,$(wildcard $(certificate)))
ifneq (,$(wildcard $(occ)))
	CAN_SIGN=true
endif
endif
endif

all: appstore

# Remove the appstore build and generated guests bundle
.PHONY: clean
clean: clean-nodejs-deps
	rm -rf ./build

.PHONY: clean-nodejs-deps
clean-nodejs-deps:
	rm -Rf $(nodejs_deps)

# Same as clean but also removes dependencies installed by npm
.PHONY: distclean
distclean: clean

# Build the source and appstore package
.PHONY: dist
dist:
	make appstore

# Build the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore:
	rm -rf $(dist_dir)
	mkdir -p $(dist_dir)/$(app_name)
	cp -R $(all_src) $(dist_dir)/$(app_name)

ifdef CAN_SIGN
	$(sign) --path="$(dist_dir)/$(app_name)"
else
	@echo $(sign_skip_msg)
endif
	tar -czf $(dist_dir)/$(app_name).tar.gz -C $(dist_dir) $(app_name)
	tar -cjf $(dist_dir)/$(app_name).tar.bz2 -C $(dist_dir) $(app_name)

$(nodejs_deps): package.json yarn.lock
	yarn install
	touch $@

$(KARMA): $(nodejs_deps)

# Command for running all tests.
.PHONY: test
test: test-acceptance test-php test-js

# Command for running acceptance tests.
.PHONY: test-acceptance
test-acceptance:
	cd $(tests_acceptance_directory) && pwd && ./run.sh

.PHONY: test-php-lint
test-php-lint:
	../../lib/composer/bin/parallel-lint --exclude vendor --exclude build .

.PHONY: test-php-codecheck
test-php-codecheck:
	# currently failes - as we use a private api
	#	$(occ) app:check-code $(app_name) -c private
	$(occ) app:check-code $(app_name) -c strong-comparison
	$(occ) app:check-code $(app_name) -c deprecation

.PHONY: test-php
test-php:
	# TODO: add unit tests...

.PHONY: test-js
test-js: $(nodejs_deps)
	$(KARMA) start tests/js/karma.config.js --single-run

.PHONY: test-js-debug
test-js-debug: $(nodejs_deps)
	$(KARMA) start tests/js/karma.config.js

