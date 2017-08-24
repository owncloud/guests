app_name=$(notdir $(CURDIR))
appstore_build_directory=$(CURDIR)/build/
appstore_package_name=$(appstore_build_directory)/$(app_name)
tests_integration_directory=$(CURDIR)/tests/integration

occ=$(CURDIR)/../../occ
private_key=$(HOME)/.owncloud/certificates/$(app_name).key
certificate=$(HOME)/.owncloud/certificates/$(app_name).crt
sign=php -f $(occ) integrity:sign-app --privateKey="$(private_key)" --certificate="$(certificate)"
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
	rm -rf $(appstore_build_directory)
	mkdir -p $(appstore_package_name)
	cp --parents -r \
	appinfo \
	controller \
	js \
	l10n \
	lib \
	settings \
	templates \
	CHANGELOG.md \
	LICENSE \
	README.md \
	$(appstore_package_name)

ifdef CAN_SIGN
	$(sign) --path="$(appstore_package_name)"
else
	@echo $(sign_skip_msg)
endif
	tar -czf $(appstore_package_name).tar.gz -C $(appstore_package_name)/../ $(app_name)

# Command for running Integration tests.
.PHONY: test
test:
	cd $(tests_integration_directory) && pwd && ./run.sh
