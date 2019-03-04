# Specify the files and folders to include in the dist tarball
doc_files=LICENSE README.md CHANGELOG.md
src_dirs=appinfo controller js l10n lib settings templates

# Include standard app makefile targets provided by core
include ../../build/rules/help.mk
include ../../build/rules/dist.mk
include ../../build/rules/test-all.mk
include ../../build/rules/clean.mk
