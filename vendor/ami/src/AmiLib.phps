<?php
/* 
 *  Copyright (c) 22/03/2008, Carlos Cesario <carloscesario@gmail.com>
 *  All rights reserved.
 * 
 *  Redistribution and use in source and binary forms, with or without modification,
 *  are permitted provided that the following conditions are met:
 * 
 *      * Redistributions of source code must retain the above copyright notice,
 *        this list of conditions and the following disclaimer.
 *      * Redistributions in binary form must reproduce the above copyright notice,
 *        this list of conditions and the following disclaimer in the documentation
 *        and/or other materials provided with the distribution.
 *      * Neither the name of the DagMoller nor the names of its contributors
 *        may be used to endorse or promote products derived from this software
 *        without specific prior written permission.
 * 
 *  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 *  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 *  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 *  IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 *  INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 *  BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 *  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 *  LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 *  OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 *  OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */

/*
 * Class to work with AMI in Asterisk using PHP 5.x
 *
 * Details:
 *
 * You need to edit your Asterisk configuration files to enable the following
 *
 * In manager.conf create the manager user
 *
 * Asterisk-1.4.x
 *
 * <code>
 * [admin]
 * secret = test
 * read = system,call,log,verbose,command,agent,user,config
 * write = system,call,log,verbose,command,agent,user,config
 * </code>
 *
 *
 * Asterisk-1.6.x
 *
 * <code>
 * [admin]
 * secret = test
 * read=system,call,log,verbose,agent,user,config,dtmf,reporting,cdr,dialplan
 * write=system,call,agent,user,config,command,reporting,originate
 * </code>
 *
 *
 * @since 22/03/2008
 * @modified 22/04/2010
 * @author Carlos Alberto Cesario <carloscesario@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 * @access public
 * @version 0.3.2
*/
class AmiLib {
	/**
	 * Socket stream
	 *
	 * @var object $_socket
	 * @access public
	*/
	private $_socket = NULL;

	/**
	 * Asterisk manager server address
	 *
	 * @var string $_server
	 * @access private
	*/
	private $_server;

	/**
	 * Asterisk manager server port
	 *
	 * @var integer $_port
	 * @access private
	*/
	private $_port;

	/**
	 * Asterisk manager user
	 *
	 * @var string $_username
	 * @access private
	*/
	private $_username;

	/**
	 * Asterisk manager secret
	 *
	 * @var string $_password
	 * @access private
	*/
	private $_password;

	/**
	 * Manager authentication type
	 * plaintext or md5
	 *
	 * @var string $_authtype
	 * @access private
	*/
	private $_authtype;

	/**
	 * Enable or disable debug commands
	 *
	 * Default: Disabled
	 *
	 * @var boolean $_debug
	 * @access private
	*/
	private $_debug;

	/**
	 * Enable or disable log commands
	 *
	 * Default: Disabled
	 *
	 * @var boolean $_log
	 * @access private
	*/
	private $_log;

	/**
	 * Log filename
	 *
	 * Default: './ami.log'
	 *
	 * @var string $_logfile
	 * @access private
	*/
	private $_logfile = './ami.log';

	/**
	 * Event Handlers
	 *
	 * @var array
	 * @access private
	*/
	private $_event_handlers;


	/**
	 * AmiLib::getSocket()
	 *
	 * Return current socket or NULL value
	 *
	 * @return object
	 * @access public
	*/
	public function getSocket() {
		return $this->_socket;
	}

	/**
	 * AmiLib::setSocket()
	 *
	 * Define the socket stream
	 *
	 * @return object
	 * @access public
	*/
	private function setSocket($socket) {
		$this->_socket = $socket;
	}

	/**
	 * AmiLib::getServer()
	 *
	 * Return server address connection  (Asterisk Server)
	 *
	 * @return string
	 * @access public
	*/
	public function getServer() {
		return $this->_server;
	}

	/**
	 * AmiLib::setServer()
	 *
	 * Define server address connection  (Asterisk Server)
	 *
	 * @param string $server
	 * @access private
	*/
	private function setServer($server) {
		$this->_server = $server;
	}

	/**
	 * AmiLib::getPort()
	 *
	 * Return current port connection
	 *
	 * @return integer
	 * @access public
	*/
	public function getPort() {
		return $this->_port;
	}

	/**
	 * AmiLib::setPort()
	 *
	 * Define port connection
	 *
	 * @param integer $port
	 * @access private
	*/
	private function setPort($port) {
		$this->_port = $port;
	}

	/**
	 * AmiLib::getUsername()
	 *
	 * Return current username manager
	 *
	 * @return string
	 * @access public
	*/
	public function getUsername() {
		return $this->_username;
	}

	/**
	 * AmiLib::setUsername()
	 *
	 * Define username manager
	 *
	 * @param string $username
	 * @access private
	*/
	private function setUsername($username) {
		$this->_username = $username;
	}

	/**
	 * AmiLib::getPassword()
	 *
	 * Return current password manager
	 *
	 * @return string
	 * @access public
	*/
	public function getPassword() {
		return $this->_password;
	}

	/**
	 * AmiLib::setPassword
	 *
	 * Define password manager
	 *
	 * @param string $password
	 * @access private
	*/
	private function setPassword($password) {
		$this->_password = $password;
	}

	/**
	 * AmiLib::getAuthtype()
	 *
	 * Return current authentication type
	 *
	 * @return string
	 * @access public
	*/
	public function getAuthtype() {
		return $this->_authtype;
	}

	/**
	 * AmiLib::setAuthtype()
	 *
	 * Define authentication type
	 *
	 * @param string $authtype
	 * @access private
	*/
	private function setAuthtype($authtype) {
		$this->_authtype = $authtype;
	}

	/**
	 * AmiLib::getDebug()
	 *
	 * Return current debug value
	 *
	 * @return bool
	 * @access public
	*/
	public function getDebug() {
		return $this->_debug;
	}

	/**
	 * AmiLib::setDebug()
	 *
	 * Define debug value
	 *
	 * @param bool
	 * @access private
	*/
	private function setDebug($debug) {
		$this->_debug = $debug;
	}

	/**
	 * AmiLib::getLog()
	 *
	 * Return current log value
	 *
	 * @return bool
	 * @access public
	*/
	public function getLog() {
		return $this->_log;
	}

	/**
	 * AmiLib::setLog()
	 *
	 * Define log value
	 *
	 * @param bool
	 * @access private
	*/
	private function setLog($log) {
		$this->_log = $log;
	}

	/**
	 * AmiLib::getLogFile()
	 *
	 * Return current log filename value
	 *
	 * @return string
	 * @access public
	*/
	public function getLogFile() {
		return $this->_logfile;
	}

	/**
	 * AmiLib::setLogFile()
	 *
	 * Define log filename value
	 *
	 * @param string
	 * @access private
	*/
	private function setLogFile($logfile) {
		$this->_logfile = $logfile;
	}

	/**
	 * AmiLib::getEventHandlers()
	 * Return event handlers
	 *
	 * @return array
	 * @access public
	*/
	public function getEventHandlers($event) {
		echo "A variaevel event vale $event\n";
		return $this->_event_handlers[$event];
	}

	/**
	 * AmiLib::setEventHandlers()
	 *
	 * Define event handlers value
	 *
	 * @param array
	 * @access private
	*/
	private function setEventHandlers($eventhandler, $callback) {
		$this->_event_handlers[$eventhandler] = $callback;
	}


	/**
	 * AmiLib::__construct()
	 *
	 * Default constructor of AmiLib class
	 *
	 * @param array $config Array of the parameters used to connect to the server
	 *
	 * <code>
	 *	  array(
	 *		'server' 		=> '127.0.0.1'		// The server to connect to
	 *		'port' 			=> '5038',			// Port of manager API
	 *		'username' 		=> 'admin',			// Asterisk manager username
	 *		'password' 		=> 'password',		// Asterisk manager password
	 *		'authtype' 		=> 'plaintext'		// Valid plaintext or md5
	 *		'debug'			=> 'true or false'  // Enable or not debug
	 *		'log'			=> 'true or false'	// Enable or nor logging
	 *		'logfile'		=> 'filename'		// Log filename
	 *	  );
	 * </code>
	 *
	 * @access public
	*/
	public function __construct($config = array()) {

		// Check if phpversion is 5 or higher
		if (version_compare(PHP_VERSION, "5", "lt")) {
			throw new Exception('Requires PHP 5 or higher - '. PHP_VERSION);
		}

		// Verify if function fsockopen exists
		if (!function_exists('fsockopen')) {
			throw new Exception('Php Socket module is unavailable');
		}

		// Check the config array variables
		if (!($config)) {
			throw new Exception('Config array params is missing');
		}


		// Check the all config array valid variables
		if (!isset($config['server'])) {
			throw new Exception("Config +server+ is missing");
		}
		else {
			$this->setServer($config['server']);
		}

		if (!isset($config['port'])) {
			throw new Exception("Config +port+ is missing");
		}
		else {
			$this->setPort($config['port']);
		}

		if (!isset($config['username'])) {
			throw new Exception("Config +username+ is missing");
		}
		else {
			$this->setUsername($config['username']);
		}

		if (!isset($config['password'])) {
			throw new Exception("Config +password+ is missing");
		}
		else {
			$this->setPassword($config['password']);
		}

		if (!isset($config['authtype']))  {
			throw new Exception("Config +authtype+ is missing");
		}
		elseif ((strtolower($config['authtype']) != "plaintext") && (strtolower($config['authtype']) != "md5"))  {
			throw new Exception("Config +authtype+ is invalid. Use plaintext or md5");
		}
		else {
			$this->setAuthtype($config['authtype']);
		}

		if (!isset($config['debug']))  {
			$this->setDebug(false);
		}
		elseif (!is_bool($config['debug'])) {
			throw new Exception("Config +debug+ is invalid. Use true or false");
		}
		else{
			$this->setDebug($config['debug']);
		}

		if ((!isset($config['log'])) || ($config['log']) == false)  {
			$this->setLog(false);
		}
		elseif (!is_bool($config['log'])) {
			throw new Exception("Config +log+ is invalid. Use true or false");
		}
		elseif ( ($config['log'] == true) && (!isset($config['logfile'])) ) {
			$this->setLog($config['log']);
			$this->setLogFile($this->getLogFile());
		}

		else {
			$this->setLog($config['log']);
			$this->setLogFile($config['logfile']);
		}
	}


	/**
	 * AmiLib::debug()
	 *
	 * Debug info
	 *
	 * @param string or array message
	 * @return boolean
	*/
	function debug($info,$message) {
		// If the debug variable is true... do...
		if ($this->getDebug() == true) {
			echo "===DEBUG: $info===<br>\n";
			if (isset($message)) {
				// Verify if the variable $message is a array or no
				if (is_array($message)) {
					print_r($message);
				}
				else {
					echo "$message";
				}
			}
		}
	}

	/**
	 * AmiLib::addLog()
	 *
	 * Log file
	 *
	 * @param string $level DEBUG|INFO|WARN|ERROR|FATAL - Default is INFO
	 * @param string $message Information message
	 * @return boolean
	*/
	function addLog($level, $message) {
		// If the log variable is true... do...
		if ($this->getLog() == true) {
			$filename = $this->getLogFile();

			if (isset($level)) {
				switch (strtolower($level)) {
					case "debug":
						$level = 'debug';
						break;

					case "info":
						$level = 'info';
						break;

					case "warn":
						$level = 'warn';
						break;

					case "error":
						$level = 'error';
						break;

					case "fatal":
						$level = 'fatal';
						break;

					default:
						$level = 'info';
				}
			}
			else {
				$level = 'info';
			}

			$message = ltrim($message);
			$date = date("d-m-Y H:i:s");
			$text = "======BEGIN\n $date $level $message\n======END\n";
			$handle = @fopen($filename, "a");

			// If the opening of the file is successfully
			if ($handle != null) {
				if (fwrite($handle, $text) == false) {
					fclose($handle);
					throw new Exception("Cannot write to file $filename");
				}
				fclose($handle);
			} else {
				throw new Exception("Cannot open file $filename");
			}
			return true;
		}
		return false;
	}



	/**
	 * AmiLib::getResponse()
	 *
	 * Wait for command response
	 *
	 * Wwill return the response
	 *
	 * @param none
	 * @return array of parameters, empty on timeout
	 * @access private
	*/
	private function getResponse() {

		$str_buffer = null;
		$str_key = null;
		$str_val = null;

		$arr_ret = array();
		$int_cont = 0;

		while (($str_buffer = fgets($this->getSocket()))) {
			/**
			 * If the line contain the follows strings
			 * Response: or Message: or Privilege:,
			 * Then this line will be splited by separator : (two dots)
			*/
			if ((preg_match ('/Response:|Message:|Privilege:/i', $str_buffer))) {

				list($str_key, $str_val) = explode(': ', $str_buffer);
				/**
				 * Here is create a array with the
				 * response and value
				 * eg.
				 * $Var['Response'] = Sucess
				*/
				$str_key = trim($str_key);
				$str_val = trim($str_val);

				if ($str_key) {
					$arr_ret[$str_key] = $str_val;
				}
			}
			else {
				/**
				 * If the line is blank, then
				 * this line receive null value
				 * and don't will be part of array
				*/
				if ((preg_match('/^\s+/', $str_buffer))) {
					$int_cont++;
					$str_buffer = null;
				}
				else {

					/**
					 * If match : (two dots) in line,
					 * Again I split the line by separator :
					 * Then I create other array with his value
					*/
					if (preg_match ('/: /', $str_buffer)) {
						list($str_key, $str_val) = explode(': ', $str_buffer);

						$str_key = trim($str_key);
						$str_val = trim($str_val);

						/**
						 * If the line is not null and the array don't have nothing and
						 * the key don't have nothing value, is create a arraye
						 * with key and value
						 * eg. $arr_ret['data'][0] = [code][010];
						*/
						if ((!is_null($str_buffer)) && (count($arr_ret["data"][$int_cont]) == 0) && ($str_key)) {
							$arr_ret["data"][$int_cont] = array($str_key => $str_val);
						}
						else {
							if ($str_key) {

								/**
								 * Else only is added this array with other key and value
								*/
								$arr_ret["data"][$int_cont][$str_key] = $str_val;
							}
						}
					}

					/**
					 * Else if, I create a array with all line
					*/
					else {
						if ((!is_null($str_buffer)) && (count($arr_ret["data"][$int_cont]) == 0)) {
							$arr_ret["data"][$int_cont] = array(trim($str_buffer));
						}
						else {
							array_push($arr_ret["data"][$int_cont], trim($str_buffer));
						}
					}
				}
			}
		}
		unset($int_cont);
		return $arr_ret ;
	}

	/**
	 * AmiLib::sendRequest()
	 *
	 * Send a request to manager
	 *
	 * @param string $action Manager Action
	 * @param array $parameters AMI Commands
	 * @return array of parameters
	 * @access public
	*/
	public function sendRequest($action, $parameters = array()) {
		if ($this->getSocket()) {
			$request = "Action: $action\r\n";
			foreach($parameters as $var => $val) {
				$request.= "$var: $val\r\n";
		}

		$request.= "\r\n";
		fwrite($this->getSocket(), $request);
		$this->debug("sendRequest requests",$request);
		$this->addLog("info","Executing action $action");
		$requestResult = $this->getResponse();
		return $requestResult;
		} else {
			$this->addLog("error","Asterisk manager socket is not active");
			throw new Exception('Asterisk manager socket is not active');
		}
	}

	/**
	 * AmiLib::connect()
	 *
	 * Connect to Asterisk Manager
	 *
	 * @return boolean true on success
	 * @access public
	*/
	public function connect($events = 'off') {
		// connect the socket
		$errno = $errstr = NULL;
		$this->setSocket(@fsockopen($this->getServer(), $this->getPort(), $errno, $errstr));

		stream_set_timeout($this->getSocket(), 1);

		if (!$this->getSocket()) {
			$this->addLog("error","Unable to connect to manager {$this->getServer()}:{$this->getPort()} ($errno): $errstr");
			throw new Exception("Unable to connect to manager {$this->getServer()}:{$this->getPort()} ($errno): $errstr");
		}
		// read the header
		$strData = fgets($this->getSocket());

		if ($strData == false) {
			// possible  problem.
			$this->addLog('error','Asterisk Manager header not received.');
			throw new Exception('Asterisk Manager header not received.');
		} else {
			// note: nothing until someone looks to see why it mangles the logging
			// $this->addLog('info',$strData);
		}
		switch (strtolower($this->getAuthtype())) {

			// Authentication mode using plaintext
			case 'plaintext':
				// Send login
				$result = $this->sendRequest('login', array(
					'Username' => $this->getUsername(),
					'Secret' => $this->getPassword(),
					'Events' => $events
				));
				if ($result['Response'] != 'Success') {
					$this->addLog("error","Failed Login {$this->getServer()}:{$this->getPort()}.");
					throw new Exception("
							Failed to login {$this->getServer()}:{$this->getPort()} U:{$this->getUsername()} P:{$this->getPassword()}
					");
				}
				$this->addLog("info","Connected to {$this->getServer()}:{$this->getPort()}.");
				return true;

				// Authentication mode using md5
				case 'md5':
					// Get md5 key
					$result = $this->sendRequest('Challenge', array(
						'AuthType' => 'md5',
						'Events' => $events
					));
				
					if ($result['Response'] == 'Success') {
						$challenge = $result['data'][0]['Challenge'];

						// Md5/password hash key
						$md5_key  = md5($challenge . $this->getPassword());

						// Send login
						$result = $this->sendRequest('login', array(
							'AuthType' => $this->getAuthtype(),
							'Username' => $this->getUsername(),
							'Key' => $md5_key,
							'Events' => $events
						));

						//stream_set_timeout($this->getSocket(), 1);
						
						if ($result['Response'] != 'Success') {
							$this->addLog("error","Failed Login {$this->host}:{$this->port}");
							throw new Exception("
								Failed to login {$this->getServer()}:{$this->getPort()} U:{$this->getUsername()} P:{$this->getPassword()}
							");
						}
					}
					$this->addLog("info","Connected to {$this->getServer()}:{$this->getPort()}.");
					return true;
		}
	}

	/**
	 * AmiLib::disconnect()
	 *
	 * Close the socket connection
	 *
	 * @return none
	 * @access public
	*/
	public function disconnect() {
		if ($this->getSocket()) {
			$this->logoff();
			fclose($this->getSocket());
			$this->addLog("info","Disconnected from {$this->getServer()}:{$this->getPort()}.");
		}
		$this->setServer(NULL);
		$this->setPort(NULL);
	}

	/**
	 * AmiLib::logoff()
	 *
	 * Logout of the current manager session attached to $this->_socket
	 *
	 * @return bool
	 * @access private
	*/
	private function logoff(){
		return $this->sendRequest('Logoff');
	}

	/**
	 * AmiLib::is_connected()
	 *
	 * Check if the socket is connected
	 *
	 * @return bool
	 * @access public
	*/
	public function is_connected(){
		return (bool)$this->getSocket();
	}

	/**
	 * AmiLib::commandExecute()
	 *
	 * Execute Asterisk CLI Command
	 *
	 * @param string $command
	 * @param string $actionid message matching variable
	 * @return array data
	 * @access public
	*/
	public function commandExecute($command, $actionid=NULL){
		$this->debug("commandExecute","Executing command $command");
		$this->addLog("info","Executing command $command");
		$parameters = array('Command'=>$command);
		if($actionid) {
			$parameters['ActionID'] = $actionid;
		}
		return $this->sendRequest('Command', $parameters);
	}
}
?>

