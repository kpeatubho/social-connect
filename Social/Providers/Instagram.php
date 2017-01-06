<?php
namespace Social\Providers;
require_once __DIR__."/../ProviderAbstract.php";

class Instagram extends \Social\ProviderAbstract {
	
	const SCOPE_BASIC = 'basic';
	const SCOPE_PUBLIC_CONTENT = 'public_content';
	const SCOPE_FOLLOWERS_LIST = 'follower_list';
	const SCOPE_COMMENTS = 'comments';
	const SCOPE_RELATIONSHIPS = 'relationships';
	const SCOPE_LIKES = 'likes';
	
	protected $_credentials = [
		'client_id' => '',
		'client_secret' => '',
		'redirect_url' => ''
	];
	
	public function redirectToAuth($scopes = []) {
		$url = 'https://api.instagram.com/oauth/authorize/';
		$params = [
			'client_id' => $this->_credentials['client_id'],
			'redirect_uri' => $this->_credentials['redirect_url'],
			'response_type' => 'code',
			'scope' => implode(' ', $scopes)
		];
		$url .= '?' . http_build_query($params);
		header('Location: '.$url);
	}
	
	public function retriveToken($code = '') {
		$return = null;
		if (isset($_REQUEST['code'])) {
			$url = 'https://api.instagram.com/oauth/access_token';
			$params = [
				'client_id' => $this->_credentials['client_id'],
				'client_secret' => $this->_credentials['client_secret'],
				'grant_type' => 'authorization_code',
				'redirect_uri' => $this->_credentials['redirect_url'],
				'code' => $_REQUEST['code']
			];				
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$result = curl_exec($curl);
			curl_close($curl);		
			if (!$result = @json_decode($result, true)) {
				$result = [];
			}
			if (isset($result['access_token'])) {				
				$return = [
					'token' => $result['access_token'],
					'expires' => '0000-00-00 00:00:00'
				];
			}
		}
		return $return;
	}
	
	public function getSources($input_params = []){
		$return = [];
		$sources_ids = [];
		if (isset($input_params['source_id']) && is_array($input_params['source_id'])) {
			foreach($input_params['source_id'] as $source_id) {
				if ($source_id > 0) {
					$sources_ids[] = $source_id;
				}
			}
		}
		if (count($sources_ids) > 0) {
			foreach($sources_ids as $source_id) {
				$url = 'https://api.instagram.com/v1/users/'.$source_id;
				$params = [
					'access_token' => $this->_token
				];
				$url .= '?' . http_build_query($params);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$result = curl_exec($curl);
				curl_close($curl);		
				if (!$result = @json_decode($result, true)) {
					$result = [];
				};
				if (isset($result['data']['id'])) {					
					$return[] = [
						'source_id' => $result['data']['id'],
						'name' => $result['data']['full_name'],
						'image' => $result['data']['profile_picture'],
						'birthday' => '0000-00-00',
						'country' => '',
						'city' => '',
						'contacts' => [
							'mobile_phone' => '',
							'home_phone' => ''
						],
						'connections' => [],
						'type' => 'user'
					];				
				}		
			}
		} else {
			$url = 'https://api.instagram.com/v1/users/self';
			$params = [
				'access_token' => $this->_token
			];
			$url .= '?' . http_build_query($params);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$result = curl_exec($curl);
			curl_close($curl);		
			if (!$result = @json_decode($result, true)) {
				$result = [];
			};
			if (isset($result['data']['id'])) {					
				$return[] = [
					'source_id' => $result['data']['id'],
					'name' => $result['data']['full_name'],
					'image' => $result['data']['profile_picture'],
					'birthday' => '0000-00-00',
					'country' => '',
					'city' => '',
					'contacts' => [
						'mobile_phone' => '',
						'home_phone' => ''
					],
					'connections' => [],
					'type' => 'user'
				];				
			}			
		}
		return $return;
	}
	
	public function getPosts($input_params = []){
		$return = [];
		if (isset($input_params['source']) && is_array($input_params['source'])) {
			foreach($input_params['source'] as $source) {
				$process_getting = true;
				$original_url = true;
				$counter = 0;
				while($process_getting) {	
					if ($original_url) {
						$url = 'https://api.instagram.com/v1/users/'.$source.'/media/recent';
						$params = [
							'access_token' => $this->_token
						];
						$url .= '?' . http_build_query($params);
					}			
					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_HEADER, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
					$result = curl_exec($curl);
					curl_close($curl);		
					if (!$result = @json_decode($result, true)) {
						$result = [];
					}
					$process_getting = false;
					if (isset($result['data']) && is_array($result['data']) && !empty($result['data'])) {
						if (isset($result['pagination']['next_url']) && $result['pagination']['next_url'] != "") {
							$process_getting = true;
							$url = $result['pagination']['next_url'];
							$original_url = false;
						}
						foreach($result['data'] as $item) {
							$message = '';
							if (isset($item['caption'])) {
								$message = $item['caption'];
							}
							$picture = '';
							if (isset($item['images']['thumbnail']['url'])) {
								$picture = $item['images']['thumbnail']['url'];
							}
							if ($message != "" || $picture != "") {								
								$return[] = [
									'post_id' => $item['id'],
									'from_id' => $source,
									'owner_id' => $source,
									'date' => date("Y-m-d H:i:s", $item['created_time']),
									'text' => $message,
									'image' => $picture,
									'comments_count' => $item['comments']['count']
								];
							}							
						}
					}
				}
			}
		}
		usort($return, function($a, $b){
			if (strtotime($a['date']) > strtotime($b['date'])) return 1;
			if (strtotime($a['date']) < strtotime($b['date'])) return -1;
			if (strtotime($a['date']) == strtotime($b['date'])) return 0;
		});
		return $return;
	}
	
	public function getComments($input_params = []) {
		$return = [];
		if (isset($input_params['post_id']) && $input_params['post_id'] > 0) {
			$process_getting = true;
			$original_url = true;
			while($process_getting) {
				if ($original_url) {
					$url = 'https://api.instagram.com/v1/media/'.$input_params['post_id'].'/comments';
					$params = [
						'access_token' => $this->_token
					];
					$url .= '?' . http_build_query($params);
				}
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$result = curl_exec($curl);
				curl_close($curl);		
				if (!$result = @json_decode($result, true)) {
					$result = [];
				}
				$process_getting = false;
				if (isset($result['data']) && is_array($result['data']) && !empty($result['data'])) {
					if (isset($result['paging']['next']) && $result['paging']['next'] != "") {
						$process_getting = true;
						$url = $result['paging']['next'];
						$original_url = false;
					}
					foreach($result['data'] as $item) {
						$message = '';
						if (isset($item['text'])) {
							$message = $item['text'];
						}
						if ($message != "") {
							$return[] = [
								'comment_id' => $item['id'],
								'from_id' => $item['from']['id'],
								'date' => date("Y-m-d H:i:s", $item['created_time']),
								'text' => $message,
								'appeal_to' => []
							];
						}							
					}
				}			
			}
		}
		return $return;
	}
	
	public function createComment($input_params = []){
		$return = null;
		if (isset($input_params['post_id']) && $input_params['post_id'] > 0 && isset($input_params['message']) && trim($input_params['message']) != "") {			
			$url = 'https://api.instagram.com/v1/media/'.$input_params['post_id'].'/comments';
			$params = [
				'access_token' => $this->_token,
				'text' => $input_params['message']
			];
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$result = curl_exec($curl);
			curl_close($curl);		
			if (!$result = @json_decode($result, true)) {
				$result = [];
			}
			if (isset($result['data']['id']) && $result['data']['id'] > 0) {
				$return = $result['data']['id'];
			}
		}
		return $return;
	}
	
	public function deleteComment($input_params = []){
		$return = false;
		if (isset($input_params['post_id']) && $input_params['post_id'] > 0 && isset($input_params['comment_id']) && $input_params['comment_id'] > 0) {
			$url = 'https://api.instagram.com/v1/media/'.$input_params['post_id'].'/comments/'.$input_params['comment_id'];
			$params = [
				'access_token' => $this->_token
			];
			$url .= '?' . http_build_query($params);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$result = curl_exec($curl);
			curl_close($curl);		
			if (!$result = @json_decode($result, true)) {
				$result = [];
			}
			if (isset($result['meta']['code']) && $result['meta']['code'] == 200) {
				$return = true;
			}
		}
		return $return;
	}
	
	public function getMessages($source_id = 0){
		$return = [];		
		return $return;
	}
	
	public function sendMessage($input_params = []){
		$return = null;		
		return $return;
	}
	
}
?>