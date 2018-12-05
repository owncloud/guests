@api
Feature: Guests

  Background:
    Given using OCS API version "1"
    And using new dav path

  Scenario: Creating a guest user works fine
    When the administrator creates guest user "guest" with email "guest@example.com" using the API
    Then the HTTP status code should be "201"
    And user "guest" should be a guest user

  Scenario: Cannot create a guest if a user with the same email address exists
    Given user "existing-user" has been created with default attributes
    And the administrator sends HTTP method "PUT" to OCS API endpoint "/cloud/users/existing-user" with body
      | key   | email             |
      | value | guest@example.com |
    When the administrator attempts to create guest user "guest" with email "guest@example.com" using the API
    Then the HTTP status code should be "422"
    And user "guest" should not exist

  Scenario: A guest user cannot upload files
    Given the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    When user "guest@example.com" uploads file "textfile.txt" from the guests test data folder to "/myfile.txt" using the WebDAV API
    Then the HTTP status code should be "401"

  @mailhog
  Scenario: A guest user can upload files
    Given user "user0" has been created with default attributes
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "user0" has created folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When user "guest@example.com" uploads file "textfile.txt" from the guests test data folder to "/tmp/textfile.txt" using the WebDAV API
    Then the HTTP status code should be "201"

  @mailhog
  Scenario: A guest user can upload chunked files
    Given user "user0" has been created with default attributes
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "user0" has created folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When user "guest@example.com" creates a new chunking upload with id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "1" with "AAAAA" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "2" with "BBBBB" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "3" with "CCCCC" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" moves new chunk file with id "chunking-42" to "/tmp/myChunkedFile.txt" using the WebDAV API
    Then as "guest@example.com" file "/tmp/myChunkedFile.txt" should exist
    And as "user0" file "/tmp/myChunkedFile.txt" should exist

  @mailhog
  Scenario: A guest user can cancel a chunked upload
    Given user "user0" has been created with default attributes
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "user0" has created folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When user "guest@example.com" creates a new chunking upload with id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "1" with "AAAAA" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "2" with "BBBBB" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "3" with "CCCCC" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" cancels chunking-upload with id "chunking-42" using the WebDAV API
    Then the HTTP status code should be "204"
    And as "user0" file "/tmp/myChunkedFile.txt" should not exist

  @mailhog
  Scenario: A guest user can upload a file and can reshare it
    Given these users have been created with default attributes:
      | username |
      | user0    |
      | user1    |
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "user0" has created folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    And user "guest@example.com" has uploaded file "textfile.txt" from the guests test data folder to "/tmp/textfile.txt"
    And user "guest@example.com" has shared file "/tmp/textfile.txt" with user "user1"
    When user "guest@example.com" sends HTTP method "GET" to OCS API endpoint "/apps/files_sharing/api/v1/shares?reshares=true&path=/tmp/textfile.txt"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  @mailhog
  Scenario: A guest user cannot reshare files
    Given these users have been created with default attributes:
      | username |
      | user0    |
      | user1    |
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "user0" has created folder "/tmp"
    And user "user0" has created a share with settings
      | path        | /tmp              |
      | shareType   | 0                 |
      | shareWith   | guest@example.com |
      | permissions | 8                 |
    And guest user "guest" has registered
    When user "guest@example.com" creates a share using the sharing API with settings
      | path        | /tmp  |
      | shareType   | 0     |
      | shareWith   | user1 |
      | permissions | 31    |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Check that skeleton is properly set
    Given user "user0" has been created with default attributes
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

  @mailhog
  Scenario: A created guest user can log in
    Given user "user0" has been created with default attributes
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "guest" should be a guest user
    And user "user0" has shared file "/textfile1.txt" with user "guest@example.com"
    When guest user "guest" registers
    Then the HTTP status code should be "200"
    And user "guest@example.com" should see the following elements
      | /textfile1.txt |

  Scenario: Trying to create a guest user that already exists
    Given the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "guest" should be a guest user
    When the administrator creates guest user "guest" with email "guest@example.com" using the API
    Then the HTTP status code should be "422"

  Scenario: removing a user from a group
    Given the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And group "guests_app" has been created
    And user "guest@example.com" has been added to group "guests_app"
    When the administrator sends HTTP method "DELETE" to OCS API endpoint "/cloud/users/guest@example.com/groups" with body
      | groupid | guests_app |
    Then the OCS status code should be "100"
    And user "guest@example.com" should not belong to group "guests_app"

  @mailhog
  Scenario: A guest user can not create new guest users
    Given user "user0" has been created with default attributes
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "user0" has created folder "/tmp"
    And user "user0" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" registers
    When user "guest@example.com" has created guest user "guest2" with email "guest2@example.com"
    Then the HTTP status code should be "403"
