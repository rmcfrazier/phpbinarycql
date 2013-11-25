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
 * Class for a CQL response.
 */
class CqlResponse
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
	 * Flag to indicate if the response contains a CQL error frame.
	 * @var boolean
	 */
	private $_isError = FALSE;
	
	/**
	 * CQL error code.
	 * @var integer
	 */
	private $_errorCode = NULL;
	
	/**
	 * CQL error message.
	 * @var string
	 */
	private $_errorMessage = NULL;
	
	/**
	 * Frame body in binary.
	 * @var string
	 */
	private $_body = NULL;
	
	/**
	 * Frame header in binary.
	 * @var string
	 */
	private $_header = NULL;
	
	/**
	 * Data payload of the CQL query.
	 * @var mixed
	 */
	private $_data = NULL;
	
	/**
	 * Inject frame and procotol objects.
	 * 
	 * @param McFrazier\PhpBinaryCql\CqlFrame $frame
	 * @param NULL | McFrazier\PhpBinaryCql\CqlProtocol $protocol
	 */
	public function __construct($frame, $protocol = NULL)
	{
		// set verison
		$this->_version = $frame->getVersion();

		// set flag
		$this->_flag = $frame->getFlag();
	
		// set stream
		$this->_stream = $frame->getStream();
	
		// set opcode
		$this->_opcode = $frame->getOpcode();
		
		// set bin body
		$this->_body = $frame->getBody();
	
		// parse the framebody
		$this->_data = $protocol->parseBinaryFrame($frame);
	
		// check if we have an error response
		if((integer)$this->_opcode === (integer)\McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_ERROR) {
			
			// we have an error set the error flag to true
			$this->_isError = TRUE;
				
			// set the error code
			$this->_errorCode = $this->_data->code;
				
			// set the error message
			$this->_errorMessage = $this->_data->msg;
		}
		
		$protocol->resetCounters();
	}
	
	/**
	 * Getter for _data property.
	 * 
	 * @return mixed
	 */
	public function getData()
	{
		return $this->_data;
	}
	
	/**
	 * Getter for _isError property.
	 * 
	 * @return boolean
	 */
	public function isError()
	{
		return $this->_isError;
	}
	
	/**
	 * Getter for _errorCode property.
	 * 
	 * @return integer
	 */
	public function getErrorCode()
	{
		return $this->_errorCode;
	}
	
	/**
	 * Getter for _errorMessage property.
	 * 
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->_errorMessage;
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
	 * Getter for _flag property.
	 * 
	 * @return integer
	 */
	public function getFlag()
	{
		return $this->_flag;
	}
	
	/**
	 * Getter for _stream property.
	 * 
	 * @return integer
	 */
	public function getStream()
	{
		return $this->_stream;
	}
	
	/**
	 * Getter for _opcode property.
	 * 
	 * @return integer
	 */
	public function getOpcode()
	{
		return $this->_opcode;
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
}