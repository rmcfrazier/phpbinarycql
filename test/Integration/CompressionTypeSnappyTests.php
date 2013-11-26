<?php
class DataTypeTextTests extends PHPUnit_Framework_TestCase
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
		self::$_pbc->addStartupOption('COMPRESSION', 'snappy'); // we are using the snappy compression !!!

		// create the keyspace
		self::$_pbc->query('create KEYSPACE '.self::$_keyspaceName.' WITH REPLICATION = {\'class\' : \'SimpleStrategy\', \'replication_factor\': 1}', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);

		// use the newly create keyspace
		self::$_pbc->query('USE '.self::$_keyspaceName, \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);

		// create the column family
		self::$_pbc->query('CREATE TABLE '.self::$_tableName. ' (row_key varchar, col_text text, PRIMARY KEY (row_key))', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	}

	public static function  tearDownAfterClass()
	{
		// drop the keyspace
		self::$_pbc->query('DROP KEYSPACE '.self::$_keyspaceName, \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
	}

	public function testInsertText()
	{
		$rowKey = md5(array_sum(explode(' ', microtime())));
		$text = $this->largeTextString(); 

		// insert data
		$result = self::$_pbc->query('INSERT INTO '.self::$_tableName. '(row_key, col_text) VALUES ('.self::$_pbc->qq($rowKey).','.self::$_pbc->qq($text).')', \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		$this->assertEquals('OK',$result->getData()->status);

		// select data
		$result = self::$_pbc->query('select col_text, row_key from '.self::$_tableName.' WHERE row_key='.self::$_pbc->qq($rowKey), \McFrazier\PhpBinaryCql\CqlConstants::QUERY_CONSISTENCY_ONE);
		// check column spec for correct column type
		// @TODO text column is returning varchar column type... need to find out why !!!
		//$this->assertEquals(\McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_TEXT,$result->getData()->rowMetadata->columnSpec[0]->optionId);

		// check column data
		$this->assertTrue(is_string($result->getData()->rowsContent[0]->col_text));
		$this->assertEquals($text,$result->getData()->rowsContent[0]->col_text);
	}
	
	
	protected function largeTextString()
	{
		$txt = 'Cras vitae orci porttitor, tincidunt est eu, dapibus libero. Pellentesque luctus arcu eu tortor euismod scelerisque. Nam porttitor, sapien non gravida imperdiet, dolor risus pulvinar nibh, non consequat massa nunc vitae leo. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nulla facilisi. Vestibulum eget tellus id enim ullamcorper faucibus id sit amet augue. Vivamus viverra nisl lectus, eget sollicitudin mi vestibulum aliquet. Nullam quis faucibus erat, vitae euismod metus. Phasellus sed condimentum ipsum, a convallis velit.';
		$txt .= 'Pellentesque posuere, quam vel placerat dignissim, sapien lacus ornare tellus, a convallis lacus augue sit amet mauris. Quisque in pretium nisl. Vestibulum consequat eget urna et pharetra. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Phasellus in arcu sed purus rutrum aliquam in sed sapien. Vivamus sodales urna ut vehicula lobortis. Interdum et malesuada fames ac ante ipsum primis in faucibus. Fusce vitae vestibulum metus. Quisque tempus pretium velit, quis dictum magna rhoncus a. Nulla facilisis lorem enim, a mollis mi cursus in. Ut ullamcorper vel felis et fringilla.';
		$txt .= 'Praesent elit massa, congue at malesuada tincidunt, euismod nec erat. Proin lacinia egestas leo. Integer ipsum libero, ultrices tristique erat id, dictum sagittis neque. Suspendisse tincidunt, magna in ornare mollis, tellus neque iaculis odio, eu ornare nunc sem non dui. Nam odio justo, blandit a dui eget, vestibulum porta dui. Fusce quis egestas velit. Etiam accumsan leo quis purus sagittis tincidunt.';
		$txt .= 'Quisque id ornare ipsum. Nam elementum dictum luctus. Nulla et urna quis felis mattis placerat. Nunc facilisis lobortis mattis. Quisque ac vulputate ante. Donec euismod fringilla erat vel consequat. Phasellus commodo libero a nibh elementum elementum. Sed vitae lectus et odio tincidunt tincidunt quis vitae neque. Curabitur dui nibh, adipiscing at nisi non, dictum ultrices tellus. Curabitur molestie justo sapien, eu scelerisque mi accumsan ac. Vivamus iaculis nisi nisi, eu sagittis nunc vulputate vel. Integer vitae dui velit. Nulla non est nec justo malesuada malesuada. Cras dignissim elementum dui, vel tincidunt quam. In ornare purus eget sem vulputate consectetur. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;';
		//$txt .= 'Suspendisse potenti. Morbi a consectetur felis, id vulputate elit. In quis sapien tellus. Phasellus placerat lectus tellus, nec cursus turpis eleifend at. Fusce mattis vehicula justo, id cursus quam auctor vel. Mauris at tortor metus. Proin hendrerit ligula sit amet orci aliquet tincidunt. Aenean laoreet arcu orci, vitae aliquam dui blandit eu. Praesent nec justo pellentesque, eleifend ante laoreet, scelerisque nibh. Vivamus dapibus ligula id risus egestas, a ultricies enim sodales. Nulla ornare, tellus ut feugiat aliquet, risus augue rutrum orci, in suscipit tellus ligula nec velit. Donec consectetur luctus diam et iaculis. Proin risus lorem, dapibus eget iaculis in, commodo quis est.';
		return $txt;
	}

}
