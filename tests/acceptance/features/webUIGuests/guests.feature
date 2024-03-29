@webUI @insulated @disablePreviews
Feature: Guests

  Background:
    Given using OCS API version "1"
    And using new dav path

  @email
  Scenario: guest user sets their own password
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    When guest user "guest" registers and sets password to "password" using the webUI
    And user "guest@example.com" logs in using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"
    And folder "tmp" should be listed on the webUI

  @email
  Scenario: guest user uses the registration link twice
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When guest user "guest" registers and sets password to "secondpassword" using the webUI
    Then the user should be redirected to a webUI page with the title "%productname%"
    And a warning should be displayed on the set-password-page saying "The token is invalid"

  @email @skipOnOcV10.2 @skipOnOcV10.3
  Scenario Outline: ordinary user uses valid email to create a guest user
    Given user "Alice" has been created with default attributes and large skeleton files
    And user "Alice" has logged in using the webUI
    When the user shares file "data.zip" with guest user with email "<email-address>" using the webUI
    Then user "<email-address>" should exist
    Examples:
      | email-address                  |
      | valid@email.com                |
      | John.Smith@email.com           |
      | Betty_Anne+Bob-Burns@email.com |

  @email
  Scenario: ordinary user uses some random string email to create a guest user
    Given user "Alice" has been created with default attributes and small skeleton files
    And user "Alice" has logged in using the webUI
    And the user has opened the share dialog for file "textfile0.txt"
    When the user types "somestring" in the share-with-field
    Then a tooltip with the text "No users or groups found for somestring" should be shown near the share-with-field on the webUI
    And user "somestring" should not be displayed in the dropdown as a guest user
    And user "somestring" should not exist

  @email @skipOnOcV10.8 @skipOnOcV10.9.0 @skipOnOcV10.9.1
  Scenario Outline: ordinary user cannot use an email of a blocked domain to create a guest user
    Given the administrator has added config key "blockdomains" with value "<block-domains>" in app "guests"
    And user "Alice" has been created with default attributes and small skeleton files
    And user "Alice" has logged in using the webUI
    And the user has opened the share dialog for file "textfile0.txt"
    When the user types "someone@gmail.com" in the share-with-field
    Then user "someone@gmail.com" should not be displayed in the dropdown as a guest user
    Examples:
      | block-domains                    |
      | gmail.com                        |
      | test.com,gmail.com               |
      | gmail.com,somewhere.org          |
      | test.com,gmail.com,somewhere.org |

  @mailhog @skipOnOcV10.8 @skipOnOcV10.9.0 @skipOnOcV10.9.1
  Scenario: ordinary user can use an email of a not-blocked domain to create a guest user even if blocked domain is substring of email domain
    Given the administrator has added config key "blockdomains" with value "test.com,gmail.com" in app "guests"
    And user "Alice" has been created with default attributes and small skeleton files
    And user "Alice" has logged in using the webUI
    When the user shares file "textfile0.txt" with guest user with email "valid@notgmail.com" using the webUI
    Then user "valid@notgmail.com" should exist

  @email @skipOnOcV10.2
  Scenario: ordinary user uses invalid email to create a guest user
    Given user "Alice" has been created with default attributes and large skeleton files
    And user "Alice" has logged in using the webUI
    When the user shares file "testimage.jpg" with guest user with email "invalid@email.com()9876a" using the webUI
    Then dialog should be displayed on the webUI
      | title | content              |
      | Error | Invalid mail address |
    And user "invalid@email.com()9876a" should not exist

  @email @skipOnOcV10.2
  Scenario: ordinary user tries to create a guest user via email with an already used email
    Given these users have been created with large skeleton files:
      | username | email        |
      | Alice    | Alice@oc.com |
      | Brian    | Brian@oc.com |
    And user "Alice" has logged in using the webUI
    And the user has opened the share dialog for file "lorem.txt"
    When the user types "Brian@oc.com" in the share-with-field
    Then user "Brian" should be listed in the autocomplete list on the webUI
    And user "Brian@oc.com" should not be displayed in the dropdown as a guest user

  @email @issue-329 @skipOnOcV10.2
  Scenario: ordinary user tries to create a guest user when a server email mode is not set
    Given user "Brian" has been created with default attributes and large skeleton files
    And user "Brian" has logged in using the webUI
    When the administrator deletes system config key "mail_smtpmode" using the occ command
    And the user shares file "testimage.jpg" with guest user with email "valid@email.com" using the webUI
    Then dialog should be displayed on the webUI
      | title | content             |
      | Error | Error while sharing |
    And user "valid@email.com" should exist
    # And user "valid@email.com" should not exist

  @email @skipOnOcV10.2 @skipOnFIREFOX
  Scenario: administrator changes the guest user's password in users menu
    Given user "admin" has uploaded file with content "new content" to "new-file.txt"
    And the administrator has logged in using the webUI
    And the user shares file "new-file.txt" with guest user with email "valid@email.com" using the webUI
    And the administrator has browsed to the users page
    When the administrator changes the password of user "valid@email.com" to "newpassword" using the webUI
    Then notifications should be displayed on the webUI with the text
      | Password successfully changed |
    When the administrator logs out of the webUI
    And the user logs in with username "valid@email.com" and password "newpassword" using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"

  @email @issue-329 @skipOnOcV10.2
  Scenario: ordinary user tries to create a guest user when a server email is invalid
    Given user "Brian" has been created with default attributes and large skeleton files
    And user "Brian" has logged in using the webUI
    When the administrator adds system config key "mail_smtphost" with value "conkey" using the occ command
    And the user shares file "testimage.jpg" with guest user with email "valid@email.com" using the webUI
    Then dialog should be displayed on the webUI
      | title | content             |
      | Error | Error while sharing |
    And user "valid@email.com" should exist
    # And user "valid@email.com" should not exist

  @email @skipOnOcV10.2
  Scenario: administrator deletes a guest user in user's menu
    Given user "admin" has uploaded file with content "new content" to "new-file.txt"
    And the administrator has logged in using the webUI
    And the user shares file "new-file.txt" with guest user with email "valid@email.com" using the webUI
    And the administrator has browsed to the users page
    When the administrator deletes user "valid@email.com" using the webUI and confirms the deletion using the webUI
    Then user "valid@email.com" should not exist

  @email @skipOnOcV10.2
  Scenario Outline: ordinary user creates a guest user with email that contains capital letters
    Given user "Alice" has been created with default attributes and large skeleton files
    And user "Alice" has logged in using the webUI
    When the user shares file "data.zip" with guest user with email "<share-email>" using the webUI
    And the user logs out of the webUI
    And guest user "<share-email>" registers with email "<register-email>" and sets password to "password" using the webUI
    And the user logs in with username "<login-email>" and password "password" using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"
    And file "data.zip" should be listed on the webUI
    Examples:
      | share-email      | register-email   | login-email      |
      | user@example.com | USER@example.com | user@example.com |
      | USER@example.com | user@example.com | user@example.com |
      | USER@example.com | USER@example.com | USER@example.com |
      | USER@example.com | USER@example.com | user@example.com |
      | user@example.com | USER@example.com | user@EXAMPLE.com |

  @email
  Scenario: guest user is not able to upload or create files
    Given user "Alice" has been created with default attributes and large skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "lorem.txt" with user "guest@example.com"
    When guest user "guest" registers and sets password to "password" using the webUI
    And user "guest@example.com" logs in using the webUI
    Then the user should not have permission to upload or create files

  @email @skipOnOcV10.3
  Scenario Outline: guest user is able to upload or create files inside a received share with change permission
    Given user "Alice" has been created with default attributes and large skeleton files
    And user "Alice" has logged in using the webUI
    When the user shares folder "simple-folder" with guest user with email "<email-address>" using the webUI
    And the user logs out of the webUI
    And guest user "<email-address>" registers with email "<email-address>" and sets password to "password" using the webUI
    And user "<email-address>" logs in using the webUI
    And the user opens folder "simple-folder" using the webUI
    And the user uploads file "new-lorem.txt" using the webUI
    Then file "new-lorem.txt" should be listed on the webUI
    And as "Alice" file "/simple-folder/new-lorem.txt" should exist
    Examples:
      | email-address                  |
      | guest@example.com              |
      | John.Smith@email.com           |
      | Betty_Anne+Bob-Burns@email.com |

  @email
  Scenario: guest user tries to upload or create files inside a received share with read only permission
    Given user "Alice" has been created with default attributes and large skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared folder "simple-folder" with user "guest@example.com"
    When user "Alice" updates the last share using the sharing API with
      | permissions | read |
    And guest user "guest" registers and sets password to "password" using the webUI
    And user "guest@example.com" logs in using the webUI
    And the user opens folder "simple-folder" using the webUI
    Then the user should not have permission to upload or create files

  @email
  Scenario: create a regular user using the same email address as an existing guest user
    Given the administrator has created guest user "guest" with email "guest@example.com"
    And the administrator has logged in using the webUI
    And the administrator has browsed to the users page
    When the administrator creates a user with the name "regularUser" and the email "guest@example.com" without a password using the webUI
    Then the administrator should be able to see the email of these users in the User Management page:
      | username    |
      | regularUser |
    And the email address of user "regularUser" should be "guest@example.com"


  Scenario: check blocked domains set from command line for guests in webUI
    Given the administrator has invoked occ command "config:app:set guests blockdomains --value='something.com,qwerty.org,example.gov'"
    And user admin has logged in using the webUI
    When the administrator browses to the guests admin settings page
    Then the blocked domains from sharing with guests input should have value "something.com,qwerty.org,example.gov" on the webUI


  Scenario: check blocked domains set from webUI for guests in command line
    Given user admin has logged in using the webUI
    And the administrator has browsed to the guests admin settings page
    When the administrator sets the value of blocked domains sharing from guests input to "something.com,qwerty.org,example.gov" using webUI
    And the administrator invokes occ command "config:app:get guests blockdomains"
    Then the command should have been successful
    And the command output should be the text "something.com,qwerty.org,example.gov"

  @email
  Scenario Outline: check sidebar panel when specific app is whitelisted
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has uploaded file with content "new content" to "lorem.txt"
    And user "Alice" has shared file "/lorem.txt" with user "guest@example.com"
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "<app>" to the whitelist for the guest user
    And guest user "guest" has registered
    And user "guest@example.com" has logged in using the webUI
    When the user opens the file action menu of file "lorem.txt" on the webUI
    And the user clicks the details file action on the webUI
    And the user switches to the "<panel>" tab in the details panel using the webUI
    Then the "<panel>" details panel should be visible
    Examples:
      | app            | panel    |
      | comments       | comments |
      | systemtags     | tags     |
      | files_versions | versions |

  @email
  Scenario Outline: check sidebar panel when specific app is not whitelisted
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has uploaded file with content "new content" to "lorem.txt"
    And user "Alice" has shared file "/lorem.txt" with user "guest@example.com"
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has removed the app "<app>" from the whitelist for the guest user
    And guest user "guest" has registered
    And user "guest@example.com" has logged in using the webUI
    When the user opens the file action menu of file "lorem.txt" on the webUI
    And the user clicks the details file action on the webUI
    Then the "<panel>" details panel should not be visible
    Examples:
      | app            | panel    |
      | comments       | comments |
      | systemtags     | tags     |
      | files_versions | versions |

  @email
  Scenario: check deleted files sidebar when files_trashbin app is whitelisted
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has uploaded file with content "new content" to "lorem.txt"
    And user "Alice" has shared file "/lorem.txt" with user "guest@example.com"
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "files_trashbin" to the whitelist for the guest user
    And guest user "guest" has registered
    When user "guest@example.com" logs in using the webUI
    Then the user should see "Deleted files" sidebar navigation on the webUI

  @email
  Scenario: check deleted files sidebar when files_trashbin app is not whitelisted
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has uploaded file with content "new content" to "lorem.txt"
    And user "Alice" has shared file "/lorem.txt" with user "guest@example.com"
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has removed the app "files_trashbin" from the whitelist for the guest user
    And guest user "guest" has registered
    When user "guest@example.com" logs in using the webUI
    Then the user should not see "Deleted files" sidebar navigation on the webUI
