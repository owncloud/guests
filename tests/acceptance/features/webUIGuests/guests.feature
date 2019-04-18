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
  Scenario: Guest user with uppercase letters sets its own password
    Given user "user0" has been created with default attributes
    And the administrator has created guest user "Guest2@example.com" with email "Guest2@example.com"
    And user "user0" has created folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "Guest2@example.com"
    When guest user "Guest2@example.com" registers and sets password to "password" using the webUI
    And user "Guest2@example.com" logs in using the webUI
    Then the user should be redirected to a webUI page with the title "Files - %productname%"
    And folder "tmp" should be listed on the webUI
