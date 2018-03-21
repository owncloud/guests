@api
Feature: Guests

  Background:
    Given using api version "1"
    And using new dav path

  Scenario: Creating a guest user works fine
    Given as user "admin"
    When user "admin" creates guest user "guest" with email "guest@example.com" using the API
    Then the HTTP status code should be "201"
    And user "guest" should be a guest user

  Scenario: Cannot create a guest if a user with the same email address exists
    Given as user "admin"
    And user "existing-user" has been created
    And user "admin" sends HTTP method "PUT" to API endpoint "/cloud/users/existing-user" with body
      | key   | email             |
      | value | guest@example.com |
    When user "admin" attempts to create guest user "guest" with email "guest@example.com" using the API
    Then the HTTP status code should be "422"
    And user "guest" should not exist

  Scenario: A guest user cannot upload files
    Given as user "admin"
    And user "admin" has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    When user "guest@example.com" uploads file "data/textfile.txt" to "/myfile.txt" using the API
    Then the HTTP status code should be "401"

  Scenario: A guest user can upload files
    Given as user "admin"
    And user "user0" has been created
    And user "admin" has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "user0" has created a folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When user "guest@example.com" uploads file "data/textfile.txt" to "/tmp/textfile.txt" using the API
    Then the HTTP status code should be "201"

  Scenario: A guest user can upload a file and can reshare it
    Given as user "admin"
    And user "user0" has been created
    And user "user1" has been created
    And user "admin" has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "user0" has created a folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    And user "guest@example.com" has uploaded file "data/textfile.txt" to "/tmp/textfile.txt"
    And user "guest@example.com" has shared file "/tmp/textfile.txt" with user "user1"
    When user "guest@example.com" sends HTTP method "GET" to API endpoint "/apps/files_sharing/api/v1/shares?reshares=true&path=/tmp/textfile.txt"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: A guest user cannot reshare files
    Given as user "admin"
    And user "user0" has been created
    And user "user1" has been created
    And user "admin" has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "user0" has created a folder "/tmp"
    And user "user0" has created a share with settings
      | path        | /tmp              |
      | shareType   | 0                 |
      | shareWith   | guest@example.com |
      | permissions | 8                 |
    And guest user "guest" has registered
    When user "guest@example.com" creates a share using the API with settings
      | path        | /tmp  |
      | shareType   | 0     |
      | shareWith   | user1 |
      | permissions | 31    |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Check that skeleton is properly set
    Given as user "admin"
    And user "user0" has been created
    Then user "user0" should see the following elements
      | /FOLDER/           |
      | /PARENT/           |
      | /PARENT/parent.txt |
      | /textfile0.txt     |
      | /textfile1.txt     |
      | /textfile2.txt     |
      | /textfile3.txt     |
      | /textfile4.txt     |
      | /welcome.txt       |

  Scenario: A created guest user can log in
    Given as user "admin"
    And user "user0" has been created
    And user "admin" has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "guest" should be a guest user
    And user "user0" has shared file "/textfile1.txt" with user "guest@example.com"
    When guest user "guest" registers
    Then the HTTP status code should be "200"
    And user "guest@example.com" should see the following elements
      | /textfile1.txt |

  Scenario: Trying to create a guest user that already exists
    Given as user "admin"
    And user "admin" has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "guest" should be a guest user
    When user "admin" creates guest user "guest" with email "guest@example.com" using the API
    Then the HTTP status code should be "422"

  Scenario: removing a user from a group
    Given as user "admin"
    And user "admin" has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And group "guests_app" has been created
    And user "guest@example.com" has been added to group "guests_app"
    When user "admin" sends HTTP method "DELETE" to API endpoint "/cloud/users/guest@example.com/groups" with body
      | groupid | guests_app |
    Then the OCS status code should be "100"
    And user "guest@example.com" should not belong to group "guests_app"
