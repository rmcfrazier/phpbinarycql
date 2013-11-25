<?php
/**
 * PhpBinaryCql
 *
 * @link https://github.com/rmcfrazier/phpbinarycql
 * @copyright Copyright (c) 2013 Robert McFrazier
 * @license http://opensource.org/licenses/MIT
 */
namespace McFrazier\PhpBinaryCql;

use \McFrazier\PhpBinaryCql\CqlRequest;
use \McFrazier\PhpBinaryCql\CqlResponse;
use \McFrazier\PhpBinaryCql\CqlProtocol;
use \McFrazier\PhpBinaryCql\CqlFrame;
use \McFrazier\PhpBinaryCql\CqlConstants;

/**
 * Class that provides the CQL client.
 */
class CqlClient
{
	/**
	 * CQL Host
	 * @var string
	 */
	private $_cqlHost = NULL;
	
	/**
	 * CQL Port
	 * @var string
	 */
	private $_cqlPort = NULL; // DataStax default port is 9042
	
	/**
	 * Handle for the CQL connection.
	 * @var resource
	 */
	private $_resourceHandle = NULL;
	
	/**
	 * Socket timeout in seconds, defaulting to 5 seconds.
	 * @var integer
	 */
	private $_socketTimeout = 5; // number of seconds before socket times out defaulting to 5 seconds.
	
	/**
	 * Size of the socket read buffer in bytes, defaulting to 8192.
	 * @var integer
	 */
	private $_socketReadBufferSize = 8192; // in bytes
	
	/**
	 * Flag the indicates whether the CQL connection is ready.
	 * @var boolean
	 */
	private $_connectionReady = FALSE;
	
	/**
	 * Array of options that are used to initialize the connection.
	 * @var array
	 */
	private $_startupOptions = array();
	
	/**
	 * Container for the CQL protocol object.
	 * @var NULL | McFrazier\PhpBinaryCql\CqlProtocol
	 */
	private $_protocol = NULL;
	
	/**
	 * Flag to indicate if we shoud configure the connection to trace this CQL cal.
	 * @var boolean
	 */
	private $_tracingFlag = FALSE;
	
	/**
	 * Location of the directory to store transmitted and recieved binary frames.  Setting this
	 * will cause the client to capture the frames.
	 * @var string
	 */
	private $_captureFrameDir = NULL;
	
	/**
	 * Sets the CQL host and port, and creates the protocol object.
	 * 
	 * @param unknown $host
	 * @param unknown $port
	 */
	public function __construct($host, $port)
	{
		if(!empty($host)) {
			$this->_cqlHost = $host;
		} else {
			exit('Please supply the CQL host');
		}
	
		if(!empty($port)) {
			$this->_cqlPort = (string)$port;
		} else {
			exit('Please supply the CQL port');
		}
		
		$this->_protocol = new \McFrazier\PhpBinaryCql\CqlProtocol();
		
		// lets register a shutdown to help when trying to debug
		register_shutdown_function(array($this, 'shutdown'));
		
	}
	
	/**
	 * Adds an element to the startupOptions array.
	 * 
	 * @param string $optionName
	 * @param string $optionValue
	 */
	public function addStartupOption($optionName, $optionValue)
	{
		$this->_startupOptions[$optionName] = $optionValue;
	}
	
	/**
	 * Setter for the _captureFrameDir property.
	 * 
	 * @param string $capturedFrameDirectory
	 */
	public function setCapturedFramesDirectory($capturedFrameDirectory)
	{
		$this->_captureFrameDir = $capturedFrameDirectory;
	}
	
	// @TODO will implement prepared querys later
// 	public function prepareQuery($query)
// 	{
// 		$req = new \McFrazier\PhpBinaryCql\CqlRequest($this->_protocol);
// 		$req->setOpcode(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_PREPARE);
// 		$req->prepareQuery($query);
		
// 		$resp = $this->send($req, $this->_protocol);
		
// 		return $resp;
// 	}

	/**
	 * This method is for the tests to be able to read binary CQL 
	 * frames from the filesystem, instead of a socket.
	 * 
	 * @param resource $handle
	 */
	public function setResourceHandle($handle)
	{
		$this->_resourceHandle = $handle;
	}
	
	/**
	 * Setter for the _socketTimeout property.
	 *  
	 * @param integer $timeout
	 */
	public function setSocketTimeout($timeout)
	{
		$this->_socketTimeout = $timeout;
	}
	
	/**
	 * Setter for the _socketReadBufferSize.
	 * 
	 * @param integer $bufferSize
	 */
	public function setSocketReadBufferSize($bufferSize)
	{
		$this->_socketReadBufferSize = $bufferSize;
	}
	
	/**
	 * Setter for the _tracingFlag property.
	 * 
	 * @param boolean $flag
	 */
	public function setTracingFlag($flag)
	{
		$this->_tracingFlag = $flag;
	}
	
	// @TODO implement authentication later
// 	public function addCredentials($username, $password)
// 	{
// 		$data = new \stdClass();
// 		$data->username = $username;
// 		$data->password = $password;
// 		$this->_credentials[] = $data;
// 	}

	
	/**
	 * Send a CQL options request.
	 * 
	 * @return McFrazier\PhpBinaryCql\CqlRequest
	 */
	public function getSupportedStartupOptions()
	{
		// get CQL supported options
		$requestObj = new \McFrazier\PhpBinaryCql\CqlRequest();
		$requestObj->setOpcode(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_OPTIONS);
			
		// we are telling the send method to skip the connection initializing because
		// this call does not need it.
		$responseObj = $this->send($requestObj, $this->_protocol , FALSE); // FALSE = does not initialize the connection
													   					  
		return $responseObj;
	}
	
	/**
	 * Builds a frame from a CQLRequest object and transmittes that frame, returning
	 * a CQLResponse object.
	 * 
	 * @param McFrazier\PhpBinaryCql\CqlRequest $requestObj
	 * @param boolean $initializeConnection (default is TRUE)
	 * @return McFrazier\PhpBinaryCql\CqlResponse
	 */
	public function send($requestObj, $initializeConnection = TRUE)
	{
		// check to see if we need to initialize the connection
		if($initializeConnection && $this->_connectionReady == FALSE) {
			$this->_initializeConnection();
		}
	
		$reqFrame = new \McFrazier\PhpBinaryCql\CqlFrame($this->_protocol);
	
		// version
		$version = $requestObj->getVersion();
		if($version) {
			$reqFrame->setVersion($version);
		} else {
			$reqFrame->setVersion(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_REQUEST_VERSION);
		}
	
		// opcode
		$reqFrame->setOpcode($requestObj->getOpcode());
	
		// flag
		if($requestObj->getFlag()) {
			$reqFrame->setFlag($requestObj->getFlag());
		} else {
			$flags = \McFrazier\PhpBinaryCql\CqlConstants::FRAME_FLAG_EMPTY;
			if(key_exists('COMPRESSION', $this->_startupOptions)) {
				$flags |= \McFrazier\PhpBinaryCql\CqlConstants::FRAME_FLAG_COMPRESSION;
			}

			if($this->_tracingFlag) {
				$flags |= \McFrazier\PhpBinaryCql\CqlConstants::FRAME_FLAG_TRACING;
			}
			$reqFrame->setFlag($flags);

		}
		
		// stream
		if($requestObj->getStream()) {
			$reqFrame->setStream($requestObj->getStream());
		} else {
			$reqFrame->setStream(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_STREAM_DEFAULT);
		}
	
		// body
		if($requestObj->getBody()) {
			$reqFrame->setBody($requestObj->getBody());
		}
		
		// send the frame
		$this->_sendFrame($reqFrame);
		
		// return a response object
		return new \McFrazier\PhpBinaryCql\CqlResponse($this->_receiveFrame(), $this->_protocol);
	}
	
	
	/**
	 * Open CQL socket.
	 * 
	 * @param string $cqlHost
	 * @param string $cqlPort
	 * @param integer $socketTimeout
	 * 
	 * @return resource $resourceHandle
	 */
	public function openCqlSocket($cqlHost, $cqlPort ,$socketTimeout)
	{
		$resourceHandle = stream_socket_client('tcp://'.$cqlHost.':'.$cqlPort, $errno, $errorMessage, $socketTimeout);
	
		if ($resourceHandle === FALSE) {
			exit('Failed to connect to CQL host: '.$cqlHost.' and CQL port: '.$cqlPort.' Reason: '.$errorMessage);
		}
		
		return $resourceHandle;
	}
	
	/**
	 * Create and sends CQLRequest object for making a CQL query. 
	 * 
	 * @param string $queryText
	 * @param integer $queryConsistency
	 * @return \McFrazier\PhpBinaryCql\CqlResponse
	 */
	public function query($queryText, $queryConsistency)
	{
		$req = new \McFrazier\PhpBinaryCql\CqlRequest($this->_protocol);
		$req->setOpcode(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_QUERY);
		$req->query($queryText, $queryConsistency);
		
		$resp = $this->send($req, $this->_protocol);
		
		return $resp;
	}
	
	/**
	 * Return a string with a leading and trailing single quote.
	 * 
	 * @param string $string
	 * @return string
	 */
	public function qq($string)
	{
		return '\''.$string.'\'';
	}
	
	/**
	 * When object is destoryed, check to see if we have an error and print debug info
	 * 
	 * This is still a work in progress.
	 */
	public function shutdown()
	{
		$err = error_get_last();
		if(!empty($err))
		{
			var_dump($err);
		}
	}
	
	/**
	 * Initialize the CQL connection.
	 * Creates a frame with the configured startup options, transmits and receives frame
	 * based on opcode of response frame set the connectionReady property.
	 * 
	 * @return boolean
	 */
	private function _initializeConnection()
	{
		// build frame for initialize call
		$frame = new \McFrazier\PhpBinaryCql\CqlFrame();
		$frame->setOpcode(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_STARTUP);
		$frame->setVersion(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_REQUEST_VERSION);
		$frame->setFlag(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_FLAG_EMPTY);
		$frame->setStream(\McFrazier\PhpBinaryCql\CqlConstants::FRAME_STREAM_DEFAULT);
		
		// build frame body from startup options
		$frameBody = $this->_protocol->generateStringmap($this->_startupOptions, 0);
		$frame->setBody($frameBody);
		
		// send frame
		$this->_sendFrame($frame); //
		
		// get response from CQL
		$respFrame = $this->_receiveFrame();
		
		// create response object.
		$responseObj = new \McFrazier\PhpBinaryCql\CqlResponse($respFrame, $this->_protocol);
	
		// we have sent the startup frame and we should see a ready frame in response
		// ready frame is opcode 0x02
		switch($responseObj->getOpcode())
		{
			case \McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_READY:
				$this->_connectionReady = TRUE;
				break;
			case \McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_ERROR:
				// Need to figure out what to do if a connection startup returns an error
				break;
 			// case \McFrazier\PhpBinaryCql\CqlConstants::FRAME_OPCODE_AUTHENTICATE:
		}
	
		return $this->_connectionReady;
	}
	
	/**
	 * Writes captured frames to the directory specified by the $dir paramater.
	 * Files are named by [unix_timestamp_seconds].[unix_timestamp_milliseconds]_[opcode]_[version]_[flag]_[stream].cqlframe
	 * 
	 * @param McFrazier\PhpBinaryCql\CqlFrame $frame
	 * @param string $dir
	 */
	private function _captureFrame($frame, $dir)
	{
		$date_array = explode(" ",microtime());
		$fileName = ($date_array[0] + $date_array[1]).'_'.$frame->getVersion().'_'.$frame->getFlag().'_'.$frame->getStream().'_'.$frame->getOpcode().'.cqlframe';
		
		// check for a directory seperator at the end of the string if it is not there add it
		$lastChar = substr($dir, -1);
		if($lastChar != DIRECTORY_SEPARATOR) {
			$dir .= DIRECTORY_SEPARATOR;
		}
		
		$handle = fopen($dir.$fileName, 'wb');
		fwrite($handle, $this->_protocol->generateBinaryFrame($frame));
		fclose($handle);
	}
	
	/**
	 * Writes CQL binary frame data to socket handle.
	 * 
	 * @param string $binaryData
	 */
	private function _socketWrite($binaryData)
	{
		if(!$this->_connectionReady) {
			$this->_resourceHandle = $this->openCqlSocket($this->_cqlHost, $this->_cqlPort, $this->_socketTimeout);
		}
		// @TODO add error checks
		stream_set_write_buffer($this->_resourceHandle ,0);
	
		// @TODO add error checks
		fwrite($this->_resourceHandle, $binaryData);
	}
	
	/**
	 * Reads binary frame data from socket handle and creates a frame object.
	 * 
	 * @return \McFrazier\PhpBinaryCql\CqlFrame
	 */
	private function _socketRead()
	{
		// don't buffer socket reads
		stream_set_read_buffer($this->_resourceHandle, 0);
	
		// The frame header is 8 bytes, after unpacking into the hex representation
		// we have 16 bytes string... frame header is broken down in to the following parts
		//
		// 0-1 : version
		// 2-3 : flags
		// 4-5 : streamId
		// 6-7 : opcode
		// 8-16 : body length
		//
		// We will get that first
		$frameHeader = fread($this->_resourceHandle, 8);
	
		$frameHeaderObj = $this->_protocol->parseFrameHeader($frameHeader);
		
		$frame = new \McFrazier\PhpBinaryCql\CqlFrame();
		$frame->setVersion($frameHeaderObj->version);
		$frame->setFlag($frameHeaderObj->flag);
		$frame->setStream($frameHeaderObj->stream);
		$frame->setOpcode($frameHeaderObj->opcode);
		$frame->setLength($frameHeaderObj->bodyLength);
		
		// now we get the body, we will read in chucks of 8192 bytes which is the default
		$readIterations = ceil($frameHeaderObj->bodyLength / $this->_socketReadBufferSize);
	
		$frameBodyBinary = NULL;
		for($x = 0; $x < $readIterations; $x++)
		{
			$frameBodyBinary .= fread($this->_resourceHandle, $this->_socketReadBufferSize);
		}
	
		$frame->setBody($frameBodyBinary);
	
		return $frame;
	}
	
	/**
	 * Get binary data from a frame object and transmit it.
	 * 
	 * @param \McFrazier\PhpBinaryCql\CqlFrame $frame
	 */
	private function _sendFrame($frame)
	{
		// check to see if we should capture this frame
		if($this->_captureFrameDir) {
			$this->_captureFrame($frame, $this->_captureFrameDir);
		}
		
		$this->_socketWrite($this->_protocol->generateBinaryFrame($frame));
	}
	
	/**
	 * Read the frame data from socket and return frame.
	 * 
	 * @return \McFrazier\PhpBinaryCql\CqlFrame
	 */
	private function _receiveFrame()
	{
		// socketRead returns a frame object
		$frame = $this->_socketRead();

		// check to see if we should capture this frame
		if($this->_captureFrameDir) {
			$this->_captureFrame($frame, $this->_captureFrameDir);
		}
	
		return $frame;
	}
}