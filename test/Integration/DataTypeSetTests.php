<?php
class DataTypeSetTests extends PHPUnit_Framework_TestCase
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
		self::$_pbc->query('CREATE TABLE '.self::$_tableName. ' (row_key varchar, PRIMARY KEY (row_key))', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	}

	public static function  tearDownAfterClass()
	{
		// drop the keyspace
		self::$_pbc->query('DROP KEYSPACE '.self::$_keyspaceName, \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	}

	public function testIntSet()
	{
		$result = self::$_pbc->query('Alter TABLE '.self::$_tableName. ' ADD fld_set_int set<int>', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		//var_dump($result->getData());
		$this->assertEquals('UPDATED',$result->getData()->changeType);
		$this->assertEquals(self::$_keyspaceName,$result->getData()->affectedKeyspace);
		$this->assertEquals(self::$_tableName, $result->getData()->affectedTable);
	
		// ipv4 - update inet data
		$result = self::$_pbc->query('UPDATE '.self::$_tableName.' SET fld_set_int = {0,1,2,3,4} WHERE row_key = \''.self::$_rowKey.'\'', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		$this->assertEquals('OK',$result->getData()->status);
	
		// select data
		$result = self::$_pbc->query('select fld_set_int from '.self::$_tableName.' WHERE row_key=\''.self::$_rowKey.'\'', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		
		// check column spec for correct column type which is list
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_SET,$result->getData()->rowMetadata->columnSpec[0]->optionId);
		
		// this is a list of ints make sure that type is correct as well
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_INT,$result->getData()->rowMetadata->columnSpec[0]->optionValue);

		// should have an array
		$this->assertTrue(is_array($result->getData()->rowsContent[0]->fld_set_int));
		
		// check column data for correct column type
		$counter = 0;
		foreach($result->getData()->rowsContent[0]->fld_set_int as $item)
		{
			$this->assertSame((string)$counter,$item);
			$counter++;
		}
	
	}
	
	public function testVarcharSet()
	{
		$rowKey = md5(array_sum(explode(' ', microtime())));
		
		$result = self::$_pbc->query('Alter TABLE '.self::$_tableName. ' ADD fld_set_varchar set<varchar>', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		//var_dump($result->getData());
		$this->assertEquals('UPDATED',$result->getData()->changeType);
		$this->assertEquals(self::$_keyspaceName,$result->getData()->affectedKeyspace);
		$this->assertEquals(self::$_tableName, $result->getData()->affectedTable);
		
		// acsii
		$varChar[0] = 'zero';
		$varChar[1] = 'one';
		$varChar[2] = 'two';
		$varChar[3] = 'three';
		$varChar[4] = 'four';
		
		// utf8
		$varChar[5] = '일';
		$varChar[6] = '이';
		$varChar[7] = '삼';
		$varChar[8] = '사';
		$varChar[9] = '오';
	
		// ipv4 - update inet data
		$result = self::$_pbc->query('UPDATE '.self::$_tableName.' SET fld_set_varchar = {\''.implode("','",$varChar).'\'} WHERE row_key = \''.$rowKey.'\'', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		$this->assertEquals('OK',$result->getData()->status);
	
		// select data
		$result = self::$_pbc->query('select fld_set_varchar from '.self::$_tableName.' WHERE row_key=\''.$rowKey.'\'', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	
		// check column spec for correct column type which is list
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_SET,$result->getData()->rowMetadata->columnSpec[0]->optionId);
	
		// this is a list of ints make sure that type is correct as well
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_VARCHAR,$result->getData()->rowMetadata->columnSpec[0]->optionValue);
	
		// should have an array
		$this->assertTrue(is_array($result->getData()->rowsContent[0]->fld_set_varchar));

		// check column data for correct column type
		//$counter = 0;
		foreach($result->getData()->rowsContent[0]->fld_set_varchar as $item)
		{
			$this->assertTrue(in_array($item, $varChar));
		}
	
	}
	
	public function testUuidSet()
	{
		$rowKey = md5(array_sum(explode(' ', microtime())));
	
		$result = self::$_pbc->query('Alter TABLE '.self::$_tableName. ' ADD fld_set_uuid set<uuid>', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		//var_dump($result->getData());
		$this->assertEquals('UPDATED',$result->getData()->changeType);
		$this->assertEquals(self::$_keyspaceName,$result->getData()->affectedKeyspace);
		$this->assertEquals(self::$_tableName, $result->getData()->affectedTable);
	
		// UUID v1
		$uuid[0] = '6d3ff5a0-586b-11e3-949a-0800200c9a66';
		$uuid[1] = '6d3ff5a1-586b-11e3-949a-0800200c9a66';
		$uuid[2] = '6d3ff5a2-586b-11e3-949a-0800200c9a66';
		$uuid[3] = '6d3ff5a3-586b-11e3-949a-0800200c9a66';
		$uuid[4] = '6d3ff5a4-586b-11e3-949a-0800200c9a66';
		
		// UUID v4
		$uuid[5] = 'dfa5be3a-5a12-4bda-ad7d-45e04d5e9603';
		$uuid[6] = '9cd511ff-75ec-4ea0-a48a-af92be5654c1';
		$uuid[7] = '664b9bc6-aa76-4d5b-ab41-b1ee9566ed29';
		$uuid[8] = '71d1e847-2d43-487b-a2fe-b6bccd38b5f8';
		$uuid[9] = '14149f4a-b8d0-465d-9480-d5460a718d05';
	
		// ipv4 - update inet data
		$result = self::$_pbc->query('UPDATE '.self::$_tableName.' SET fld_set_uuid = {'.implode(',',$uuid).'} WHERE row_key = \''.$rowKey.'\'', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		$this->assertEquals('OK',$result->getData()->status);
	
		// select data
		$result = self::$_pbc->query('select fld_set_uuid from '.self::$_tableName.' WHERE row_key=\''.$rowKey.'\'', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	
		// check column spec for correct column type which is list
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_SET,$result->getData()->rowMetadata->columnSpec[0]->optionId);
	
		// this is a list of ints make sure that type is correct as well
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_UUID,$result->getData()->rowMetadata->columnSpec[0]->optionValue);
	
		// should have an array
		$this->assertTrue(is_array($result->getData()->rowsContent[0]->fld_set_uuid));
	
		// check column data for correct column type
		foreach($result->getData()->rowsContent[0]->fld_set_uuid as $item)
		{
			$this->assertTrue(in_array($item, $uuid));
		}
	}
	
}

