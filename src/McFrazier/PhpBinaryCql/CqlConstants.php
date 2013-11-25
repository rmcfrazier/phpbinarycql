<?php
/**
 * PhpBinaryCql
 *
 * @link https://github.com/rmcfrazier/phpbinarycql
 * @copyright Copyright (c) 2013 Robert McFrazier
 * @license http://opensource.org/licenses/MIT
 */
namespace McFrazier\PhpBinaryCql;

/**
 * CQL binary protocol constants
 */
class CqlConstants {
	
	/**
	 * Frame version - request
	 * @var integer
	 */
	const FRAME_REQUEST_VERSION = 0x0001;
	
	/**
	 * Frame version - response
	 * @var integer
	 */
	const FRAME_RESPONSE_VERSION = 0x0081;
	
	/**
	 * Frame opcode - error
	 * @var integer
	 */
	const FRAME_OPCODE_ERROR = 0x00;
	
	/**
	 * Frame opcode - startup
	 * @var integer
	 */
	const FRAME_OPCODE_STARTUP = 0x01;
	
	/**
	 * Frame opcode - ready
	 * @var integer
	 */
	const FRAME_OPCODE_READY = 0x02;
	
	/**
	 * Frame opcode - authenticate
	 * @var integer
	 */
	const FRAME_OPCODE_AUTHENTICATE = 0x03;

	/**
	 * Frame opcode - options
	 * @var integer
	 */
	const FRAME_OPCODE_OPTIONS = 0x05; 
	
	/**
	 * Frame opcode - supported
	 * @var integer
	 */
	const FRAME_OPCODE_SUPPORTED = 0x06;
	
	/**
	 * Frame opcode - query
	 * @var integer
	 */
	const FRAME_OPCODE_QUERY = 0x07;
	
	/**
	 * Frame opcode - result
	 * @var integer
	 */
	const FRAME_OPCODE_RESULT = 0x08;
	
	/**
	 * Frame opcode - prepare
	 * @var integer
	 */
	const FRAME_OPCODE_PREPARE = 0x09;
	
	/**
	 * Frame opcode - execute
	 * @var integer
	 */
	const FRAME_OPCODE_EXECUTE = 0x0A;    
	    
	/**
	 * Frame flag - none
	 * @var integer
	 */
	const FRAME_FLAG_EMPTY = 0x00;
	
	/**
	 * Frame flag - compression
	 * @var integer
	 */
	const FRAME_FLAG_COMPRESSION = 0x01;
	
	/**
	 * Frame flag - tracing
	 * @var integer
	 */
	const FRAME_FLAG_TRACING = 0x02;
	
	/**
	 * Frame stream - default
	 * @var integer
	 */
	const FRAME_STREAM_DEFAULT = 0x00;
	
	/**
	 * Query consistency - any
	 * @var integer
	 */
	const QUERY_CONSISTENCY_ANY = 0x0000;
	
	/**
	 * Query consistency - one
	 * @var integer
	 */
	const QUERY_CONSISTENCY_ONE = 0x0001;
	
	/**
	 * Query consistency - two
	 * @var integer
	 */
	const QUERY_CONSISTENCY_TWO = 0x0002;
	
	/**
	 * Query consistency - three
	 * @var integer
	 */
	const QUERY_CONSISTENCY_THREE = 0x0003;
	
	/**
	 * Query consistency - quorum
	 * @var integer
	 */
	const QUERY_CONSISTENCY_QUORUM = 0x0004;
	
	/**
	 * Query consistency - all
	 * @var integer
	 */
	const QUERY_CONSISTENCY_ALL = 0x0005;
	
	/**
	 * Query consistency - local quorum
	 * @var integer
	 */
	const QUERY_CONSISTENCY_LOCAL_QUORUM = 0x0006;
	
	/**
	 * Query consistency - each quorum
	 * @var integer
	 */
	const QUERY_CONSISTENCY_EACH_QUORUM = 0x0007;

	/**
	 * Result type - void
	 * @var integer
	 */
	const RESULT_TYPE_VOID = 0x0001;
	
	/**
	 * Result type - rows
	 * @var integer
	 */
	const RESULT_TYPE_ROWS = 0x0002;
	
	/**
	 * Result type - set key space
	 * @var integer
	 */
	const RESULT_TYPE_SET_KEYSPACE = 0x0003;
	
	/**
	 * Result type - prepared
	 * @var integer
	 */
	const RESULT_TYPE_PREPARED = 0x0004;
	
	/**
	 * Result type - schema change
	 * @var integer
	 */
	const RESULT_TYPE_SCHEMA_CHANGE = 0x0005;
	
	/**
	 * Column type - custom
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_CUSTOM = 0x0000;
	
	/**
	 * Column type - acsii
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_ASCII = 0x0001;
	
	/**
	 * Column type - bigint
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_BIGINT = 0x0002;
	
	/**
	 * Column type - blob
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_BLOB = 0x0003;
	
	/**
	 * Column type - boolean
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_BOOLEAN = 0x0004;
	
	/**
	 * Column type - counter
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_COUNTER = 0x0005;
	
	/**
	 * Column type - decimal
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_DECIMAL = 0x0006;
	
	/**
	 * Column type - double
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_DOUBLE = 0x0007;
	
	/**
	 * Column type - float
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_FLOAT = 0x0008;
	
	/**
	 * Column type - int
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_INT = 0x0009;
	
	/**
	 * Column type - text
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_TEXT = 0x000A;
	
	/**
	 * Column type - timestamp
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_TIMESTAMP = 0x000B;
	
	/**
	 * Column type - UUID
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_UUID = 0x000C;
	
	/**
	 * Column type - varchar
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_VARCHAR = 0x000D;
	
	/**
	 * Column type - varint
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_VARINT = 0x000E;
	
	/**
	 * Column type - TimeUUID
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_TIMEUUID = 0x000F;
	
	/**
	 * Column type - inet
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_INET = 0x0010;
	
	/**
	 * Column type - list
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_LIST = 0x0020;
	
	/**
	 * Column type - map
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_MAP = 0x0021;
	
	/**
	 * Column type - set
	 * @var integer
	 */
	const COLUMN_TYPE_OPTION_SET = 0x0022;
	
	/**
	 * Error code - server error
	 * @var integer
	 */
	const ERROR_CODE_SERVER_ERROR = 0x0000;
	
	/**
	 * Error code - protocol error
	 * @var integer
	 */
	const ERROR_CODE_PROTOCOL_ERROR = 0x000A;
	
	/**
	 * Error code - bad credentials
	 * @var integer
	 */
	const ERROR_CODE_BAD_CREDENTIALS = 0x0100;
	
	/**
	 * Error code - unavailable exception
	 * @var integer
	 */
	const ERROR_CODE_UNAVAILABLE_EXCEPTION = 0x1000;
	
	/**
	 * Error code - overloaded
	 * @var integer
	 */
	const ERROR_CODE_OVERLOADED = 0x1001;
	
	/**
	 * Error code - is bootstrapping
	 * @var integer
	 */
	const ERROR_CODE_IS_BOOTSTRAPPING = 0x1002;
	
	/**
	 * Error code - truncate error
	 * @var integer
	 */
	const ERROR_CODE_TRUNCATE_ERROR = 0x1003;
	
	/**
	 * Error code - write timeout
	 * @var integer
	 */
	const ERROR_CODE_WRITE_TIMEOUT = 0x1100;

	/**
	 * Error code - read timeout
	 * @var integer
	 */
	const ERROR_CODE_READ_TIMEOUT = 0x1200;
	
	/**
	 * Error code - syntax error
	 * @var integer
	 */
	const ERROR_CODE_SYNTAX_ERROR = 0x2000;
	
	/**
	 * Error code - unauthorized
	 * @var integer
	 */
	const ERROR_CODE_UNAUTHORIZED = 0x2100;
	
	/**
	 * Error code - invalid
	 * @var integer
	 */
	const ERROR_CODE_INVALID = 0x2200;
	
	/**
	 * Error code - config error
	 * @var integer
	 */
	const ERROR_CODE_CONFIG_ERROR = 0x2300;
	
	/**
	 * Error code - already exists
	 * @var integer
	 */
	const ERROR_CODE_ALREADY_EXISTS = 0x2400;
	
	/**
	 * Error code - unprepared
	 * @var integer
	 */
	const ERROR_CODE_UNPREPARED = 0x2500;
	
	/**
	 * Rows Metadata Flag - global tables spec
	 * @var integer
	 */
	const ROWS_METADATA_FLAG_GLOBAL_TABLES_SPEC =  0x0001;
}