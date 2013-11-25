<?php
class DataTypeIntTests extends PHPUnit_Framework_TestCase
{
	protected static $_keyspaceName = NULL;
	protected static $_tableName = NULL;
	protected static $_pbc = NULL;
	protected static $_rowKey = NULL;
	
	public static function setUpBeforeClass()
	{
		/**
		 * Generate random name for keyspace
		 * foramt ks_<5-digit number>
		 */
		self::$_keyspaceName = 'ks_'.substr(md5(rand()), 0, 5);
		
		/**
		 * Generate random name for column family
		 * foramt tbl_<5-digit number>
		 */
		self::$_tableName = 'tbl_'.substr(md5(rand()), 0, 5);
		
		/**
		 * Generate random rowkey
		 * foramt ks_<5-digit number>
		 */
		self::$_rowKey = md5(time());
	
		self::$_pbc = new \McFrazier\PhpBinaryCql\CqlClient(CASSANDRA_BINARY_CQL_HOST, CASSANDRA_BINARY_CQL_PORT);
		self::$_pbc->addStartupOption('CQL_VERSION', '3.0.4');
		
		// create the keyspace
		self::$_pbc->query('create KEYSPACE '.self::$_keyspaceName.' WITH REPLICATION = {\'class\' : \'SimpleStrategy\', \'replication_factor\': 1}', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		
		// use the newly create keyspace
		self::$_pbc->query('USE '.self::$_keyspaceName, \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		
		// create the column family
		self::$_pbc->query('CREATE TABLE '.self::$_tableName. ' (row_key varchar, col_double double, PRIMARY KEY (row_key))', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		
		// insert row_key into column family
		//self::$_pbc->query('INSERT INTO '.self::$_tableName. '(row_key) VALUES (\''.self::$_rowKey.'\')', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		
	}
	
	public static function  tearDownAfterClass()
	{
		// drop the keyspace
		self::$_pbc->query('DROP KEYSPACE '.self::$_keyspaceName, \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	}
	
	public function testMaxInteger()
	{
		$rowKey = md5(array_sum(explode(' ', microtime())));
		$double = 3.1415926535897932384626433832795; //
	
		// insert data
		$result = self::$_pbc->query('INSERT INTO '.self::$_tableName. '(row_key, col_double) VALUES ('.self::$_pbc->qq($rowKey).','.$double.');', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		$this->assertEquals('OK',$result->getData()->status);
	
		// select data
		$result = self::$_pbc->query('select col_double, row_key from '.self::$_tableName.' WHERE row_key='.self::$_pbc->qq($rowKey), \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);

		// check column spec for correct column type
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_DOUBLE,$result->getData()->rowMetadata->columnSpec[0]->optionId);
	
		// check column data
		$this->assertTrue(is_float($result->getData()->rowsContent[0]->col_double));
		//$this->assertEquals($double,$result->getData()->rowsContent[0]->col_double);
	}
}

