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
 * Class for a CQL binary protocol frame.
 */
class CqlFrame
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
	 * Frame body in binary.
	 * @var string
	 */
	private $_body = NULL;
	
	/**
	 * Frame body length.
	 * @var integer
	 */
	private $_bodyLength = NULL;
	
	/**
	 * Frame header.
	 * @var string
	 */
	private $_header = NULL;
	
	/**
	 * Constructor for frame object, noop.
	 */
	public function __construct()
	{
		// do nothing, for now...
	}
	
	/**
	 * Getter for _version property.
	 *
	 * @return integer
	 */
	public function getVersion()
	{
		return $this->_version;
	}
	
	/**
	 * Setter for _version property.
	 *
	 * @param integer $version
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
	public function getFlag()
	{
		return $this->_flag;
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
	 * Getter for _stream property.
	 *
	 * @return string
	 */
	public function getStream()
	{
		return $this->_stream;
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
	 * Getter for _opcode property.
	 *
	 * @return string
	 */
	public function getOpcode()
	{
		return $this->_opcode;
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
	 * Getter for _bodyLength property.
	 * 
	 * @return integer
	 */
	public function getLength()
	{
		return $this->_bodyLength;
	}
	
	/**
	 * Setter for _bodyLength property.
	 * 
	 * @param integer $length
	 */
	public function setLength($length)
	{
		$this->_bodyLength = $length;
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
	 * Setter for _body property.
	 *
	 * @param string $body
	 */
	public function setBody($body)
	{
		$this->_body = $body;
	}
}