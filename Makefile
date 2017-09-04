SHELL := /bin/bash

#
# Define NPM and check if it is available on the system.
#
NPM := $(shell command -v npm 2> /dev/null)
ifndef NPM
    $(error npm is not available on your system, please install npm)
endif

NODE_PREFIX=$(shell pwd)

PHPUNIT="$(PWD)/lib/composer/phpunit/phpunit/phpunit"
BOWER=$(NODE_PREFIX)/node_modules/bower/bin/bower
JSDOC=$(NODE_PREFIX)/node_modules/.bin/jsdoc

app_name=$(notdir $(CURDIR))
doc_files=LICENSE README.md CHANGELOG.md
src_dirs=appinfo controller js l10n lib settings templates
all_src=$(src_dirs) $(doc_files)
build_dir=$(CURDIR)/build
dist_dir=$(build_dir)/dist
tests_integration_directory=$(CURDIR)/tests/integration


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

all: build

# Fetch JS dependencies and compile the JS
.PHONY: build
build: npm
	npm run build

# Fetch JS dependencies and compile the JS
.PHONY: dev
dev: npm
	npm run dev

# Node modules
.PHONY: npm
npm:
	npm install
	
# Remove the appstore build and generated guests bundle
.PHONY: clean
clean:
	rm -rf ./build
	rm -f js/guests.bundle.js

# Same as clean but also removes dependencies installed by npm
.PHONY: distclean
distclean: clean
	rm -rf node_modules
	rm -f package-lock.json

# Build the source and appstore package
.PHONY: dist
dist:
	make appstore

# Build the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore: build
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

# Command for running Integration tests.
.PHONY: test
test:
	cd $(tests_integration_directory) && pwd && ./run.sh
