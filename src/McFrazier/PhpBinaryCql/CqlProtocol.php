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
 * Class that generates and parses a binary CQL protocol frame
 * CQL Binary Spec URL:
 * https://git-wip-us.apache.org/repos/asf?p=cassandra.git;a=blob_plain;f=doc/native_protocol.spec;hb=refs/heads/cassandra-1.2
 */
class CqlProtocol
{
	/**
	 * Counter used while parsing a frame.
	 * @var integer
	 */
	private $_offset = 0;
	
	/**
	 * Counter used while genearting a frame.
	 * @var integer
	 */
	private $_byteCount = 0;
	
	/**
	 * Array to hold protocol actions, used for debugging.
	 * @var array
	 */
	private $_actionLog = array();
	
	/**
	 * Parse the binary frame header.
	 * The frame header is 8 bytes, after unpacking into the hex representation
	 * we have a 16 byte string... the frame header is broken down in to the following parts
	 * 
	 * 0-1 : version
	 * 2-3 : flags
	 * 4-5 : streamId
	 * 6-7 : opcode
	 * 8-16 : body length
	 * 
	 * @param string $binaryFrameHeader
	 * @return stdClass
	 */
	public function parseFrameHeader($binaryFrameHeader)
	{
		$frameHeader = new \stdClass();
		
		$frameHeaderHex = bin2hex($binaryFrameHeader);
		
		// frame version
		$frameHeader->version = substr($frameHeaderHex, 0, 2);
		
		// get frame flag
		$frameHeader->flag = substr($frameHeaderHex, 2, 2);
		
		// get frame stream
		$frameHeader->stream = substr($frameHeaderHex, 4, 2);
		
		// get frame opcode
		$frameHeader->opcode = substr($frameHeaderHex, 6, 2);
		
		// get frame body length
		$frameHeader->bodyLength = hexdec(substr($frameHeaderHex, 8, 16));
		
		return $frameHeader;
	}
	
	/**
	 * Builds a binary frame, from a frame object.
	 * 
	 * @param McFrazier\PhpBinaryCql\CqlFrame $frame
	 * @return string
	 */
	public function generateBinaryFrame($frame)
	{
		// get flag status
		$flagStatus = $this->_checkFrameFlags($frame->getFlag());
		
		// binary body length
		$frameBody = $frame->getBody();
		
		$frameBodyLength = NULL;
		// @TODO need to add a check to check which compression was used
		// in the frame, can't assume snappy.
		if($flagStatus->compression) {
			// compressed
			$frameBody = snappy_compress($frameBody);
			$bodyLength = strlen($frameBody);
			$frameBodyLength = $this->generateInt($bodyLength);
		} else {
			// not compressed
			$bodyLength = strlen($frameBody);
			$frameBodyLength = $this->generateInt($bodyLength);
		}
		
		// build frame header
		$header =  pack("hhhh", $frame->getVersion(), $frame->getFlag(), $frame->getStream(), $frame->getOpcode());
		$header .= $frameBodyLength;
		
		return $header.$frameBody;
	}
	
	/**
	 * Generate a binary integer.
	 * A 4 bytes integer.
	 * 
	 * @param integer $integer
	 * @return string
	 */
	public function generateInt($integer)
	{
		// return a unsigned short big endian
		$intHex = substr("00000000".dechex((int)$integer), -8);
	
		if(is_int($intHex) && ($intHex <= 9)) {
			$intBin = pack('N',$intHex);
		} else {
			$intBin = pack('H*',$intHex);
		}
		$this->_byteCount += 4;
		
		return $intBin;
	}
	
	/**
	 * Generate binary Long string.
	 * An [int] n, followed by n bytes representing an UTF-8 string.
	 * 
	 * @param string $string
	 * @return string
	 */
	public function generateLongString($string)
	{
		$len = $this->generateInt(strlen($string));
		$stringBin = $this->_convertAsciiToBinary($string);
		$this->_byteCount += strlen($stringBin);
		
		return $len.$stringBin;
	}
	
	/**
	 * Generate binary bytes.
	 * A [int] n, followed by n bytes if n >= 0. If n < 0, 
	 * no byte should follow and the value represented is `null`.
	 * 
	 * @param string $string
	 * @return string
	 */
	public function generateBytes($string)
	{
		$len = $this->generateInt(strlen($string));
		$binString = $this->_convertAsciiToBinary($string);
		
		$this->_byteCount += strlen($binString);
		
		return $len.$binString;
	}
	
	/**
	 * Generate a binary short.
	 * A 2 bytes unsigned integer.
	 * 
	 * @param integer $short
	 * @return string
	 */
	public function generateShort($short)
	{
		// return a unsigned short big endian
		$shortHex = substr("0000".dechex((int)$short),-4);
		$shortBin = pack('H*',$shortHex);
		$this->_byteCount += 2;
		
		return $shortBin;
	}
	
	/**
	 * Generate binary short bytes.
	 * A [short] n, followed by n bytes if n >= 0.
	 * 
	 * @param unknown $string
	 * @return string
	 */
	public function generateShortBytes($string)
	{
		$len = $this->generateShort(strlen($string));
		$binString = $this->_convertAsciiToBinary($string);
		
		$this->_byteCount += strlen($binString);
		
		return $len.$binString;
	}
	
	/**
	 * Generate binary string.
	 * A [short] n, followed by n bytes representing an UTF-8 string.
	 * 
	 * @param unknown $string
	 * @return string
	 */
	public function generateString($string)
	{
		$len = $this->generateShort(strlen($string));
		$stringBin = $this->_convertAsciiToBinary($string);
		$this->byteCount += strlen($stringBin);
		
		return $len.$stringBin;
	}
	
	/**
	 * Generate binary String map.
	 * A [short] n, followed by n pair <k><v> where <k> and <v> are [string].
	 * 
	 * @param array $map
	 * @return string
	 */
	public function generateStringmap($map)
	{
		$mapSize = $this->generateShort(count($map));
		$hexString = '';

		foreach($map as $key => $val)
		{
			$hexString .= $this->generateString($key).$this->generateString($val);
		}
		
		return $mapSize.$hexString;
	}
	
	/**
	 * Parse binary frame.
	 * 
	 * @param unknown $frame
	 * @return stdClass | array | 
	 */
	public function parseBinaryFrame($frame)
	{
		$data = NULL;
		$frameBody = NULL;
		$traceId = NULL;
		
		// check frame flags
		$flagStatus = $this->_checkFrameFlags($frame->getFlag());
		
		// do we need to decompress
		// @TODO need to add check to see which compression has been used, can't assume snappy
		// all the time...
		if($flagStatus->compression) {
			$frameBody =  snappy_uncompress($frame->getBody());
		} else {
			$frameBody =  $frame->getBody();
		}

		// do we need to grab a tracing id
		if($flagStatus->tracing) {
			$traceId =  $this->parseUuid($frameBody);
		}

		switch($frame->getOpcode())
		{
			case \McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_ERROR:
				$data = $this->parseError($frameBody);
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_SUPPORTED:
				$data = $this->parseStringMultimap($frameBody);
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_RESULT:
				$data = $this->parseResult($frameBody);
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_AUTHENTICATE:
				$data = $this->parseString($frameBody);
				break;
		}
		
		// add the tracing id... if present
		if($traceId) {
			$data->traceId = $traceId;
		}
	
		return $data;
	}
	
	/**
	 * Parse bytes.
	 * A [int] n, followed by n bytes if n >= 0. If n < 0, 
	 * no byte should follow and the value represented is `null`.
	 * 
	 * @param unknown $frameBody
	 * @param string $returnBinary
	 * @return string
	 */
	public function parseBytes($frameBody, $returnBinary = FALSE)
	{
		// get string length which is a short
		$processIntResult =  $this->parseInt($frameBody);
	
		// get binary string
		$returnString = NULL;
		$binString = substr($frameBody, $this->_offset, $processIntResult);
		if($returnBinary) {
			$returnString = $binString;
		} else {
			$returnString = bin2hex($binString);
		}

		// add length of string to internal offset
		$this->_offset += $processIntResult;
		
		// log action
		$this->_actionLog[] = 'Parse bytes: '.bin2hex($binString);
			
		return $returnString;
	}
	
	/**
	 * Parse a CQL error frame.
	 * 
	 * @param string $frameBody
	 * @return stdClass
	 */
	public function parseError($frameBody)
	{
		// get error code
		$processIntResult = $this->parseInt($frameBody);
		
		// get error message
		$processStringResult = $this->parseString($frameBody);
	
		$data = NULL;
		switch($processIntResult)
		{
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_SERVER_ERROR:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_ALREADY_EXISTS:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_BAD_CREDENTIALS:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_OVERLOADED:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_IS_BOOTSTRAPPING:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_TRUNCATE_ERROR:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_SYNTAX_ERROR:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_UNAUTHORIZED:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_INVALID:
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_CONFIG_ERROR:
				// no frame body returned
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_UNAVAILABLE_EXCEPTION:
				$data = new \stdClass();
				$data->consistency = $this->parseShort($frameBody);
				$data->required = $this->parseInt($frameBody);
				$data->alive = $this->parseInt($frameBody);
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_WRITE_TIMEOUT:
				$data = new \stdClass();
				$data->consistency = $this->parseShort($frameBody);
				$data->received = $this->parseInt($frameBody);
				$data->blockFor = $this->parseInt($frameBody);
				$data->writeType = $this->parseString($frameBody);
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_READ_TIMEOUT:
				$data = new \stdClass();
				$data->consistency = $this->parseShort($frameBody);
				$data->received = $this->parseInt($frameBody);
				$data->blockFor = $this->parseInt($frameBody);
				$data->dataPresent = bin2hex(substr($frameBody, $this->_offset, 1));
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_ALREADY_EXISTS:
				$data = new \stdClass();
				$data->existingKeyspace = $this->parseString($frameBody);
				$data->existingTable = $this->parseString($frameBody);
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::ERROR_CODE_UNPREPARED:
				$data = new \stdClass();
				$data->unknownId = $this->parseShortBytes($frameBody);
				break;
		}
	
		$return = new \stdClass();
		$return->code = $processIntResult;
		$return->msg = $processStringResult;
		$return->body = $data;
		
		return $return;
	}
	
	/**
	 * Parse a CQL results frame.
	 * 
	 * @param string $frameBody
	 * @return stdClass
	 */
	public function parseResult($frameBody)
	{
		// get the result type
		$resultType = $this->parseInt($frameBody);
	
		$data = null;
		switch($resultType)
		{
			case \McFrazier\PhpBinaryCql\CqlConstants::RESULT_TYPE_VOID:
				// body is empty
				$data = new \stdClass();
				$data->status = 'OK';
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::RESULT_TYPE_ROWS:
				//$this->resetCounters();
				$rowMetadata = $this->parseRowMetadata($frameBody);
				$rowsCount = $this->parseInt($frameBody);
				$rowsContent = $this->parseRowsContent($frameBody, $rowsCount, $rowMetadata);
				
				$data = new \stdClass();
				$data->rowMetadata = $rowMetadata;
				$data->rowsCount = $rowsCount;
				$data->rowsContent = $rowsContent;
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::RESULT_TYPE_SCHEMA_CHANGE:
				$this->resetCounters();
				$changeKind = $this->parseInt($frameBody);

				// <change><keyspace><table>
				$changeType = $this->parseString($frameBody);
				$changedKeyspace = $this->parseString($frameBody);
				$changedTable = $this->parseString($frameBody);
				
				$data = new \stdClass();
				$data->changeType = $changeType;
				$data->affectedKeyspace = $changedKeyspace;
				$data->affectedTable = $changedTable;
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::RESULT_TYPE_SET_KEYSPACE:
				$this->resetCounters();
				$changeKind = $this->parseInt($frameBody);
				$setKeyspace = $this->parseString($frameBody);
				
				$data = new \stdClass();
				$data->UsingKeyspace = $setKeyspace;
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::RESULT_TYPE_PREPARED:
				//$this->resetCounters();
				$changeKind = $this->parseInt($frameBody);
				$queryId = $this->parseShortBytes($frameBody);
				$meta = $this->parseRowMetadata($frameBody);
				
				$data = new \stdClass();
				$data->queryId = $queryId;
				$data->metadata = $meta;
				break;
		}
		
		return $data;
	}
	
	/**
	 * Parse the binary result rows content.
	 * 
	 * @param string $frameBody
	 * @param integer $rowCount
	 * @param stdClass $rowMetaData
	 * @return array
	 */
	public function parseRowsContent($frameBody, $rowCount, $rowMetaData)
	{
		$rows = array();
		for($rowCounter=0; $rowCounter < $rowCount; $rowCounter++)
		{
			$cols = new \stdClass();
			for($colCounter=0; $colCounter < $rowMetaData->columnsCount; $colCounter++)
			{
				$optionId = $rowMetaData->columnSpec[$colCounter]->optionId;
				$optionValue = (isset($rowMetaData->columnSpec[$colCounter]->optionValue) ? $rowMetaData->columnSpec[$colCounter]->optionValue : NULL );
				$columnValue = $this->_parseBytesByColumnType($frameBody, $optionId, $optionValue);
				$columnName = $rowMetaData->columnSpec[$colCounter]->columnName;
				$cols->$columnName = $columnValue;
			}
			$rows[] = $cols;
		}
		
		return $rows;
	}
	
	/**
	 * Parse binary results row metadata.
	 * <flags><columns_count><global_table_spec>?<col_spec_1>...<col_spec_n>
	 * 
	 * @param string $frameBody
	 * @return stdClass
	 */
	public function parseRowMetadata($frameBody)
	{
		$flags = $this->parseInt($frameBody);
		$columnCount = $this->parseInt($frameBody);
		
		$keyspaceName = null;
		$tableName = null;
		$globalKeyspaceName = null;
		$globalTableName = null;
		
		// check the flags
		$flagStatus = $this->_checkRowMetadataFlags($flags);
		
		// logic for global_tables_spec flag
		if($flagStatus->global_tables_spec) {
			$globalKeyspaceName = $this->parseString($frameBody);
			$globalTableName = $this->parseString($frameBody);
		}
	
		// only do this if we have a column count greater than zero
		$columnSpec = array();
		if($columnCount > 0) {
			// check flag to see if we have a global table spec
	 		if(!$flagStatus->global_tables_spec) {
				$keyspaceName = $this->parseString($frameBody);
				$tableName = $this->parseString($frameBody);
	 		}

			for($x = 0; $x < $columnCount; $x++)
			{
				$columnName = $this->parseString($frameBody);
				$optionId = $this->parseShort($frameBody);
				
				$optionValue = null;
				switch($optionId)
				{
					case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_CUSTOM:
						$optionValue = $this->parseString($frameBody);
						break;
					case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_LIST:
					case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_SET:
						$short = $this->parseShort($frameBody);
						if($short == \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_CUSTOM) {
							$optionValue = new stdClass();
							$optionValue->id = $short;
							$optionValue->value = $this->parseString($frameBody);
						} else {
							$optionValue = $short;
						}
						break;
					case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_MAP:
						// @TODO will have to add how to handle custom column for maps later
						$optionValue = new \stdClass();
						$optionValue->keyType = $this->parseShort($frameBody);
						$optionValue->valueType = $this->parseShort($frameBody);
						break;
				}

				$cs = new \stdClass();
				$cs->columnName = $columnName;
				$cs->optionId = $optionId;
				if($optionValue){
					$cs->optionValue = $optionValue;
				}
				$columnSpec[$x] = $cs;
			}
		}
		
		$meta = new \stdClass();
		$meta->flags= $flags;
		$meta->columnsCount = $columnCount;
		$gts = new \stdClass();
		$gts->keyspace = $globalKeyspaceName;
		$gts->tableName = $globalTableName;
		$meta->globalTableSpec = $gts;
		$meta->columnSpec = $columnSpec;
		
		return $meta;
	}
	
	/**
	 * Parse binary short.
	 * A 2 bytes unsigned integer.
	 * 
	 * @param string $frameBody
	 * @return integer
	 */
	public function parseShort($frameBody)
	{
		$binShort = substr($frameBody, $this->_offset, 2);
		$decShort = unpack('n', $binShort);
		$this->_offset += 2; // increase the offset
		
		// log action
		$this->_actionLog[] = 'Parse short: '.bin2hex($binShort);
		
		return $decShort[1];
	}
	
	/**
	 * Parse binary int.
	 * A 4 bytes integer.
	 * 
	 * @param string $frameBody
	 * @return integer
	 */
	public function parseInt($frameBody)
	{

		$binInt = substr($frameBody, $this->_offset, 4);
		$decInt = unpack('N', $binInt);
		$this->_offset += 4; // increase the offset	
		
		// log action
		$this->_actionLog[] = 'Parse int: '.bin2hex($binInt);
		
		return $decInt[1];
	}
	
	/**
	 * Parse binary internet address (inet).
	 * An address (ip and port) to a node. It consists of one 
	 * [byte] n, that represents the address size, followed by n 
	 * [byte] representing the IP address (in practice n can only be 
	 * either 4 (IPv4) or 16 (IPv6)), following by one [int] representing the port.
	 * 
	 * @param string $frameBody
	 * @return string
	 */
	public function parseInet($frameBody)
	{
		$columnValue = NULL;
		
		// get ipaddress
		$hexColumnValue = $this->parseBytes($frameBody);
		
		// get port
		// @TODO have to figure out how to tell if a port
		// is present for the ipaddress getting an error now !!!
		//$port = $this->parseInt($frameBody);
		
		$ipParts = array();
		if(strlen($hexColumnValue) == 8) {
			$ipParts[] = hexdec(substr($hexColumnValue,0,2));
			$ipParts[] = hexdec(substr($hexColumnValue,2,2));
			$ipParts[] = hexdec(substr($hexColumnValue,4,2));
			$ipParts[] = hexdec(substr($hexColumnValue,6,2));
			$columnValue = implode('.',$ipParts);
		} else {
			$ipParts[] = substr($hexColumnValue,0,4);
			$ipParts[] = substr($hexColumnValue,4,4);
			$ipParts[] = substr($hexColumnValue,8,4);
			$ipParts[] = substr($hexColumnValue,12,4);
			$ipParts[] = substr($hexColumnValue,16,4);
			$ipParts[] = substr($hexColumnValue,20,4);
			$ipParts[] = substr($hexColumnValue,24,4);
			$ipParts[] = substr($hexColumnValue,28,4);
			$columnValue = implode(':',$ipParts);
		}
		
		return $columnValue;
	}
	
	/**
	 * Parse binary short bytes, this method can return a hex or binary
	 * representation of the bytes.
	 * 
	 * @param string $frameBody
	 * @param boolean $returnBinary
	 * @return string
	 */
	public function parseShortBytes($frameBody, $returnBinary = FALSE)
	{
		$short = $this->parseShort($frameBody);
		$returnString = null;
		if($short >= 0) {
			$binString = substr($frameBody, $this->_offset, $short);
 			if($returnBinary) {
 				$returnString = $binString;
 			} else {
 				$returnString = bin2hex($binString);
 			}
		}
		
		$this->_offset += $short;
		
		// log action
		$this->_actionLog[] = 'Parse bytes: '.bin2hex($binString);
		
		return $returnString;
	}
	
	/**
	 * Parse binary string.
	 * A [short] n, followed by n bytes representing an UTF-8 string.
	 * 
	 * @param string $frameBody
	 * @return string
	 */
	public function parseString($frameBody)
	{
		// get string length which is a short
		$short = $this->parseShort($frameBody);

		// get string hex values
		$hexString = bin2hex(substr($frameBody, $this->_offset, $short));
		$string = $this->_convertHexToAscii($hexString);
		
		// add length of string to internal offset
		$this->_offset += $short;
	
		return $string;
	}
	
	/**
	 * Parse binary string list.
	 * A [short] n, followed by n [string].
	 * 
	 * @param string $frameBody
	 * @return array
	 */
	public function parseStringList($frameBody)
	{
		// get string length which is a short
		$sizeOfStringList = $this->parseShort($frameBody);
		
		$stringList = array();
		for($x=0; $x<$sizeOfStringList ;$x++)
		{
			// get the string
			$string = $this->parseString($frameBody);
		
			// add element to list
			$stringList[] = $string;
		}
	
		return $stringList;
	}
	
	/**
	 * Parse binary string multimap.
	 * A [short] n, followed by n pair <k><v> where <k> is a 
	 * [string] and <v> is a [string list].
	 *            
	 * @param string $frameBody
	 * @return array
	 */
	public function parseStringMultimap($frameBody)
	{
		// get number of maps in multimap
		$sizeOfMultimap = $this->parseShort($frameBody);
	
		$stringMultimap = array();
		for($x=0; $x<$sizeOfMultimap ;$x++)
		{
			// get the key
			$key = self::parseString($frameBody);
			
			// get the value
			$processStringListResult = $this->parseStringList($frameBody);
			
			// add to string multimap
			$stringMultimap[$key] = $processStringListResult;
		}
	
		return $stringMultimap;
	}
	
	/**
	 * Parse a binary UUID.
	 * A 16 bytes long uuid.
	 * 
	 * @param string $frameBody
	 * @return string
	 */
	public function parseUuid($frameBody)
	{
		$int = $this->parseInt($frameBody);
		$binString = substr($frameBody, $this->_offset, $int);
		$this->_offset += $int;
		$hexString = bin2hex($binString);

		$uuidParts = array();
		
		$uuidParts[] = substr($hexString,0,8);
		$uuidParts[] = substr($hexString,8,4);
		$uuidParts[] = substr($hexString,12,4);
		$uuidParts[] = substr($hexString,16,4);
		$uuidParts[] = substr($hexString,20,12);
		
		return implode('-',$uuidParts);
	}
	
	/**
	 * Reset the byte counters.
	 */
	public function resetCounters()
	{
		$this->_byteCount = 0;
		$this->_offset = 0;
	}
	
	/**
	 * Check the rowmetadata flags.
	 * 
	 * @param integer $flags
	 * @return stdClass
	 */
	private function _checkRowMetadataFlags($flags)
	{
		$flagStatus = new \stdClass();
		$flagStatus->global_tables_spec = (bool)((integer)$flags & \McFrazier\PhpBinaryCql\CqlConstants::ROWS_METADATA_FLAG_GLOBAL_TABLES_SPEC);
		
		return $flagStatus;
	}
	
	/**
	 * Check the frame flags.
	 * 
	 * @param integer $flags
	 * @return stdClass
	 */
	private function _checkFrameFlags($flags)
	{
		$flagStatus = new \stdClass();
		$flagStatus->compression = (bool)((integer)$flags & \McFrazier\PhpBinaryCql\CqlConstants::FRAME_FLAG_COMPRESSION);
		$flagStatus->tracing = (bool)((integer)$flags & \McFrazier\PhpBinaryCql\CqlConstants::FRAME_FLAG_TRACING);
		
		return $flagStatus;
	}
	
	/**
	 * Convert a hexstring to an ascii string.
	 * 
	 * @param string $hexString
	 * @return string
	 */
	private function _convertHexToAscii($hexString)
	{
		$asciiString = '';
		for($i=0;$i<strlen($hexString);$i+=2)
		{
			//$asciiString .= chr(hexdec(substr($hexString,$i,2)));
			$int = hexdec(substr($hexString,$i,2));
			$asciiString .= mb_convert_encoding(pack('n', $int), 'UTF-8', 'UTF-16BE');
		}
		return $asciiString;
	}
	
	/**
	 * Convert an ascii string to a binary string.
	 * 
	 * @param stirng $asciiString
	 * @return string
	 */
	private function _convertAsciiToBinary($asciiString)
	{
		$hex = '';
		for ($i=0; $i < strlen($asciiString); $i++)
		{
			$hex .= dechex(ord($asciiString[$i]));
		}
		
		return pack('H*',$hex);
	}
	
	/**
	 * Convert a hex to a IEEE 754 32bit float.
	 * 
	 * @param string $hexString
	 * @return string
	 */
	private function _convertHexTo32Float($hexString)
	{
		$v = hexdec($strHex);
		$x = ($v & ((1 << 23) - 1)) + (1 << 23) * ($v >> 31 | 1);
		$exp = ($v >> 23 & 0xFF) - 127;
		return (string)$x * pow(2, $exp - 23);
	}
	
	private function _convertHexToSignedInteger($hexString)
	{
		// ignore non hex characters
		//$hex = preg_replace('/[^0-9A-Fa-f]/', '', $hex);
		 
		// converted decimal value:
		$dec = hexdec($hexString);
	 
		// maximum decimal value based on length of hex + 1:
		//   number of bits in hex number is 8 bits for each 2 hex -> max = 2^n
		//   use 'pow(2,n)' since '1 << n' is only for integers and therefore limited to integer size.
		$max = pow(2, 4 * (strlen($hexString) + (strlen($hexString) % 2)));
		 
		// complement = maximum - converted hex:
		$_dec = $max - $dec;
		 
		// if dec value is larger than its complement we have a negative value (first bit is set)
		return (string)$dec > $_dec ? -$_dec : $dec;
	}
	
	/**
	 * Parse column value bytes.
	 * 
	 * @param string $frameBody
	 * @param integer $optionId
	 * @param integer | stdClass $optionValue
	 * @return string
	 */
	private function _parseBytesByColumnType($frameBody, $optionId, $optionValue)
	{
		$columnValue = NULL;
		switch($optionId)
		{
			// all of the following will be returned as strings and the caller will
			// have to cast to to correct type...
				
			// ascii
			// @TODO look at this to make sure it handle UTF-8 correctly
			// will have to create some tests to check this
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_ASCII:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_VARCHAR:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_TEXT:
				$hexColumnValue = $this->parseBytes($frameBody);
				$columnValue = $this->_convertHexToAscii($hexColumnValue);
				break;
					
				// this will return the bytes in binary format
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BLOB:
				$columnValue = $this->parseBytes($frameBody, TRUE);
				break;
		
				// this will return a PHP Boolean tpye
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BOOLEAN:
				$hexColumnValue = $this->parseBytes($frameBody);
				if($hexColumnValue == 01) {
					$columnValue = TRUE;
				} else {
					$columnValue = FALSE;
				}
				break;
		
				// UUID
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_UUID:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_TIMEUUID:
				$columnValue = $this->parseUuid($frameBody);
				break;
		
				// ints
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_COUNTER:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BIGINT:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_VARINT:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_TIMESTAMP:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_INT:
				$hexColumnValue = $this->parseBytes($frameBody);
				$msb = (integer)substr($hexColumnValue, 0,1);
				$columnValue = number_format((integer)hexdec($hexColumnValue), 0, '', '');

				// yes, overkill, but I want these to be correct.
				if($msb > 7) {
					$columnValue = -abs($columnValue);
				} else {
					$columnValue = abs($columnValue);
				}
				break;
				
				// real numbers
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_DOUBLE:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_DECIMAL:
				$binColumnValue = $this->parseBytes($frameBody, TRUE);
				$columnValue = unpack("d", $binColumnValue); // unpack returns an array
				$columnValue = $columnValue[1];
				break;
				
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_FLOAT:
				$binColumnValue = $this->parseBytes($frameBody, TRUE);
				$columnValue = unpack("f", $binColumnValue); // unpack returns an array
				$columnValue = $columnValue[1];
				break;
		
				// inet
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_INET:
				$columnValue = $this->parseInet($frameBody);
				break;
		
				// Collection: LIST and SET
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_LIST:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_SET:
				$int = $this->parseInt($frameBody); // list column type not for sure why it is here again, but anyway...
				$short = $this->parseShort($frameBody); // count in list
				for($x = 0; $x < $short; $x++)
				{
					$columnValue[] = $this->_parseShortBytesByColumnType($frameBody, $optionValue, NULL);
				}
				break;
				
				// Collection: MAP
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_MAP:
				$int = $this->parseInt($frameBody); // list column type not for sure why it is here again, but anyway...
				$short = $this->parseShort($frameBody); // size of map
				for($x = 0; $x < $short; $x++)
				{
					$key = $this->_parseShortBytesByColumnType($frameBody, $optionValue->keyType, NULL);
					$val = $this->_parseShortBytesByColumnType($frameBody, $optionValue->valueType, NULL);
					$columnValue[$key] = $val;
				}
				break;
		}
		
		return $columnValue;
	}
	
	/**
	 * Parse column value short bytes.
	 * 
	 * @param string $frameBody
	 * @param integer $optionId
	 * @param integer | stdClass $optionValue
	 * @return string
	 */
	private function _parseShortBytesByColumnType($frameBody, $optionId, $optionValue)
	{
		$columnValue = NULL;
		switch($optionId)
		{
			// all of the following will be returned as strings and the caller will
			// have to cast to to correct type...
	
			// ascii
			// @TODO look at this to make sure it handle UTF-8 correctly
			// will have to create some tests to check this
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_ASCII:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_VARCHAR:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_TEXT:
				$hexColumnValue = $this->parseShortBytes($frameBody);
				$columnValue = $this->_convertHexToAscii($hexColumnValue);
				break;
					
				// this will return the bytes in binary format
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BLOB:
				$columnValue = $this->parseShortBytes($frameBody, TRUE);
				break;
	
				// this will return a PHP Boolean tpye
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BOOLEAN:
				$hexColumnValue = $this->parseBytes($frameBody);
				if($hexColumnValue == 01) {
					$columnValue = TRUE;
				} else {
					$columnValue = FALSE;
				}
				break;
	
				// UUID
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_UUID:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_TIMEUUID:
				$columnValue = $this->parseUuid($frameBody);
				break;
	
				// ints
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_COUNTER:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_BIGINT:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_DOUBLE:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_INT:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_VARINT:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_TIMESTAMP:
				$hexColumnValue = $this->parseShortBytes($frameBody);
				$columnValue = number_format(hexdec($hexColumnValue), 0, '', '');
				break;
	
				// floats
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_DECIMAL:
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_FLOAT:
				$hexColumnValue = $this->parseShortBytes($frameBody);
				$columnValue = $this->_convertHexTo32Float($hexString);
				break;
	
				// inet
			case \McFrazier\PhpBinaryCql\CqlConstants::COLUMN_TYPE_OPTION_INET:
				$columnValue = $this->parseInet($frameBody);
				break;
		}
	
		return $columnValue;
	}
}