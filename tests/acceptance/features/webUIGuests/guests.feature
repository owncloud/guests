@webUI @insulated @disablePreviews
Feature: Guests

  Background:
    Given using OCS API version "1"
    And using new dav path

  @mailhog
  Scenario: Guest user sets its own password
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    When guest user "guest" registers and sets password to "password" using the webUI
    And user "guest@example.com" logs in using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"
    And folder "tmp" should be listed on the webUI

  @mailhog
  Scenario: Guest user uses the link twice
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When guest user "guest" registers and sets password to "secondpassword" using the webUI
    Then the user should be redirected to a webUI page with the title "%productname%"
    And a warning should be displayed on the set-password-page saying "The token is invalid"

  @mailhog @skipOnOcV10.2 @skipOnOcV10.3
  Scenario Outline: User uses valid email to create a guest user
    Given user "Alice" has been created with default attributes and large skeleton files
    And user "Alice" has logged in using the webUI
    When the user shares file "data.zip" with guest user with email "<email-address>" using the webUI
    Then user "<email-address>" should exist
    Examples:
      | email-address                  |
      | valid@email.com                |
      | John.Smith@email.com           |
      | Betty_Anne+Bob-Burns@email.com |

  @mailhog
  Scenario: User uses some random string email to create a guest user
    Given user "Alice" has been created with default attributes and small skeleton files
    And user "Alice" has logged in using the webUI
    And the user has opened the share dialog for file "textfile0.txt"
    When the user types "somestring" in the share-with-field
    Then a tooltip with the text "No users or groups found for somestring" should be shown near the share-with-field on the webUI
    And user "somestring" should not be displayed in the dropdown as a guest user
    And user "somestring" should not exist

  @mailhog @skipOnOcV10.8 @skipOnOcV10.9.0 @skipOnOcV10.9.1
  Scenario Outline: User cannot use an email of a blocked domain to create a guest user
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
  Scenario: User can use an email of a not-blocked domain to create a guest user
    Given the administrator has added config key "blockdomains" with value "test.com,gmail.com" in app "guests"
    And user "Alice" has been created with default attributes and small skeleton files
    And user "Alice" has logged in using the webUI
    When the user shares file "textfile0.txt" with guest user with email "valid@email.com" using the webUI
    Then user "valid@email.com" should exist

  @mailhog @skipOnOcV10.2
  Scenario: User uses invalid email to create a guest user
    Given user "Alice" has been created with default attributes and large skeleton files
    And user "Alice" has logged in using the webUI
    When the user shares file "testimage.jpg" with guest user with email "invalid@email.com()9876a" using the webUI
    Then dialog should be displayed on the webUI
      | title | content              |
      | Error | Invalid mail address |
    And user "invalid@email.com()9876a" should not exist

  @mailhog @skipOnOcV10.2
  Scenario: User tries to create a guest user via email with an already used email
    Given these users have been created with large skeleton files:
      | username | email        |
      | Alice    | Alice@oc.com |
      | Brian    | Brian@oc.com |
    And user "Alice" has logged in using the webUI
    And the user has opened the share dialog for file "lorem.txt"
    When the user types "Brian@oc.com" in the share-with-field
    Then user "Brian" should be listed in the autocomplete list on the webUI
    And user "Brian@oc.com" should not be displayed in the dropdown as a guest user

  @mailhog @issue-329 @skipOnOcV10.2
  Scenario: User tries to create a guest user when a server email mode is not set
    Given user "Brian" has been created with default attributes and large skeleton files
    And user "Brian" has logged in using the webUI
    When the administrator deletes system config key "mail_smtpmode" using the occ command
    And the user shares file "testimage.jpg" with guest user with email "valid@email.com" using the webUI
    Then dialog should be displayed on the webUI
      | title | content             |
      | Error | Error while sharing |
    And user "valid@email.com" should exist
    # And user "valid@email.com" should not exist

  @mailhog @skipOnOcV10.2 @skipOnFIREFOX
  Scenario: Administrator changes the guest user's password in users menu
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

  @mailhog @issue-329 @skipOnOcV10.2
  Scenario: User tries to create a guest user when a server email is invalid
    Given user "Brian" has been created with default attributes and large skeleton files
    And user "Brian" has logged in using the webUI
    When the administrator adds system config key "mail_smtphost" with value "conkey" using the occ command
    And the user shares file "testimage.jpg" with guest user with email "valid@email.com" using the webUI
    Then dialog should be displayed on the webUI
      | title | content             |
      | Error | Error while sharing |
    And user "valid@email.com" should exist
    # And user "valid@email.com" should not exist

  @mailhog @skipOnOcV10.2
  Scenario: Administrator deletes a guest user in user's menu
    Given user "admin" has uploaded file with content "new content" to "new-file.txt"
    And the administrator has logged in using the webUI
    And the user shares file "new-file.txt" with guest user with email "valid@email.com" using the webUI
    And the administrator has browsed to the users page
    When the administrator deletes user "valid@email.com" using the webUI and confirms the deletion using the webUI
    Then user "valid@email.com" should not exist

  @mailhog @skipOnOcV10.2
  Scenario Outline: User creates a guest user with email that contains capital letters
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

  @mailhog
  Scenario: Guest user is not able to upload or create files
    Given user "Alice" has been created with default attributes and large skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "lorem.txt" with user "guest@example.com"
    When guest user "guest" registers and sets password to "password" using the webUI
    And user "guest@example.com" logs in using the webUI
    Then the user should not have permission to upload or create files

  @mailhog @skipOnOcV10.3
  Scenario Outline: Guest user is able to upload or create files inside the received share(with change permission)
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

  @mailhog
  Scenario: Guest user tries to upload or create files inside the received share(read only permission)
    Given user "Alice" has been created with default attributes and large skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared folder "simple-folder" with user "guest@example.com"
    When user "Alice" updates the last share using the sharing API with
      | permissions | read |
    And guest user "guest" registers and sets password to "password" using the webUI
    And user "guest@example.com" logs in using the webUI
    And the user opens folder "simple-folder" using the webUI
    Then the user should not have permission to upload or create files

  @mailhog
  Scenario: Create a regular user using the same email address of an existing guest user
    Given the administrator has created guest user "guest" with email "guest@example.com"
    And the administrator has logged in using the webUI
    And the administrator has browsed to the users page
    When the administrator creates a user with the name "regularUser" and the email "guest@example.com" without a password using the webUI
    Then the administrator should be able to see the email of these users in the User Management page:
      | username    |
      | regularUser |
    And the email address of user "regularUser" should be "guest@example.com"
