Feature: Guests

Background:
	Given using api version "1"
	And using new dav path

Scenario: Creating a guest user works fine
	Given As an "admin"
	When user "admin" creates guest user "guest" with email "guest@example.com"
	Then the HTTP status code should be "201"
	And check that user "guest" is a guest

Scenario: A guest user cannot upload files
	Given As an "admin"
	And user "admin" creates guest user "guest" with email "guest@example.com"
	And the HTTP status code should be "201"
	When User "guest@example.com" uploads file "data/textfile.txt" to "/myfile.txt"
	Then the HTTP status code should be "401"

Scenario: A guest user can upload files
	Given As an "admin"
	And user "user0" exists
	And user "admin" creates guest user "guest" with email "guest@example.com"
	And the HTTP status code should be "201"
	And user "user0" created a folder "/tmp"
	And folder "/tmp" of user "user0" is shared with user "guest@example.com"
	And guest user "guest" sets its password
	When User "guest@example.com" uploads file "data/textfile.txt" to "/tmp/textfile.txt"
	Then the HTTP status code should be "201"

Scenario: A guest user can upload a file and can reshare it
        Given As an "admin"
        And user "user0" exists
        And user "user1" exists
        And user "admin" creates guest user "guest" with email "guest@example.com"
        And the HTTP status code should be "201"
        And user "user0" created a folder "/tmp"
        And folder "/tmp" of user "user0" is shared with user "guest@example.com"
        And guest user "guest" sets its password
        And User "guest@example.com" uploads file "data/textfile.txt" to "/tmp/textfile.txt"
        And file "/tmp/textfile.txt" of user "guest@example.com" is shared with user "user1"
        And As an "guest@example.com"
        When sending "GET" to "/apps/files_sharing/api/v1/shares?reshares=true&path=/tmp/textfile.txt"
        Then the OCS status code should be "100"
        And the HTTP status code should be "200"

Scenario: A guest user cannot reshare files
        Given As an "admin"
        And user "user0" exists
        And user "user1" exists
        And user "admin" creates guest user "guest" with email "guest@example.com"
        And the HTTP status code should be "201"
        And user "user0" created a folder "/tmp"
        And As an "user0"
        And creating a share with
                        | path | /tmp |
                        | shareType | 0 |
                        | shareWith | guest@example.com |
                        | permissions | 8 |
        And guest user "guest" sets its password
        And As an "guest@example.com"
                When creating a share with
                        | path | /tmp |
                        | shareType | 0 |
                        | shareWith | user1 |
                        | permissions | 31 |
        Then the OCS status code should be "404"
		And the HTTP status code should be "200"
	
Scenario: Check that skeleton is properly set
	Given As an "admin"
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
	Given As an "admin"
	And user "user0" exists
	And user "admin" creates guest user "guest" with email "guest@example.com"
	And the HTTP status code should be "201"
	And check that user "guest" is a guest
	And file "/textfile1.txt" of user "user0" is shared with user "guest@example.com"
	When guest user "guest" sets its password
	Then the HTTP status code should be "200"
	And user "guest@example.com" should see following elements
		| /textfile1.txt |

Scenario: Trying to create a guest user that already exists
	Given As an "admin"
	And user "admin" creates guest user "guest" with email "guest@example.com"
	And the HTTP status code should be "201"
	And check that user "guest" is a guest
	When user "admin" creates guest user "guest" with email "guest@example.com"
	Then the HTTP status code should be "422"

Scenario: removing a user from a group
        Given As an "admin"
        And user "admin" creates guest user "guest" with email "guest@example.com"
        And the HTTP status code should be "201"
        And group "guests_app" exists
        And user "guest@example.com" belongs to group "guests_app"
        When sending "DELETE" to "/cloud/users/guest@example.com/groups" with
                | groupid | guests_app |
        Then the OCS status code should be "100"
        And user "guest@example.com" does not belong to group "guests_app"
