@api
Feature: Guests

  Background:
    Given using OCS API version "1"
    And using new dav path

  @skipOnOcV10.3
  Scenario Outline: Creating a guest user works fine
    When the administrator creates guest user "<user>" with email "<email-address>" using the API
    Then the HTTP status code should be "201"
    And user "<user>" should be a guest user
    And the email address of user "<email-address>" should be "<email-address>"
    Examples:
      | email-address                  | user                 |
      | guest@example.com              | guest                |
      | John.Smith@email.com           | John.Smith           |
      | betty_anne+bob-burns@email.com | betty_anne+bob-burns |


  Scenario: Cannot create a guest if a user with the same email address exists
    Given user "existing-user" has been created with default attributes and small skeleton files
    And the administrator sends HTTP method "PUT" to OCS API endpoint "/cloud/users/existing-user" with body
      | key   | email             |
      | value | guest@example.com |
    When the administrator attempts to create guest user "guest" with email "guest@example.com" using the API
    Then the HTTP status code should be "422"
    And user "guest" should not exist


  Scenario: A guest user cannot upload files to their own storage
    Given the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    When user "guest@example.com" uploads overwriting file "textfile.txt" from the guests test data folder to "/myfile.txt" with all mechanisms using the WebDAV API
    Then the HTTP status code of all upload responses should be "401"
    And the HTTP reason phrase of all upload responses should be "Unauthorized"
    And as "guest@example.com" file "/textfile.txt" should not exist
    And as "Alice" file "/textfile.txt" should not exist


  Scenario: A guest user cannot upload files to their own storage (async upload)
    Given the administrator has enabled async operations
    And the administrator has created guest user "guest" with email "guest@example.com"
    When user "guest@example.com" uploads file "textfile.txt" from the guests test data folder asynchronously to "/textfile.txt" in 3 chunks using the WebDAV API
    Then the HTTP status code should be "401"
    And as "guest@example.com" file "/textfile.txt" should not exist
    And as "Alice" file "/textfile.txt" should not exist

  @email @skipOnOcV10.3
  Scenario Outline: A guest user can upload files to a folder shared with them
    Given user "Alice" has been created with default attributes and small skeleton files
    And the administrator has created guest user "<user>" with email "<email-address>"
    And the HTTP status code should be "201"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with guest user "<email-address>"
    And guest user "<user>" has registered
    When user "<email-address>" uploads file "textfile.txt" from the guests test data folder to "/tmp/textfile.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And as "Alice" file "/tmp/textfile.txt" should exist
    Examples:
      | email-address                  | user                 |
      | guest@example.com              | guest                |
      | John.Smith@email.com           | John.Smith           |
      | betty_anne+bob-burns@email.com | betty_anne+bob-burns |

  @email
  Scenario: A guest user can upload chunked files to a folder shared with them
    Given user "Alice" has been created with default attributes and small skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When user "guest@example.com" creates a new chunking upload with id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "1" with "AAAAA" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "2" with "BBBBB" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "3" with "CCCCC" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" moves new chunk file with id "chunking-42" to "/tmp/myChunkedFile.txt" using the WebDAV API
    Then as "guest@example.com" file "/tmp/myChunkedFile.txt" should exist
    And as "Alice" file "/tmp/myChunkedFile.txt" should exist

  @email @issue-279
  Scenario: A guest user can upload files to a folder shared with them
    Given user "Alice" has been created with default attributes and small skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When user "guest@example.com" uploads overwriting file "textfile.txt" from the guests test data folder to "/tmp/myfile.txt" with all mechanisms using the WebDAV API
    #Then the HTTP status code of all upload responses should be "201"
    #ToDo: after fixing the issue merge the different upload tests
    Then the HTTP status code of all upload responses should be between "201" and "400"
    And the content of file "/tmp/myfile.txt" for user "Alice" should be:
    """
    This is a testfile.

    Cheers.
    """
    And the content of file "/tmp/myfile.txt" for user "guest@example.com" should be:
    """
    This is a testfile.

    Cheers.
    """

  @email
  Scenario: A guest user can upload files to a folder shared with them (async upload)
    Given the administrator has enabled async operations
    And user "Alice" has been created with default attributes and small skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When user "Alice" uploads file "textfile.txt" from the guests test data folder asynchronously to "/tmp/textfile.txt" in 3 chunks using the WebDAV API
    Then the HTTP status code should be "202"
    And the oc job status values of last request for user "Alice" should match these regular expressions
      | status | /^finished$/ |
    And the content of file "/tmp/textfile.txt" for user "Alice" should be:
    """
    This is a testfile.

    Cheers.
    """
    And the content of file "/tmp/textfile.txt" for user "guest@example.com" should be:
    """
    This is a testfile.

    Cheers.
    """

  @email
  Scenario: A guest user can cancel a chunked upload
    Given user "Alice" has been created with default attributes and small skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    When user "guest@example.com" creates a new chunking upload with id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "1" with "AAAAA" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "2" with "BBBBB" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" uploads new chunk file "3" with "CCCCC" to id "chunking-42" using the WebDAV API
    And user "guest@example.com" cancels chunking-upload with id "chunking-42" using the WebDAV API
    Then the HTTP status code should be "204"
    And as "Alice" file "/tmp/myChunkedFile.txt" should not exist

  @email
  Scenario: A guest user can upload a file and can reshare it
    Given these users have been created with default attributes and small skeleton files:
      | username |
      | Alice    |
      | Brian    |
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" has registered
    And user "guest@example.com" has uploaded file "textfile.txt" from the guests test data folder to "/tmp/textfile.txt"
    And user "guest@example.com" has shared file "/tmp/textfile.txt" with user "Brian"
    When user "guest@example.com" sends HTTP method "GET" to OCS API endpoint "/apps/files_sharing/api/v1/shares?reshares=true&path=/tmp/textfile.txt"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  @email
  Scenario: A guest user cannot reshare files
    Given these users have been created with default attributes and small skeleton files:
      | username |
      | Alice    |
      | Brian    |
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has created a share with settings
      | path        | /tmp              |
      | shareType   | user              |
      | shareWith   | guest@example.com |
      | permissions | delete            |
    And guest user "guest" has registered
    When user "guest@example.com" creates a share using the sharing API with settings
      | path        | /tmp  |
      | shareType   | user  |
      | shareWith   | Brian |
      | permissions | all   |
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  @email
  Scenario: A created guest user can log in
    Given user "Alice" has been created with default attributes and small skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "guest" should be a guest user
    And user "Alice" has shared file "/textfile1.txt" with user "guest@example.com"
    When guest user "guest" registers
    Then the HTTP status code should be "200"
    And user "guest@example.com" should see the following elements
      | /textfile1.txt |


  Scenario: Trying to create a guest user that already exists
    Given the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And user "guest" should be a guest user
    When the administrator attempts to create guest user "guest" with email "guest@example.com" using the API
    Then the HTTP status code should be "422"


  Scenario: removing a guest user from a group
    Given the administrator has created guest user "guest" with email "guest@example.com"
    And the HTTP status code should be "201"
    And group "guests_app" has been created
    And user "guest@example.com" has been added to group "guests_app"
    When the administrator sends HTTP method "DELETE" to OCS API endpoint "/cloud/users/guest@example.com/groups" with body
      | groupid | guests_app |
    Then the OCS status code should be "100"
    And user "guest@example.com" should not belong to group "guests_app"

  @email
  Scenario: A guest user can not create new guest users
    Given user "Alice" has been created with default attributes and small skeleton files
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has created folder "/tmp"
    And user "Alice" has shared folder "/tmp" with user "guest@example.com"
    And guest user "guest" registers
    When user "guest@example.com" attempts to create guest user "guest2" with email "guest2@example.com" using the API
    Then the HTTP status code should be "403"

  @email
  Scenario: Create a regular user using the same email address as an existing guest user
    Given the administrator has created guest user "guest" with email "guest@example.com"
    When the administrator creates these users with skeleton files:
      | username    | email             |
      | regularUser | guest@example.com |
    Then the email address of user "regularUser" should be "guest@example.com"

  @email @skip_on_objectstore
  Scenario: A guest user cannot view versions of resource when files_versions app is not in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And user "Alice" has uploaded file with content "some added content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has removed the app "files_versions" from the whitelist for the guest user
    When user "guest@example.com" gets the version metadata of file "textfile0.txt"
    Then the HTTP status code should be "207"
    And the version folder of file "/textfile0.txt" for user "guest@example.com" should contain "0" element
    But the version folder of file "/textfile0.txt" for user "Alice" should contain "1" element

  @email @skip_on_objectstore
  Scenario: A guest user cannot add versions of resource when files_versions app is not in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has removed the app "files_versions" from the whitelist for the guest user
    When user "guest@example.com" uploads file with content "some new content" to "/textfile0.txt" using the WebDAV API
    Then the HTTP status code should be "204"
    And the version folder of file "/textfile0.txt" for user "guest@example.com" should contain "0" element
    And the version folder of file "/textfile0.txt" for user "Alice" should contain "0" element

  @email
  Scenario: A guest user can view versions of resource when files_versions app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And user "Alice" has uploaded file with content "some added content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "files_versions" to the whitelist for the guest user
    When user "guest@example.com" gets the version metadata of file "textfile0.txt"
    Then the HTTP status code should be "207"
    And the version folder of file "/textfile0.txt" for user "guest@example.com" should contain "1" element
    And the version folder of file "/textfile0.txt" for user "Alice" should contain "1" element
    And the content of file "/textfile0.txt" for user "guest@example.com" should be "some added content"
    And the content of file "/textfile0.txt" for user "Alice" should be "some added content"

  @email
  Scenario: A guest user can add versions of resource when files_versions app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "files_versions" to the whitelist for the guest user
    When user "guest@example.com" uploads file with content "some new content" to "/textfile0.txt" using the WebDAV API
    Then the HTTP status code should be "204"
    And the version folder of file "/textfile0.txt" for user "guest@example.com" should contain "1" element
    And the version folder of file "/textfile0.txt" for user "Alice" should contain "1" element
    And the content of file "/textfile0.txt" for user "guest@example.com" should be "some new content"
    And the content of file "/textfile0.txt" for user "Alice" should be "some new content"

  @email
  Scenario: A guest user can add comments on a resource when comments app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "comments" to the whitelist for the guest user
    When user "guest@example.com" comments with content "A comment from guest" on file "/textfile0.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And user "Alice" should have the following comments on file "/textfile0.txt"
      | user              | comment              |
      | guest@example.com | A comment from guest |

  @email
  Scenario: A guest user can view comments on a resource when comments app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And user "Alice" has commented with content "My first comment" on folder "/textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "comments" to the whitelist for the guest user
    When user "guest@example.com" gets the following properties of file "/textfile0.txt" using the WebDAV API
      | propertyName      |
      | oc:comments-count |
    Then the HTTP status code should be "200"
    And the single response should contain a property "oc:comments-count" with value "1"
    And user "guest@example.com" should have the following comments on file "/textfile0.txt"
      | user  | comment          |
      | Alice | My first comment |

  @email
  Scenario: A guest user can edit own comments on a shared resource when comments app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "comments" to the whitelist for the guest user
    And user "guest@example.com" has commented with content "A comment from guest" on file "/textfile0.txt"
    When user "guest@example.com" edits the last created comment with content "My edited comment" using the WebDAV API
    Then the HTTP status code should be "207"
    And user "guest@example.com" should have the following comments on file "/textfile0.txt"
      | user              | comment           |
      | guest@example.com | My edited comment |

  @email
  Scenario: A guest user can delete own comments on a shared resource when comments app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And user "Alice" has commented with content "My first comment" on folder "/textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "comments" to the whitelist for the guest user
    And user "guest@example.com" has commented with content "A comment from guest" on file "/textfile0.txt"
    When user "guest@example.com" deletes the last created comment using the WebDAV API
    Then the HTTP status code should be "204"
    And user "guest@example.com" should have 1 comments on file "/textfile0.txt"
    And user "guest@example.com" should have the following comments on file "/textfile0.txt"
      | user  | comment          |
      | Alice | My first comment |

  @email
  Scenario: A guest user can view tags on resource when systemtags app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created a "normal" tag with name "MyFirstTag"
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And user "Alice" has added tag "MyFirstTag" to file "/textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "systemtags" to the whitelist for the guest user
    When user "guest@example.com" requests tags for file "/textfile0.txt" owned by user "Alice" using the WebDAV API
    Then the HTTP status code should be "207"
    And file "/textfile0.txt" should have the following tags for user "guest@example.com"
      | name       | type   |
      | MyFirstTag | normal |

  @email
  Scenario: A guest user can add tags on resource when systemtags app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created a "normal" tag with name "MyFirstTag"
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "systemtags" to the whitelist for the guest user
    When user "guest@example.com" adds tag "MyFirstTag" to file "/textfile0.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    And file "/textfile0.txt" shared by user "Alice" should have the following tags
      | name       | type   |
      | MyFirstTag | normal |

  @email
  Scenario: A guest user can delete tags on resource when systemtags app is in whitelist
    Given user "Alice" has been created with default attributes and without skeleton files
    And the administrator has created a "normal" tag with name "MyFirstTag"
    And the administrator has created a "normal" tag with name "MySecondTag"
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And user "Alice" has added tag "MyFirstTag" to file "/textfile0.txt"
    And user "Alice" has added tag "MySecondTag" to file "/textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "systemtags" to the whitelist for the guest user
    When user "guest@example.com" removes tag "MyFirstTag" from file "/textfile0.txt" shared by "Alice" using the WebDAV API
    Then the HTTP status code should be "204"
    And file "/textfile0.txt" should have the following tags for user "Alice"
      | name        | type   |
      | MySecondTag | normal |

  @email
  Scenario: a guest user cannot delete other users comments on resource when the comments app is whitelisted
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And user "Alice" has commented with content "My first comment" on file "/textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has added the app "comments" to the whitelist for the guest user
    When user "guest@example.com" deletes the last created comment using the WebDAV API
    Then the HTTP status code should be "403"
    And user "Alice" should have the following comments on file "/textfile0.txt"
      | user  | comment          |
      | Alice | My first comment |

  @email @issue-549
  Scenario: a guest user cannot add comments on resource when the comments app is not whitelisted
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has removed the app "comments" from the whitelist for the guest user
    When user "guest@example.com" comments with content "Comment from guest" on file "/textfile0.txt" using the WebDAV API
    Then the HTTP status code should be "201"
    # uncomment the line below and remove the line above when issue-549 is fixed
    #Then the HTTP status code should be "403"

  @email @issue-549
  Scenario: a guest user cannot view comments on a resource when the comments app is not whitelisted
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And user "Alice" has commented with content "My first comment" on file "/textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has removed the app "comments" from the whitelist for the guest user
    When user "guest@example.com" gets the following properties of file "/textfile0.txt" using the WebDAV API
      | propertyName      |
      | oc:comments-count |
    Then the HTTP status code should be "200"
    And the single response should contain a property "oc:comments-count" with value "1"
    # uncomment the line below and remove the line above when issue-549 is fixed
    #And the single response should contain a property "oc:comments-count" with value "0"

  @email @issue-549
  Scenario: a guest user cannot delete their comments on a resource when the comments app is not whitelisted
    Given user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "some content" to "textfile0.txt"
    And the administrator has created guest user "guest" with email "guest@example.com"
    And user "Alice" has shared file "/textfile0.txt" with user "guest@example.com"
    And guest user "guest" has registered
    And user "guest@example.com" has commented with content "My first comment" on file "/textfile0.txt"
    And the administrator has limited the guest access to the default whitelist apps
    And the administrator has removed the app "comments" from the whitelist for the guest user
    When user "guest@example.com" deletes the last created comment using the WebDAV API
    Then the HTTP status code should be "204"
    # uncomment the line above and remove the line above when issue-549 is fixed
    #Then the HTTP status code should be "403"
