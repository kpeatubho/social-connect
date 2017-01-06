<?php
namespace Social\Providers;
require_once __DIR__."/../ProviderAbstract.php";

class Facebook extends \Social\ProviderAbstract {
	
	const SCOPE_PUBLIC_PROFILE = 'public_profile';
	const SCOPE_USER_FRIENDS = 'user_friends';
	const SCOPE_EMAIL = 'email';
	const SCOPE_USER_ABOUT_ME = 'user_about_me';
	const SCOPE_USER_BIRTHDAY = 'user_birthday';
	const SCOPE_USER_EDUCATION_HISTORY = 'user_education_history';
	const SCOPE_USER_EVENTS = 'user_events';
	const SCOPE_USER_GAMES_ACTIVITY = 'user_games_activity';
	const SCOPE_USER_HOMETOWN = 'user_hometown';
	const SCOPE_USER_LIKES = 'user_likes';
	const SCOPE_USER_LOCATION = 'user_location';
	const SCOPE_USER_MANAGED_GROUPS = 'user_managed_groups';
	const SCOPE_USER_PHOTOS = 'user_photos';
	const SCOPE_USER_POSTS = 'user_posts';
	const SCOPE_USER_RELATIONSHIPS = 'user_relationships';
	const SCOPE_USER_RELATIONSHIP_DETAILS = 'user_relationship_details';
	const SCOPE_USER_RELIGION_POLITICS = 'user_religion_politics';
	const SCOPE_USER_TAGGED_PLACES = 'user_tagged_places';
	const SCOPE_USER_VIDEOS = 'user_videos';
	const SCOPE_USER_WEBSITE = 'user_website';
	const SCOPE_USER_WORK_HISTORY = 'user_work_history';
	const SCOPE_READ_CUSTOM_FRIENDLIST = 'read_custom_friendlists';
	const SCOPE_READ_INSIGHTS = 'read_insights';
	const SCOPE_READ_AUDIENCE_NETWORK_INSIGHTS = 'read_audience_network_insights';
	const SCOPE_READ_PAGE_MAILBOXES = 'read_page_mailboxes';
	const SCOPE_MANAGE_PAGES = 'manage_pages';
	const SCOPE_PUBLISH_PAGES = 'publish_pages';
	const SCOPE_PUBLISH_ACTIONS = 'publish_actions';
	const SCOPE_RSVP_EVENT = 'rsvp_event';
	const SCOPE_PAGES_SHOW_LIST = 'pages_show_list';
	const SCOPE_PAGES_MANAGE_CTA = 'pages_manage_cta';
	const SCOPE_PAGES_MANAGE_INSTANT_ARTICLES = 'pages_manage_instant_articles';
	const SCOPE_ADS_READ = 'ads_read';
	const SCOPE_ADS_MANAGEMENT = 'ads_management';
	const SCOPE_BUSINESS_MANAGEMENT = 'business_management';
	const SCOPE_PAGES_MESSAGING = 'pages_messaging';
	const SCOPE_PAGES_MESSAGING_SUBSCRIPTIONS = 'pages_messaging_subscriptions';
	const SCOPE_PAGES_MESSAGING_PAYMENTS = 'pages_messaging_payments';
	const SCOPE_PAGES_MESSAGING_PHONE_NUMBER = 'pages_messaging_phone_number';
	
	protected $_credentials = [
		'client_id' => '',
		'client_secret' => '',
		'redirect_url' => ''
	];
	
	public function redirectToAuth($scopes = []) {
		$url = 'https://www.facebook.com/v2.8/dialog/oauth';
		$params = [
			'client_id' => $this->_credentials['client_id'],
			'redirect_uri' => $this->_credentials['redirect_url'],
			'response_type' => 'code',
			'scope' => implode(',', $scopes),
			'auth_type' => 'rerequest'
		];
		$url .= '?' . http_build_query($params);
		header('Location: '.$url);
	}
	
	public function retriveToken($code = '') {
		$return = null;
		if (isset($_REQUEST['code'])) {
			$url = 'https://graph.facebook.com/v2.8/oauth/access_token';
			$params = [
				'client_id' => $this->_credentials['client_id'],
				'client_secret' => $this->_credentials['client_secret'],
				'redirect_uri' => $this->_credentials['redirect_url'],
				'code' => $_REQUEST['code']
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
			}
			if (isset($result['access_token'])) {
				$url = 'https://graph.facebook.com/v2.8/oauth/access_token';
				$params = [
					'grant_type' => 'fb_exchange_token',
					'client_id' => $this->_credentials['client_id'],
					'client_secret' => $this->_credentials['client_secret'],
					'fb_exchange_token' => $result['access_token']
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
				}
				if (isset($result['access_token'])) {
					$return = [
						'token' => $result['access_token'],
						'expires' => date("Y-m-d H:i:s", time() + $result['expires_in'])
					];
				}
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
				$url = 'https://graph.facebook.com/v2.8/'.$source_id;
				$params = [
					'access_token' => $this->_token,
					'metadata' => 1
				];
				$url .= '?' . http_build_query($params);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$meta_result = curl_exec($curl);
				curl_close($curl);		
				if (!$meta_result = @json_decode($meta_result, true)) {
					$meta_result = [];
				};
				if (isset($meta_result['id'])) {
					if ($meta_result['metadata']['type'] == 'user') {
						$url = 'https://graph.facebook.com/v2.8/'.$source_id;
						$params = [
							'access_token' => $this->_token,
							'fields' => 'id,first_name,last_name,birthday,location'
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
						if (isset($result['id'])) {
							$image = '';
							$url = 'https://graph.facebook.com/v2.8/me/picture';
							$params = [
								'access_token' => $this->_token,
								'type' => 'square',
								'redirect' => 'false'
							];
							$url .= '?' . http_build_query($params);
							$curl = curl_init();
							curl_setopt($curl, CURLOPT_URL, $url);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_HEADER, 0);
							curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
							$temp_result = curl_exec($curl);
							curl_close($curl);		
							if (!$temp_result = @json_decode($temp_result, true)) {
								$temp_result = [];
							};
							if (isset($temp_result['data']['url']) && $temp_result['data']['url'] != "") {
								$image = $temp_result['data']['url'];
							}
							$birthday = '0000-00-00';
							if (isset($result['birthday'])) {
								$temp = explode("/", $result['response'][0]['bdate']);
								if (count($temp) == 3) {
									$birthday = date('Y-m-d', mktime(0, 0, 0, $temp[1], $temp[0], $temp[2]));
								} elseif (count($temp) == 2) {
									$birthday = date('Y-m-d', mktime(0, 0, 0, $temp[1], $temp[0], 1900));
								} elseif (count($temp) == 1) {
									$birthday = date('Y-m-d', mktime(0, 0, 0, 1, 1, $temp[0]));
								}
							}
							$country = '';
							$city = '';
							if (isset($result['location']['id']) && $result['location']['id'] > 0) {
								$url = 'https://graph.facebook.com/v2.8/'.$result['location']['id'];
								$params = [
									'access_token' => $this->_token,
									'fields' => 'id,location'
								];
								$url .= '?' . http_build_query($params);
								$curl = curl_init();
								curl_setopt($curl, CURLOPT_URL, $url);
								curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($curl, CURLOPT_HEADER, 0);
								curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
								curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
								$temp_result = curl_exec($curl);
								curl_close($curl);		
								if (!$temp_result = @json_decode($temp_result, true)) {
									$temp_result = [];
								};
								if (isset($temp_result['location']['country']) && $temp_result['location']['country'] != "") {
									$country = $temp_result['location']['country'];
								}
								if (isset($temp_result['location']['city']) && $temp_result['location']['city'] != "") {
									$city = $temp_result['location']['city'];
								}
							}			
							$return[] = [
								'source_id' => $result['id'],
								'name' => trim($result['first_name'] . ' ' . $result['last_name']),
								'image' => $image,
								'birthday' => $birthday,
								'country' => $country,
								'city' => $city,
								'contacts' => [
									'mobile_phone' => '',
									'home_phone' => ''
								],
								'connections' => [],
								'type' => 'user'
							];
						}
					} elseif ($meta_result['metadata']['type'] == 'group') {
						$url = 'https://graph.facebook.com/v2.8/'.$source_id;
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
						if (isset($result['id'])) {
							$image = '';
							$url = 'https://graph.facebook.com/v2.8/'.$result['id'].'/picture';
							$params = [
								'access_token' => $this->_token,
								'type' => 'square',
								'redirect' => 'false'
							];
							$url .= '?' . http_build_query($params);
							$curl = curl_init();
							curl_setopt($curl, CURLOPT_URL, $url);
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_HEADER, 0);
							curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
							$temp_result = curl_exec($curl);
							curl_close($curl);		
							if (!$temp_result = @json_decode($temp_result, true)) {
								$temp_result = [];
							};
							if (isset($temp_result['data']['url']) && $temp_result['data']['url'] != "") {
								$image = $temp_result['data']['url'];
							}
							$return[] = [
								'source_id' => $result['id'],
								'full_name' => $result['name'],
								'image' => $image,
								'bdate' => '0000-00-00',
								'country' => '',
								'city' => '',
								'contacts' => [
									'mobile_phone' => '',
									'home_phone' => ''
								],
								'connections' => '',
								'type' => 'group'
							];
						}
					}
				}			
			}
		} else {
			$url = 'https://graph.facebook.com/v2.8/me';
			$params = [
				'access_token' => $this->_token,
				'fields' => 'id,first_name,last_name,birthday,location'
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
			if (isset($result['id'])) {
				$image = '';
				$url = 'https://graph.facebook.com/v2.8/me/picture';
				$params = [
					'access_token' => $this->_token,
					'type' => 'square',
					'redirect' => 'false'
				];
				$url .= '?' . http_build_query($params);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				$temp_result = curl_exec($curl);
				curl_close($curl);		
				if (!$temp_result = @json_decode($temp_result, true)) {
					$temp_result = [];
				};
				if (isset($temp_result['data']['url']) && $temp_result['data']['url'] != "") {
					$image = $temp_result['data']['url'];
				}
				$birthday = '0000-00-00';
				if (isset($result['birthday'])) {
					$temp = explode("/", $result['response'][0]['bdate']);
					if (count($temp) == 3) {
						$birthday = date('Y-m-d', mktime(0, 0, 0, $temp[1], $temp[0], $temp[2]));
					} elseif (count($temp) == 2) {
						$birthday = date('Y-m-d', mktime(0, 0, 0, $temp[1], $temp[0], 1900));
					} elseif (count($temp) == 1) {
						$birthday = date('Y-m-d', mktime(0, 0, 0, 1, 1, $temp[0]));
					}
				}
				$country = '';
				$city = '';
				if (isset($result['location']['id']) && $result['location']['id'] > 0) {
					$url = 'https://graph.facebook.com/v2.8/'.$result['location']['id'];
					$params = [
						'access_token' => $this->_token,
						'fields' => 'id,location'
					];
					$url .= '?' . http_build_query($params);
					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($curl, CURLOPT_HEADER, 0);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
					$temp_result = curl_exec($curl);
					curl_close($curl);		
					if (!$temp_result = @json_decode($temp_result, true)) {
						$temp_result = [];
					};
					if (isset($temp_result['location']['country']) && $temp_result['location']['country'] != "") {
						$country = $temp_result['location']['country'];
					}
					if (isset($temp_result['location']['city']) && $temp_result['location']['city'] != "") {
						$city = $temp_result['location']['city'];
					}
				}			
				$return[] = [
					'source_id' => $result['id'],
					'name' => trim($result['first_name'] . ' ' . $result['last_name']),
					'image' => $image,
					'birthday' => $birthday,
					'country' => $country,
					'city' => $city,
					'contacts' => [
						'mobile_phone' => '',
						'home_phone' => ''
					],
					'connections' => [],
					'type' => 'user'
				];
				$url = 'https://graph.facebook.com/v2.8/me/groups';
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
				if (isset($result['data']) && is_array($result['data'])) {
					foreach($result['data'] as $item) {
						$image = '';
						$url = 'https://graph.facebook.com/v2.8/'.$item['id'].'/picture';
						$params = [
							'access_token' => $this->_token,
							'type' => 'square',
							'redirect' => 'false'
						];
						$url .= '?' . http_build_query($params);
						$curl = curl_init();
						curl_setopt($curl, CURLOPT_URL, $url);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($curl, CURLOPT_HEADER, 0);
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
						$temp_result = curl_exec($curl);
						curl_close($curl);		
						if (!$temp_result = @json_decode($temp_result, true)) {
							$temp_result = [];
						};
						if (isset($temp_result['data']['url']) && $temp_result['data']['url'] != "") {
							$image = $temp_result['data']['url'];
						}
						$return[] = [
							'source_id' => $item['id'],
							'full_name' => $item['name'],
							'image' => $image,
							'bdate' => '0000-00-00',
							'country' => '',
							'city' => '',
							'contacts' => [
								'mobile_phone' => '',
								'home_phone' => ''
							],
							'connections' => '',
							'type' => 'group'
						];						
					}
				}
			}			
		}
		return $return;
	}
	
	public function getPosts($input_params = []){
		$return = [];
		$from = array();
		if (isset($input_params['from']) && is_array($input_params['from'])) {
			$from = $input_params['from'];
		}
		if (isset($input_params['source']) && is_array($input_params['source'])) {
			foreach($input_params['source'] as $source) {
				$process_getting = true;
				$original_url = true;
				$counter = 0;
				while($process_getting) {	
					if ($original_url) {
						$url = 'https://graph.facebook.com/v2.8/'.$source.'/feed';
						$params = [
							'access_token' => $this->_token,
							'fields' => 'id,message,created_time,from,picture'
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
							if (empty($from) || in_array($item['from']['id'], $from)) {
								$message = '';
								if (isset($item['message'])) {
									$message = $item['message'];
								}
								$picture = '';
								if (isset($item['picture'])) {
									$picture = $item['picture'];
								}
								if ($message != "" || $picture != "") {
									$comments_count = 0;
									$temp_url = 'https://graph.facebook.com/v2.8/'.$item['id'].'/comments';
									$params = [
										'access_token' => $this->_token,
										'summary' => 1
									];
									$temp_url .= '?' . http_build_query($params);
									$curl = curl_init();
									curl_setopt($curl, CURLOPT_URL, $temp_url);
									curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($curl, CURLOPT_HEADER, 0);
									curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
									curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
									$temp_result = curl_exec($curl);
									curl_close($curl);		
									if (!$temp_result = @json_decode($temp_result, true)) {
										$temp_result = [];
									};
									if (isset($temp_result['summary']['total_count'])) {
										$comments_count = $temp_result['summary']['total_count'];
									}
									$return[] = [
										'post_id' => $item['id'],
										'from_id' => $item['from']['id'],
										'owner_id' => $source,
										'date' => date("Y-m-d H:i:s", strtotime($item['created_time'])),
										'text' => $message,
										'image' => $picture,
										'comments_count' => $comments_count
									];
								}							
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
					$url = 'https://graph.facebook.com/v2.8/'.$input_params['post_id'].'/comments';
					$params = [
						'access_token' => $this->_token,
						'fields' => 'id,message,created_time,from',
						'node' => 'comment'
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
						if (isset($item['message'])) {
							$message = $item['message'];
						}
						if ($message != "") {
							$return[] = [
								'comment_id' => $item['id'],
								'from_id' => $item['from']['id'],
								'date' => date("Y-m-d H:i:s", strtotime($item['created_time'])),
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
			$comment_object = $input_params['post_id'];
			if (isset($input_params['reply_to_comment']) && $input_params['reply_to_comment'] > 0) {
				$comment_object = $input_params['reply_to_comment'];
			}				
			$url = 'https://graph.facebook.com/v2.8/'.$comment_object.'/comments';
			$params = [
				'access_token' => $this->_token,
				'message' => $input_params['message']
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
			if (isset($result['id']) && $result['id'] > 0) {
				$return = $result['id'];
			}
		}
		return $return;
	}
	
	public function deleteComment($input_params = []){
		$return = false;
		if (isset($input_params['comment_id']) && $input_params['comment_id'] > 0) {
			$url = 'https://graph.facebook.com/v2.8/'.$input_params['comment_id'];
			$params = [
				'access_token' => $this->_token
			];
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			$result = curl_exec($curl);
			curl_close($curl);		
			if (!$result = @json_decode($result, true)) {
				$result = [];
			}
			if (isset($result['success']) && $result['success'] == 1) {
				$return = true;
			}
		}
		return $return;
	}
	
	public function getMessages($source_id = 0){
		$return = [];
		if ($source_id != 0) {
			$pages = [];
			$url = 'https://graph.facebook.com/v2.8/me/accounts';
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
			}
			if (isset($result['data']) && is_array($result['data']) && !empty($result['data'])) {
				foreach($result['data'] as $page) {
					$pages[] = [
						'id' => $page['id'],
						'token' => $page['access_token']
					];
				}
			}
			foreach($pages as $page) {					
				$process_getting = true;
				$original_url = true;
				while($process_getting) {	
					if ($original_url) {
						$url = 'https://graph.facebook.com/v2.8/'.$page['id'].'/conversations';
						$params = [
							'access_token' => $page['token'],
							'fields' => 'id,participants'
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
						foreach($result['data'] as $conversation) {													
							$source_found = false;
							if (isset($conversation['participants']['data']) && is_array($conversation['participants']['data']) && !empty($conversation['participants']['data'])) {
								foreach($conversation['participants']['data'] as $participant) {
									if ($participant['id'] == $source_id) {
										$source_found = true;
										break;
									}
								}
							}
							if ($source_found) {
								$process_getting = false;
								$process_getting_history = true;
								$original_url_history = true;
								while($process_getting_history) {	
									if ($original_url_history) {
										$url = 'https://graph.facebook.com/v2.8/'.$conversation['id'].'/messages';
										$params = [
											'access_token' => $page['token'],
											'fields' => 'id,from,message,created_time'
										];
										$url .= '?' . http_build_query($params);
									}																		
									$curl = curl_init();
									curl_setopt($curl, CURLOPT_URL, $url);
									curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
									curl_setopt($curl, CURLOPT_HEADER, 0);
									curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
									curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
									$result_history = curl_exec($curl);
									curl_close($curl);		
									if (!$result_history = @json_decode($result_history, true)) {
										$result_history = [];
									}
									$process_getting_history = false;
									if (isset($result_history['data']) && is_array($result_history['data']) && !empty($result_history['data'])) {
										if (isset($result_history['paging']['next']) && $result_history['paging']['next'] != "") {
											$process_getting_history = true;
											$url = $result_history['paging']['next'];
											$original_url_history = false;
										}
										foreach($result_history['data'] as $item_history) {
											$return[] = [
												'message_id' => $item_history['id'],
												'from_id' => $item_history['from']['id'],
												'date' => date("Y-m-d H:i:s", strtotime($item_history['created_time'])),
												'message' => $item_history['message'],
												'read' => 1
											];
										}
									}
								}
								break;
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
	
	public function sendMessage($input_params = []){
		$return = null;
		if (isset($input_params['source_id']) && $input_params['source_id'] != 0 && isset($input_params['message']) && trim($input_params['message']) != "") {
			$pages = [];
			$url = 'https://graph.facebook.com/v2.8/me/accounts';
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
			}
			if (isset($result['data']) && is_array($result['data']) && !empty($result['data'])) {
				foreach($result['data'] as $page) {
					$pages[] = [
						'id' => $page['id'],
						'token' => $page['access_token']
					];
				}
			}
			foreach($pages as $page) {					
				$process_getting = true;
				$original_url = true;
				while($process_getting) {	
					if ($original_url) {
						$url = 'https://graph.facebook.com/v2.8/'.$page['id'].'/conversations';
						$params = [
							'access_token' => $page['token'],
							'fields' => 'id,participants'
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
						foreach($result['data'] as $conversation) {													
							$source_found = false;
							if (isset($conversation['participants']['data']) && is_array($conversation['participants']['data']) && !empty($conversation['participants']['data'])) {
								foreach($conversation['participants']['data'] as $participant) {
									if ($participant['id'] == $input_params['source_id']) {
										$source_found = true;
										break;
									}
								}
							}
							if ($source_found) {
								$url = 'https://graph.facebook.com/v2.8/'.$conversation['id'].'/messages';
								$params = [
									'access_token' => $page['token'],
									'message' => $input_params['message']
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
								if (isset($result['id']) && $result['id'] != '') {
									$return = $result['id'];
								}								
								break;
							}
						}
					}
				}
			}			
		}
		return $return;
	}
	
}
?>