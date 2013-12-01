<?php
class DataTypeMapTests extends PHPUnit_Framework_TestCase
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
	
	public function testVarchar_VarcharMap()
	{
		$rowKey = md5(array_sum(explode(' ', microtime())));
		
		$result = self::$_pbc->query('Alter TABLE '.self::$_tableName. ' ADD fld_map_varchar_varchar map<varchar, varchar>', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		//var_dump($result->getData());
		$this->assertEquals('UPDATED',$result->getData()->changeType);
		$this->assertEquals(self::$_keyspaceName,$result->getData()->affectedKeyspace);
		$this->assertEquals(self::$_tableName, $result->getData()->affectedTable);
		
		// acsii
		$varChar['zero'] = 'This is zero';
		$varChar['one'] = 'This is one';
		$varChar['two'] = 'This is two';
		$varChar['three'] = 'This is three';
		$varChar['four'] = 'This is four';
		
		// utf8
// 		$varChar[5] = '일';
// 		$varChar[6] = '이';
// 		$varChar[7] = '삼';
// 		$varChar[8] = '사';
// 		$varChar[9] = '오';
	
		// ipv4 - update inet data
		foreach($varChar as $key => $val)
		{
			$result = self::$_pbc->query('UPDATE '.self::$_tableName.' SET fld_map_varchar_varchar[\''.$key.'\'] = \''.$val.'\' WHERE row_key = \''.$rowKey.'\'', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
			$this->assertEquals('OK',$result->getData()->status);
		}
	
		// select data
		$result = self::$_pbc->query('select fld_map_varchar_varchar from '.self::$_tableName.' WHERE row_key=\''.$rowKey.'\'', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	
		// check column spec for correct column type which is list
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_MAP,$result->getData()->rowMetadata->columnSpec[0]->optionId);
	
		// this is a list of ints make sure that type is correct as well.. maps are a little
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_VARCHAR,$result->getData()->rowMetadata->columnSpec[0]->optionValue->keyType);
		$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_VARCHAR,$result->getData()->rowMetadata->columnSpec[0]->optionValue->valueType);
		
		// should have an array
		//$this->assertTrue(is_array($result->getData()->rowsContent[0]->fld_map_varchar_varchar));

		// check column data for correct column type
		foreach($result->getData()->rowsContent[0]->fld_map_varchar_varchar as $key => $val)
		{
			$this->assertTrue(array_key_exists($key, $varChar));
			$this->assertSame($varChar[$key],$val);
		}
	}
}
