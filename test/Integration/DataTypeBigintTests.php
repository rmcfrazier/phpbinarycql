<?php
class DataTypeBigintTests extends PHPUnit_Framework_TestCase
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
		self::$_pbc->query('CREATE TABLE '.self::$_tableName. ' (row_key varchar, col_bigint bigint, PRIMARY KEY (row_key))', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	}

	public static function  tearDownAfterClass()
	{
		// drop the keyspace
		self::$_pbc->query('DROP KEYSPACE '.self::$_keyspaceName, \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	}

	public function testInsertMaxBigint()
	{
		$rowKey = md5(array_sum(explode(' ', microtime())));
		$maxInt = '9223372036854775807'; // maximum 64 bit signed long

		// insert data
		$result = self::$_pbc->query('INSERT INTO '.self::$_tableName. '(row_key, col_bigint) VALUES ('.self::$_pbc->qq($rowKey).','.$maxInt.')', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		$this->assertEquals('OK',$result->getData()->status);

		// select data
		$result = self::$_pbc->query('select col_bigint, row_key from '.self::$_tableName.' WHERE row_key='.self::$_pbc->qq($rowKey), \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);

		// check column spec for correct column type
		$this->assertSame(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BIGINT,$result->getData()->rowMetadata->columnSpec[0]->optionId);

		// check column data
		$this->assertTrue(is_string($result->getData()->rowsContent[0]->col_bigint));
		$this->assertSame($maxInt,$result->getData()->rowsContent[0]->col_bigint);
	}

	public function testInsertMinBigint()
	{
		$rowKey = md5(array_sum(explode(' ', microtime())));
		$minInt = '-9223372036854775808'; // minimum 64 bit signed long

		// insert data
		$result = self::$_pbc->query('INSERT INTO '.self::$_tableName. '(row_key, col_bigint) VALUES ('.self::$_pbc->qq($rowKey).','.$minInt.');', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		$this->assertEquals('OK',$result->getData()->status);

		// select data
		$result = self::$_pbc->query('select col_bigint, row_key from '.self::$_tableName.' WHERE row_key='.self::$_pbc->qq($rowKey), \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		
		// check column spec for correct column type
		$this->assertSame(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BIGINT,$result->getData()->rowMetadata->columnSpec[0]->optionId);

		// check column data
		$this->assertTrue(is_string($result->getData()->rowsContent[0]->col_bigint));
		$this->assertSame($minInt,$result->getData()->rowsContent[0]->col_bigint);
	}
	
	public function testInsertBigints()
	{
		
		$bigInts = array();
		$bigInts[] = '-8223372036854775808';
		$bigInts[] = '-7223372036854775808';
		$bigInts[] = '-6223372036854775808';
		$bigInts[] = '-5223372036854775808';
		$bigInts[] = '-4223372036854775808';
		$bigInts[] = '-3223372036854775808';
		$bigInts[] = '-2223372036854775808';
		$bigInts[] = '-1223372036854775808';
		$bigInts[] = '1223372036854775808';
		$bigInts[] = '2223372036854775808';
		$bigInts[] = '3223372036854775808';
		$bigInts[] = '4223372036854775808';
		$bigInts[] = '5223372036854775808';
		$bigInts[] = '6223372036854775808';
		$bigInts[] = '7223372036854775808';
		$bigInts[] = '8223372036854775808';
		
		$insertArray = array();
		
		// inserts
		foreach($bigInts as $item) {
		// insert data
			$rowKey = md5(array_sum(explode(' ', microtime())));
			$result = self::$_pbc->query('INSERT INTO '.self::$_tableName. '(row_key, col_bigint) VALUES ('.self::$_pbc->qq($rowKey).','.$item.');', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
			$this->assertEquals('OK',$result->getData()->status);
			$insertArray[$rowKey] = $item;
		}
		
		// selects
		foreach($insertArray as $key => $val)
		{
			// select data
			$result = self::$_pbc->query('select col_bigint, row_key from '.self::$_tableName.' WHERE row_key='.self::$_pbc->qq($key), \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
			
			// check column spec for correct column type
			$this->assertSame(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BIGINT,$result->getData()->rowMetadata->columnSpec[0]->optionId);
			
			// check column data
			$this->assertTrue(is_string($result->getData()->rowsContent[0]->col_bigint));
			$this->assertSame($val,$result->getData()->rowsContent[0]->col_bigint);
		}

	}

}