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
 * Class for a CQL request.
 */
class CqlRequest
{
	/**
	 * Frame header - Version
	 * @var string
	 */
	private $_version = NULL;
	
	/**
	 * Frame header - Flag
	 * @var string
	 */
	private $_flag = NULL;
	
	/**
	 * Frame header - Stream Id
	 * @var string
	 */
	private $_stream = NULL;
	
	/**
	 * Frame header - OpCode
	 * @var string
	 */
	private $_opcode = NULL;
	
	/**
	 * Binary frame body.
	 * @var string
	 */
	private $_body = NULL;
	
	/**
	 * Container for CQLProtocol object
	 * @var NULL | McFrazier\PhpBinaryCql\CqlProtocol
	 */
	private $_protocol = NULL;
	
	/**
	 * Inject protocol object.
	 * 
	 * @param McFrazier\PhpBinaryCql\CqlProtocol $protocol
	 */
	public function __construct($protocol = NULL)
	{
		if($protocol) {
			$this->_protocol = $protocol;
		}
	}
	
	/**
	 * Setter for _version property.
	 * 
	 * @param string $version
	 */
	public function setVersion($version)
	{
		$this->_version = (string)$version;
	}
	
	/**
	 * Getter for _version property.
	 * 
	 * @return string
	 */
	public function getVersion()
	{
		return $this->_version;
	}
	
	/**
	 * Setter for _flag property.
	 * 
	 * @param string $flag
	 */
	public function setFlag($flag)
	{
		$this->_flag = (string)$flag;
	}
	
	/**
	 * Getter for _flag property.
	 * 
	 * @return string
	 */
	public function getFlag()
	{
		return $this->_flag;
	}
	
	/**
	 * Setter for _stream property.
	 * 
	 * @param unknown $stream
	 */
	public function setStream($stream)
	{
		$this->_stream = (string)$stream;
	}
	
	/**
	 * Getter for _stream property.
	 * 
	 * @return string
	 */
	public function getStream()
	{
		return $this->_stream;
	}
	
	/**
	 * Setter for _opcode property.
	 * 
	 * @param string $opcode
	 */
	public function setOpcode($opcode)
	{
		$this->_opcode = (string)$opcode;
	}
	
	/**
	 * Getter for _opcode property.
	 * 
	 * @return string
	 */
	public function getOpcode()
	{
		return $this->_opcode;
	}
	
	/**
	 * Setter for _body property.
	 * 
	 * @param string $body
	 */
	public function setBody($body)
	{
		$this->_body = $body;
	}
	
	/**
	 * Getter for _body property.
	 * 
	 * @return string
	 */
	public function getBody()
	{
		return $this->_body;
	}
	
	/**
	 * Builds frame body from query text and query consistency.
	 * 
	 * @param string $query
	 * @param integer $queryConsistency
	 */
	public function query($query, $queryConsistency)
	{
		$this->_protocol->resetCounters();
		$binaryLongString = $this->_protocol->generateLongString($query);
		$this->_protocol->resetCounters();
		$binaryShort = $this->_protocol->generateShort($queryConsistency);     
		$this->_body = $binaryLongString.$binaryShort;
	}
	
	/**
	 * @TODO will implement this later
	 * Build frame body from prepare query text.
	 * @param string $query
	 */
// 	public function prepareQuery($query)
// 	{
// 		$this->_protocol->resetCounters();
// 		$binaryLongString = $this->_protocol->generateLongString($query);
// 		$this->_body = $binaryLongString;
// 	}
}