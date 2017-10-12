Feature: Guests

Background:
	Given using api version "1"
	And using new dav path

Scenario: Creating a guest user works fine
	Given as an "admin"
	When user "admin" creates guest user "guest" with email "guest@example.com"
	Then the HTTP status code should be "201"
	And check that user "guest" is a guest

Scenario: Cannot create a guest if a user with the same email address exists
	Given as an "admin"
	And user "existing-user" exists
	When sending "PUT" to "/cloud/users/existing-user" with
		| key | email |
		| value | guest@example.com |
	When user "admin" creates guest user "guest" with email "guest@example.com"
	Then the HTTP status code should be "422"
	# TODO: missing appropriate step in core / Provisioning
	#And check that user "guest" does not exist

Scenario: A guest user cannot upload files
	Given as an "admin"
	And user "admin" creates guest user "guest" with email "guest@example.com"
	And the HTTP status code should be "201"
	When user "guest@example.com" uploads file "data/textfile.txt" to "/myfile.txt"
	Then the HTTP status code should be "401"

Scenario: A guest user can upload files
	Given as an "admin"
	And user "user0" exists
	And user "admin" creates guest user "guest" with email "guest@example.com"
	And the HTTP status code should be "201"
	And user "user0" created a folder "/tmp"
	And folder "/tmp" of user "user0" is shared with user "guest@example.com"
	And guest user "guest" registers
	When user "guest@example.com" uploads file "data/textfile.txt" to "/tmp/textfile.txt"
	Then the HTTP status code should be "201"

Scenario: A guest user can upload a file and can reshare it
        Given as an "admin"
        And user "user0" exists
        And user "user1" exists
        And user "admin" creates guest user "guest" with email "guest@example.com"
        And the HTTP status code should be "201"
        And user "user0" created a folder "/tmp"
        And folder "/tmp" of user "user0" is shared with user "guest@example.com"
        And guest user "guest" registers
        And user "guest@example.com" uploads file "data/textfile.txt" to "/tmp/textfile.txt"
        And file "/tmp/textfile.txt" of user "guest@example.com" is shared with user "user1"
        And as an "guest@example.com"
        When sending "GET" to "/apps/files_sharing/api/v1/shares?reshares=true&path=/tmp/textfile.txt"
        Then the OCS status code should be "100"
        And the HTTP status code should be "200"

Scenario: A guest user cannot reshare files
        Given as an "admin"
        And user "user0" exists
        And user "user1" exists
        And user "admin" creates guest user "guest" with email "guest@example.com"
        And the HTTP status code should be "201"
        And user "user0" created a folder "/tmp"
        And as an "user0"
        And creating a share with
                        | path | /tmp |
                        | shareType | 0 |
                        | shareWith | guest@example.com |
                        | permissions | 8 |
        And guest user "guest" registers
        And as an "guest@example.com"
                When creating a share with
                        | path | /tmp |
                        | shareType | 0 |
                        | shareWith | user1 |
                        | permissions | 31 |
        Then the OCS status code should be "404"
		And the HTTP status code should be "200"
	
Scenario: Check that skeleton is properly set
	Given as an "admin"
	And user "user0" exists
	Then user "user0" should see following elements
		| /FOLDER/ |
		| /PARENT/ |
		| /PARENT/parent.txt |
		| /textfile0.txt |
		| /textfile1.txt |
		| /textfile2.txt |
		| /textfile3.txt |
		| /textfile4.txt |
		| /welcome.txt |

Scenario: A created guest user can log in
	Given as an "admin"
	And user "user0" exists
	And user "admin" creates guest user "guest" with email "guest@example.com"
	And the HTTP status code should be "201"
	And check that user "guest" is a guest
	And file "/textfile1.txt" of user "user0" is shared with user "guest@example.com"
	When guest user "guest" registers
	Then the HTTP status code should be "200"
	And user "guest@example.com" should see following elements
		| /textfile1.txt |

Scenario: Trying to create a guest user that already exists
	Given as an "admin"
	And user "admin" creates guest user "guest" with email "guest@example.com"
	And the HTTP status code should be "201"
	And check that user "guest" is a guest
	When user "admin" creates guest user "guest" with email "guest@example.com"
	Then the HTTP status code should be "422"

Scenario: removing a user from a group
        Given as an "admin"
        And user "admin" creates guest user "guest" with email "guest@example.com"
        And the HTTP status code should be "201"
        And group "guests_app" exists
        And user "guest@example.com" belongs to group "guests_app"
        When sending "DELETE" to "/cloud/users/guest@example.com/groups" with
                | groupid | guests_app |
        Then the OCS status code should be "100"
        And user "guest@example.com" does not belong to group "guests_app"
