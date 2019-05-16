@webUI @insulated @disablePreviews
Feature: Guests

  Background:
    Given using OCS API version "1"
    And using new dav path

  @mailhog
  Scenario: Guest user sets its own password
    Given user "user0" has been created with default attributes
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "user0" has created folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    When guest user "guest" registers and sets password to "password" using the webUI
    And user "guest@example.com" logs in using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"
    And folder "tmp" should be listed on the webUI

  @mailhog
  Scenario: Guest user uses the link twice
    Given user "user0" has been created with default attributes
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "user0" has created folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When guest user "guest" registers and sets password to "secondpassword" using the webUI
    Then the user should be redirected to a webUI page with the title "%productname%"
    And a warning should be displayed on the set-password-page saying "The token is invalid"

  @mailhog
  Scenario: User uses valid email to create a guest user
    Given user "user0" has been created with default attributes
    And user "user0" has logged in using the webUI
    When the user shares file "data.zip" with guest user with email "valid@email.com" using webUI
    Then user "valid@email.com" should exist

  @mailhog
  Scenario: User uses some random string email to crete a guest user
    Given user "user0" has been created with default attributes
    And user "user0" has logged in using the webUI
    And the user has opened the share dialog for folder "lorem.txt"
    When the user types "somestring" in the share-with-field
    Then a tooltip with the text "No users or groups found for somestring" should be shown near the share-with-field on the webUI
    And user "somestring" should not be displayed in dropdown as guest user
    And user "somestring" should not exist

  @mailhog
  Scenario: User uses invalid email to create a guest user
    Given user "user0" has been created with default attributes
    And user "user0" has logged in using the webUI
    When the user shares file "testimage.jpg" with guest user with email "invalid@email.com()9876a" using webUI
    Then dialog should be displayed on the webUI
      | title | content               |
      | Error | Invalid mail address  |
    And user "invalid@email.com()9876a" should not exist

  @mailhog
  Scenario: User tries to create a guest user via email with an already used email
    Given these users have been created:
      |    username    |    email        |
      |     user0      |  user0@oc.com   |
      |     user1      |  user1@oc.com   |
    And user "user0" has logged in using the webUI
    And the user has opened the share dialog for file "lorem.txt"
    When the user types "user1@oc.com" in the share-with-field
    Then user "user1" should be listed in the autocomplete list on the webUI
    And user "user1@oc.com" should not be displayed in dropdown as guest user

  @mailhog @issue-329
  Scenario: User tries to create a guest user when a server email mode is not set
    Given user "user1" has been created with default attributes
    And user "user1" has logged in using the webUI
    When the administrator deletes system config key "mail_smtpmode" using the occ command
    And the user shares file "testimage.jpg" with guest user with email "valid@email.com" using webUI
    Then dialog should be displayed on the webUI
      | title | content               |
      | Error | Error while sharing   |
    And user "valid@email.com" should exist
    # And user "valid@email.com" should not exist

  @mailhog
  Scenario: Administrator changes the guest user's password in users menu
    Given guest user "user0" has been created with email "user0@example.com" and password "%alt2%"
    And the administrator has logged in using the webUI
    And the administrator has browsed to the users page
    When the administrator changes the password of user "user0" to "newpassword" using the webUI
    Then user "user0" should exist
    And notifications should be displayed on the webUI with the text
      | Password successfully changed |
    When the administrator logs out of the webUI
    And the user logs in with username "user0" and password "newpassword" using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"

  @mailhog @issue-329
  Scenario: User tries to create a guest user when a server email is invalid
    Given user "user1" has been created with default attributes
    And user "user1" has logged in using the webUI
    When the administrator adds system config key "mail_smtphost" with value "conkey" using the occ command
    And the user shares file "testimage.jpg" with guest user with email "valid@email.com" using webUI
    Then dialog should be displayed on the webUI
      | title | content               |
      | Error | Error while sharing   |
    And user "valid@email.com" should exist
    # And user "valid@email.com" should not exist

  @mailhog
  Scenario: Administrator deletes a guest user in user's menu
    Given guest user "user0" has been created with email "test@example.com" and password "%alt2%"
    And the administrator has logged in using the webUI
    And the administrator has browsed to the users page
    When the administrator deletes user "user0" using the webUI and confirms the deletion using the webUI
    Then user "user0" should not exist
