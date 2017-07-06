guests (0.4.1 new_registration)
* Permit creation of guest users by their email address in the sharetabview.
* In the modal window "Share with guest 'guestuser'" name and email are prefilled with the email address (so the display name can be changed, if desired)
* Actual username creation is performed in the backend (is the lowercase email address)
* While creating a new guest user, a registerToken instead of lostpassword token is created (i.e. lostcontroller functionality is removed)
* The invitation email template will provide the link to a new route guests.register.*
* After a guest user submits the password it will be set via UserManager, the registerToken is deleted.

guests (0.4.1)
* Last release did not contain the mentioned fix due to packaging error

guests (0.4)
* Fixed breakage when market app is installed

guests (0.3)
NOTE: Currently only compatible with ownCloud 10.0 beta2 and higher.

## Improvements
- Better wording in invite email, user is hinted that he can use his email address to login so he doesn`t need to memorize a username.
- Use shortlink in share notification and invite mail.
- Added translatable texts


## Bugfixes
- Could not create a guest user as non-admin
- Fixed issue with inconsistent display of guest user`s default permissions

## Misc
Removed do not use warning from readme. This app is now considered early beta quality.

guests (0.2)
NOTE: Currently only compatible with ownCloud 10.0 beta2 and higher.

This release consists mostly of internal changes to adapt the guest app to ownCloud 10

- Fixed bug where guest users couldn`t be created if ownCloud is installed in a subdirectory
- E-Mail invite could not be sent with ownCloud 10
- First version of working jailing code (guest user can`t navigate out of his home dir)

guests (0.1)
* Core functionality
