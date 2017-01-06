<?php
namespace Social;

abstract class ProviderAbstract {
	
	protected $_credentials = [];
	protected $_token = null;
	
	abstract public function redirectToAuth($scopes = []);
	abstract public function retriveToken($code = '');	
	abstract public function getSources($input_params = []);
	abstract public function getPosts($input_params = []);
	abstract public function getComments($input_params = []);
	abstract public function createComment($input_params = []);
	abstract public function deleteComment($input_params = []);
	abstract public function getMessages($source_id = 0);
	abstract public function sendMessage($input_params = []);
	
	public function __construct($credentials = []) {
		$this->setCredentials($credentials);
	}	
	
	public function setToken($token) {
		$this->_token = $token;
	}
	
	public function getToken() {
		return $this->_token;
	}
	
	public function setCredentials($credentials = []) {
		if (!empty($credentials) && is_array($credentials)) {
			foreach($credentials as $key => $value) {
				$this->_credentials[$key] = $value;
			}			
		}
	}
	
	public function getCredentials() {
		return $this->_credentials;
	}
	
}
?>