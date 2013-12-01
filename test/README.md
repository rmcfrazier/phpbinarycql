Php Binary CQL
============

Php implementation of the CQL binary protocol.  This project is a client that will allow queries to be sent using the binary protocol.

Required PHP Modules (not installed by default)
--------------------
iconv
mbstring
bcmath

Need to have PHPUnit installed
--------------------------------------
- version 3.7.28

Configure the phpunit.xml to point to your running Cassandra instance
<php>
	<const name="CASSANDRA_BINARY_CQL_HOST" value="192.168.2.240"/>
	<const name="CASSANDRA_BINARY_CQL_PORT" value="9042"/>
</php>

Running the tests
-----------------
All of the test require a running Cassandra instance.  A new keyspace and CFs will be created and removed after the tests are complete.

From the test directory enter
- phpunit --testsuite="allTests" to execute all of the tests.

You can run a specific suite of test by using the file path to the test file.  To run the tests for the text DataType enter this
- phpunit Integration/DataTypeTextTests.php