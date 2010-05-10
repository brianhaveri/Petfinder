<?php

/**
 *----------------------------------------
 * PHP Client Library for Petfinder.com API
 * @author Brian Haveri
 * @link http://github.com/brianhaveri/petfinder
 * @license MIT License http://en.wikipedia.org/wiki/MIT_License
 *----------------------------------------
 */
class Petfinder {

	// Only modify if official API changes.
	private $_apiUrl = 'http://api.petfinder.com/';
	private $_validResponseFormats = array('json', 'xml');
	private $_methodsRequiringToken = array();
	
	
	// Default values. You can modify these.
	private $_responseFormat = 'xml';
	
	
	// Intermediate storage. Do not modify.
	private $_apiKey;
	private $_apiSecret;
	private $_lastRequest;
	private $_token;
	
	
	/**
	 *----------------------------------------
	 * Create Petfinder instance
	 * @param string $apiKey API Key
	 * @param string $apiSecret API Secret
	 *----------------------------------------
	 */
	function __construct($apiKey, $apiSecret=NULL) {
		$this->_apiKey = $apiKey;
		$this->_apiSecret = $apiSecret;
	}
	
	
	/**
	 *----------------------------------------
	 * Set API response format
	 * @param string $format
	 * @return bool TRUE on success, FALSE on fail
	 *----------------------------------------
	 */
	public function setResponseFormat($format) {
		if(in_array($format, $this->_validResponseFormats)) {
			$this->_responseFormat = $format;
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 *----------------------------------------
	 * Get the last request
	 * @return string Request
	 *----------------------------------------
	 */
	public function getLastRequest() {
		return $this->_lastRequest;
	}
	
	
	/**
	 *----------------------------------------
	 * Get the token
	 * @return string
	 *----------------------------------------
	 */
	public function getToken() {
		return $this->_token;
	}
	
	
	/**
	 *----------------------------------------
	 * Set the token
	 * @param string $token
	 * @return bool
	 *----------------------------------------
	 */
	public function setToken($token) {
		$this->_token = $token;
		return TRUE;
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: Returns a token valid for a timed session
	 * @link http://www.petfinder.com/developers/api-docs
	 * @return string
	 *----------------------------------------
	 */
	public function auth_getToken() {
		return $this->_callMethod(__FUNCTION__, array('sig'=>$this->_getSignature()));
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: Returns a list of breeds for a particular animal.
	 * @link http://www.petfinder.com/developers/api-docs
	 * @param mixed $data Array with 'animal' key OR string of animal name
	 * @return string
	 *----------------------------------------
	 */
	public function breed_list($data) {
		if(! is_array($data)) {
			$data = array('animal'=>(string) $data);
		}
		return $this->_callMethod(__FUNCTION__, $data);
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: Returns a record for a single pet.
	 * @link http://www.petfinder.com/developers/api-docs
	 * @param mixed $data Array with 'id' key OR integer of pet id
	 * @return string
	 *----------------------------------------
	 */
	public function pet_get($data) {
		if(! is_array($data)) {
			$data = array('id'=>(string) $data);
		}
		return $this->_callMethod(__FUNCTION__, $data);
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: Returns a record for a randomly selected pet.
	 * @link http://www.petfinder.com/developers/api-docs
	 * @param array $data
	 * @return string
	 *----------------------------------------
	 */
	public function pet_getRandom($data=array()) {
		return $this->_callMethod(__FUNCTION__, $data);
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: returns a collection of pet records.
	 * @link http://www.petfinder.com/developers/api-docs
	 * @param mixed $data Array with 'location' key OR string of location
	 * @return string
	 *----------------------------------------
	 */
	public function pet_find($data) {
		if(! is_array($data)) {
			$data = array('location'=>(string) $data);
		}
		return $this->_callMethod(__FUNCTION__, $data);
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: Returns a collection of shelter records matching your search criteria.
	 * @link http://www.petfinder.com/developers/api-docs
	 * @param mixed $data Array with 'location' key OR string of location
	 * @return string
	 *----------------------------------------
	 */
	public function shelter_find($data) {
		if(! is_array($data)) {
			$data = array('location'=>(string) $data);
		}
		return $this->_callMethod(__FUNCTION__, $data);
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: Returns a record for a single shelter.
	 * @link http://www.petfinder.com/developers/api-docs
	 * @param mixed $data Array with 'id' key OR string of shelter id
	 * @return string
	 *----------------------------------------
	 */
	public function shelter_get($data) {
		if(! is_array($data)) {
			$data = array('id'=>(string) $data);
		}
		return $this->_callMethod(__FUNCTION__, $data);
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: Returns a list of IDs or collection of pet records for an individual shelter
	 * @link http://www.petfinder.com/developers/api-docs
	 * @param mixed $data Array with 'id' key OR string of shelter id
	 * @return string
	 *----------------------------------------
	 */
	public function shelter_getPets($data) {
		if(! is_array($data)) {
			$data = array('id'=>(string) $data);
		}
		return $this->_callMethod(__FUNCTION__, $data);
	}
	
	
	/**
	 *----------------------------------------
	 * Petfinder API: Returns a list of shelter IDs listing animals of a particular breed.
	 * @link http://www.petfinder.com/developers/api-docs
	 * @param array $data
	 * @return string
	 *----------------------------------------
	 */
	public function shelter_listByBreed($data) {
		return $this->_callMethod(__FUNCTION__, $data);
	}
	
	
	/**
	 *----------------------------------------
	 * Call an API method
	 * @param string $method Method name
	 * @param array $data
	 * @return string
	 *----------------------------------------
	 */
	private function _callMethod($method, $data=array()) {
	
		// Get a token if necessary
		if(in_array($method, $this->_methodsRequiringToken)) {
			if(! $this->getToken()) {
				$tokenResponse = $this->auth_getToken();
				$matches = FALSE;
				switch($this->_responseFormat) {
					case 'json':preg_match('/"token"\:{"\$t"\:"(.*?)"}/', $tokenResponse, $matches); break;
					case 'xml':	preg_match('/<token>(.*?)<\/token>/', $tokenResponse, $matches); break;
				}
				if(is_array($matches) && count($matches) > 1) {
					$this->setToken($matches[1]);
				}
			}
			$data['token'] = $this->getToken();
		}
		
		$request = $this->_getRequest($method, $data);
		if($method === 'auth_getToken') {
			$request = str_replace('&amp;', '&', $request);
		}
		$this->_lastRequest = $request;
		return $this->_getResponse($request);
	}
	
	
	/**
	 *----------------------------------------
	 * Send an API request and receive a response
	 * @param string $request
	 * @return array $data
	 *----------------------------------------
	 */
	private function _getResponse($request) {
		$curlOptions = array(
			CURLOPT_URL	=> $request,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_USERAGENT => __CLASS__.' PHP Client Library'
		);
		$ch = curl_init();
		curl_setopt_array($ch, $curlOptions);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	
	/**
	 *----------------------------------------
	 * Generate an API request string
	 * @param string $method
	 * @param array $data
	 * @return string
	 *----------------------------------------
	 */
	private function _getRequest($method, $data=array()) {
		
		// Argument order matters for auth_getToken().
		// For consistency, always use boilerPlateData, then data
		$boilerPlateData = array(
			'key'	=> $this->_apiKey,
			'format'=> $this->_responseFormat
		);
		$data = array_merge($boilerPlateData, (array) $data);
		
		return join('', array(
			$this->_apiUrl,
			$this->_convertMethod($method),
			'?',
			http_build_query($data)
		));
	}
	
	
	/**
	 *----------------------------------------
	 * Generate an API signature
	 * @return string Signature
	 *----------------------------------------
	 */
	private function _getSignature() {
		return md5($this->_apiSecret.'key='.$this->_apiKey.'&format='.$this->_responseFormat);
	}
	
	
	/**
	 *----------------------------------------
	 * Convert a method name into API method name format
	 * Example: pet_find => pet.find
	 * @param string $method
	 * @return string
	 *----------------------------------------
	 */
	private function _convertMethod($method) {
		return str_replace('_', '.', $method);
	}
}

?>