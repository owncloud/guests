# Specify the files and folders to include in the dist tarball
doc_files=LICENSE README.md CHANGELOG.md
src_dirs=appinfo controller js l10n lib settings templates

# Include standard app makefile targets provided by core
include ../../build/rules/help.mk
include ../../build/rules/dist.mk
include ../../build/rules/test-acceptance.mk
include ../../build/rules/test-js.mk
include ../../build/rules/test-php.mk
include ../../build/rules/clean.mk

# App codecheck
.PHONY: test-php-codecheck
test-php-codecheck:
	# currently fails - as we use a private api
	#	$(occ) app:check-code $(app_name) -c private
	# Note: occ definition comes "for free" from dist.mk
	$(occ) app:check-code $(app_name) -c strong-comparison
	$(occ) app:check-code $(app_name) -c deprecation
