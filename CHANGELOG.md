# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [0.4.2] - 2017-09-11
### Added
- Use new registration controller instead of lostpassword functionality [\#64](https://github.com/owncloud/guests/issues/64)
- Add build script [\#24](https://github.com/owncloud/guests/issues/24)

### Changed
- Improve naming of guest accounts [\#69](https://github.com/owncloud/guests/issues/69)
- Permit creation of guest users by their email address in the sharetabview. [\#64](https://github.com/owncloud/guests/issues/64)
- Actual username creation is performed in the backend (is the lowercase email address) [\#64](https://github.com/owncloud/guests/issues/64)

### Fixed
- Whitelist: move "," and core to backend, UI fixes [\#127](https://github.com/owncloud/guests/pull/127)
- Login as a guest is case sensitive [\#133](https://github.com/owncloud/guests/issues/133)
- No error message in UI when duplicate email is used [\#82](https://github.com/owncloud/guests/issues/82)
- Admin Settings fixes [\#107](https://github.com/owncloud/guests/issues/107), [\#113](https://github.com/owncloud/guests/issues/113)

## [0.4.1] - 2017-04-26

### Fixed
- Last release did not contain the mentioned fix due to packaging error

## [0.4] - 2017-04-25

### Fixed
- Fixed breakage when market app is installed

## [0.3] - 2017-04-20

### Added
- Better wording in invite email, user is hinted that he can use his email address to login so he doesn't need to memorize a username.
- Use shortlink in share notification and invite mail.
- Added translatable texts

### Changed
- Removed do not use warning from readme. This app is now considered early beta quality.

### Fixed
- Could not create a guest user as non-admin
- Fixed issue with inconsistent display of guest user's default permissions

## [0.2] - 2017-04-10

This release consists mostly of internal changes to adapt the guest app to ownCloud 10

### Changed
- First version of working jailing code (guest user can't navigate out of his home dir)

### Fixed
- Fixed bug where guest users couldn't be created if ownCloud is installed in a subdirectory
- E-Mail invite could not be sent with ownCloud 10

## 0.1 - 2017-03-27

### Added
- Core functionality

[Unreleased]: https://github.com/owncloud/guests/compare/v0.4.1...HEAD
[0.4.1]: https://github.com/owncloud/guests/compare/v.0.4...v0.4.1
[0.4]: https://github.com/owncloud/guests/compare/v0.3...v.0.4
[0.3]: https://github.com/owncloud/guests/compare/v0.2...v0.3
[0.2]: https://github.com/owncloud/guests/compare/v0.1...v0.2
