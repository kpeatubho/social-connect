<?php
namespace Social\Providers;
require_once __DIR__."/../ProviderAbstract.php";

class Vk extends \Social\ProviderAbstract {
	
	const SCOPE_FRIENDS = 'friends';
	const SCOPE_PHOTOS = 'photos';
	const SCOPE_AUDIO = 'audio';
	const SCOPE_VIDEO = 'video';
	const SCOPE_PAGES = 'pages';
	const SCOPE_STATUS = 'status';
	const SCOPE_NOTES = 'notes';
	const SCOPE_MESSAGES = 'messages';
	const SCOPE_WALL = 'wall';
	const SCOPE_ADS = 'ads';
	const SCOPE_OFFLINE = 'offline';
	const SCOPE_DOCS = 'docs';
	const SCOPE_GROUPS = 'groups';
	const SCOPE_NOTIFICATIONS = 'notifications';
	const SCOPE_STATS = 'stats';
	const SCOPE_EMAIL = 'email';
	const SCOPE_MARKET = 'market';
	
	protected $_credentials = [
		'application_id' => '',
		'secret_key' => '',
		'redirect_url' => ''
	];
	
	private $_items_per_request = 100;
	private $_messages_per_request = 200;
	
	public function redirectToAuth($scopes = []) {
		$url = 'https://oauth.vk.com/authorize';
		$params = [
			'client_id' => $this->_credentials['application_id'],
			'redirect_uri' => $this->_credentials['redirect_url'],
			'display' => 'page',
			'scope' => implode(',', $scopes),
			'response_type' => 'code',
			'v' => '5.60',
			'revoke' => 1
		];
		$url .= '?' . http_build_query($params);
		header('Location: '.$url);
	}
	
	public function retriveToken($code = '') {
		$return = null;
		if (isset($_REQUEST['code'])) {
			$url = 'https://oauth.vk.com/access_token';
			$params = [
				'client_id' => $this->_credentials['application_id'],
				'client_secret' => $this->_credentials['secret_key'],
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
		$user_ids = [];
		$group_ids = [];
		if (isset($input_params['source_id']) && is_array($input_params['source_id'])) {
			foreach($input_params['source_id'] as $source_id) {
				if ($source_id > 0) {
					$user_ids[] = $source_id;
				} elseif ($source_id < 0) {
					$group_ids[] = abs($source_id);
				}
			}
		}
		if (count($user_ids) > 0) {
			$url = 'https://api.vk.com/method/users.get';
			$params = [
				'access_token' => $this->_token,
				'v' => '5.60',
				'user_ids' => implode(',', $user_ids),
				'fields' => 'photo_50,frst_name,last_name,bdate,country,city,contacts,connections'
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
			if (isset($result['response']) && is_array($result['response'])) {
				foreach($result['response'] as $item) {
					$birthday = '0000-00-00';
					if (isset($item['bdate'])) {
						$temp = explode(".", $item['bdate']);
						if (count($temp) == 3) {
							$birthday = date('Y-m-d', mktime(0, 0, 0, $temp[1], $temp[0], $temp[2]));
						} elseif (count($temp) == 2) {
							$birthday = date('Y-m-d', mktime(0, 0, 0, $temp[1], $temp[0], 1900));
						} elseif (count($temp) == 1) {
							$birthday = date('Y-m-d', mktime(0, 0, 0, 1, 1, $temp[0]));
						}
					}
					$return[] = [
						'source_id' => $item['id'],
						'name' => trim($item['first_name'] . ' ' . $item['last_name']),
						'image' => isset($item['photo_50'])?$item['photo_50']:'',
						'birthday' => $birthday,
						'country' => isset($item['country']['title'])?$item['country']['title']:'',
						'city' => isset($item['city']['title'])?$item['city']['title']:'',
						'contacts' => [
							'mobile_phone' => isset($item['contacts']['mobile_phone'])?$item['contacts']['mobile_phone']:'',
							'home_phone' => isset($item['contacts']['home_phone'])?$item['contacts']['home_phone']:''
						],
						'connections' => isset($item['connections'])?$item['connections']:[],
						'type' => 'user'
					];				
				}
			}
		}		
		if (count($group_ids) > 0) {
			$url = 'https://api.vk.com/method/groups.getById';
			$params = [
				'access_token' => $this->_token,
				'v' => '5.60',
				'group_ids' => implode(',', $group_ids),
				'extended' => 1,
				'fields' => 'id,name,photo_50'
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
			if (isset($result['response']) && is_array($result['response'])) {
				foreach($result['response'] as $item) {
					$return[] = [
						'source_id' => '-'.$item['id'],
						'name' => $item['name'],
						'image' => isset($item['photo_50'])?$item['photo_50']:'',
						'birthday' => '0000-00-00',
						'country' => '',
						'city' => '',
						'contacts' => [
							'mobile_phone' => '',
							'home_phone' => ''
						],
						'connections' => [],
						'type' => 'group'
					];
				}
			}
		}
		if (count($user_ids) == 0 && count($group_ids) == 0) {
			$user_id = 0;
			$url = 'https://api.vk.com/method/users.get';
			$params = [
				'access_token' => $this->_token,
				'v' => '5.60',
				'fields' => 'photo_50,frst_name,last_name,bdate,country,city,contacts,connections'
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
			if (isset($result['response'][0])) {
				$birthday = '0000-00-00';
				if (isset($result['response'][0]['bdate'])) {
					$temp = explode(".", $result['response'][0]['bdate']);
					if (count($temp) == 3) {
						$birthday = date('Y-m-d', mktime(0, 0, 0, $temp[1], $temp[0], $temp[2]));
					} elseif (count($temp) == 3) {
						$birthday = date('Y-m-d', mktime(0, 0, 0, $temp[1], $temp[0], 1900));
					} elseif (count($temp) == 1) {
						$birthday = date('Y-m-d', mktime(0, 0, 0, 1, 1, $temp[0]));
					}
				}
				$return[] = [
					'source_id' => $result['response'][0]['id'],
					'name' => trim($result['response'][0]['first_name'] . ' ' . $result['response'][0]['last_name']),
					'image' => isset($result['response'][0]['photo_50'])?$result['response'][0]['photo_50']:'',
					'birthday' => $birthday,
					'country' => isset($result['response'][0]['country']['title'])?$result['response'][0]['country']['title']:'',
					'city' => isset($result['response'][0]['city']['title'])?$result['response'][0]['city']['title']:'',
					'contacts' => [
						'mobile_phone' => isset($result['response'][0]['contacts']['mobile_phone'])?$result['response'][0]['contacts']['mobile_phone']:'',
						'home_phone' => isset($result['response'][0]['contacts']['home_phone'])?$result['response'][0]['contacts']['home_phone']:''
					],
					'connections' => isset($result['response'][0]['connections'])?$result['response'][0]['connections']:[],
					'type' => 'user'
				];
				$user_id = $result['response'][0]['id'];
			}
			if ($user_id > 0) {
				$url = 'https://api.vk.com/method/groups.get';
				$params = [
					'access_token' => $this->_token,
					'v' => '5.60',
					'user_id' => $user_id,
					'filter' => 'moder',
					'extended' => 1,
					'fields' => 'id,name,photo_50'
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
				if (isset($result['response']['items']) && is_array($result['response']['items'])) {
					foreach($result['response']['items'] as $item) {
						$return[] = [
							'source_id' => '-'.$item['id'],
							'name' => $item['name'],
							'image' => isset($item['photo_50'])?$item['photo_50']:'',
							'birthday' => '0000-00-00',
							'country' => '',
							'city' => '',
							'contacts' => [
								'mobile_phone' => '',
								'home_phone' => ''
							],
							'connections' => [],
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
				$offset = 0;
				while($process_getting) {
					$url = 'https://api.vk.com/method/wall.get';
					$params = [
						'access_token' => $this->_token,
						'v' => '5.60',
						'owner_id' => $source,
						'count' => $this->_items_per_request,
						'offset' => $offset
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
					$process_getting = false;
					if (isset($result['response']['items']) && isset($result['response']['count'])) {
						if ($result['response']['count'] > $offset + count($result['response']['items'])) {
							$offset += $this->_items_per_request;
							$process_getting = true;
						}
						foreach($result['response']['items'] as $item) {
							if (empty($from) || in_array($item['from_id'], $from)) {
								$image = '';
								if (trim($item['text'] == "") && isset($item['copy_history']) && !empty($item['copy_history'])) {
									$item['text'] = $item['copy_history'][0]['text'];
									if (count($item['copy_history'][0]['attachments']) > 0) {
										foreach($item['copy_history'][0]['attachments'] as $attachment) {
											if ($attachment['type'] == 'photo' || $attachment['type'] == 'video') {
												$image = $attachment[$attachment['type']]['photo_130'];
											} elseif ($attachment['type'] == 'link' && isset($attachment[$attachment['type']]['photo']['photo_130'])) {
												$image = $attachment[$attachment['type']]['photo']['photo_130'];
											}
										}
									}
								} else {
									if (count($item['attachments']) > 0) {
										foreach($item['attachments'] as $attachment) {
											if ($attachment['type'] == 'photo' || $attachment['type'] == 'video') {
												$image = $attachment[$attachment['type']]['photo_130'];
											} elseif ($attachment['type'] == 'link' && isset($attachment[$attachment['type']]['photo']['photo_130'])) {
												$image = $attachment[$attachment['type']]['photo']['photo_130'];
											}
										}
									}
								}
								if (trim($item['text']) != "" || $image != "") {
									$return[] = [
										'post_id' => $item['id'],
										'from_id' => $item['from_id'],
										'owner_id' => $item['owner_id'],
										'date' => date("Y-m-d H:i:s", $item['date']),
										'text' => preg_replace("/\[([^\|]+)\|([^\]]+)\]/sui", "$2", $item['text']),
										'image' => $image,
										'comments_count' => $item['comments']['count']
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
		$process_getting = true;
		$offset = 0;
		while($process_getting) {
			$url = 'https://api.vk.com/method/wall.getComments';
			$params = [
				'access_token' => $this->_token,
				'v' => '5.60',
				'owner_id' => isset($input_params['owner_id'])?$input_params['owner_id']:0,
				'post_id' => isset($input_params['post_id'])?$input_params['post_id']:0,
				'count' => $this->_items_per_request,
				'offset' => $offset,
				'sort' => 'asc'
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
			$process_getting = false;
			if (isset($result['response']['items']) && isset($result['response']['count'])) {
				if ($result['response']['count'] > $offset + count($result['response']['items'])) {
					$offset += $this->_items_per_request;
					$process_getting = true;
				}
				foreach($result['response']['items'] as $item) {
					$appeal_to = [
						'name' => '',
						'id' => ''
					];
					if (preg_match("/\[([^\|]+)\|([^\]]+)\]/sui", $item['text'], $matches)) {
						if (stripos($matches[1], 'id') !== false) {
							$appeal_to['name'] = $matches[2];
							$appeal_to['id'] = str_replace('id', '', $matches[1]);
						} elseif (stripos($matches[1], 'club') !== false) {
							$appeal_to['name'] = $matches[2];
							$appeal_to['id'] = str_replace('club', '-', $matches[1]);
						}
					}
					$return[] = [
						'comment_id' => $item['id'],
						'from_id' => $item['from_id'],
						'date' => date("Y-m-d H:i:s", $item['date']),
						'text' => preg_replace("/\[([^\|]+)\|([^\]]+)\]/sui", "$2", $item['text']),
						'appeal_to' => $appeal_to
					];
				}
			}
		}
		return $return;
	}
	
	public function createComment($input_params = []){
		$return = null;
		if (isset($input_params['post_id']) && $input_params['post_id'] > 0 && isset($input_params['message']) && trim($input_params['message']) != "") {
			$url = 'https://api.vk.com/method/wall.createComment';
			$appeal_to = "";
			if (isset($input_params['appeal_to']['id']) && $input_params['appeal_to']['id'] != 0 && isset($input_params['appeal_to']['name']) && trim($input_params['appeal_to']['name']) != "") {
				if ($input_params['appeal_to']['id'] > 0) {
					$appeal_to = '[id'.$input_params['appeal_to']['id'].'|'.$input_params['appeal_to']['name'].'], ';
				} else {
					$appeal_to = '[club'.abs($input_params['appeal_to']['id']).'|'.$input_params['appeal_to']['name'].'], ';
				}
			}
			$params = [
				'access_token' => $this->_token,
				'v' => '5.60',
				'owner_id' => isset($input_params['owner_id'])?$input_params['owner_id']:0,
				'post_id' => $input_params['post_id'],
				'from_group' => isset($input_params['from_group']) && ($input_params['from_group'] == true || $input_params['from_group'] == 1) && isset($input_params['owner_id']) && $input_params['owner_id'] < 0?1:0,
				'message' => $appeal_to.$input_params['message'],
				'reply_to_comment' => isset($input_params['reply_to_comment'])?$input_params['reply_to_comment']:0,
				'guid' => md5(microtime())
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
			if (isset($result['response']['comment_id']) && $result['response']['comment_id'] > 0) {
				$return = $result['response']['comment_id'];
			}
		}
		return $return;
	}
	
	public function deleteComment($input_params = []){
		$return = false;
		if (isset($input_params['comment_id']) && $input_params['comment_id'] > 0) {
			$url = 'https://api.vk.com/method/wall.deleteComment';			
			$params = [
				'access_token' => $this->_token,
				'v' => '5.60',
				'owner_id' => isset($input_params['owner_id'])?$input_params['owner_id']:0,
				'comment_id' => $input_params['comment_id']
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
			if (isset($result['response']) && $result['response'] == 1) {
				$return = true;
			}
		}
		return $return;
	}
	
	public function getMessages($source_id = 0){
		$return = [];
		if ($source_id != 0) {
			$process_getting = true;
			$offset = 0;
			while($process_getting) {	
				$url = 'https://api.vk.com/method/messages.getDialogs';
				$params = [
					'access_token' => $this->_token,
					'v' => '5.60',
					'count' => $this->_messages_per_request,
					'offset' => $offset
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
				$process_getting = false;
				if (isset($result['response']['items']) && isset($result['response']['count'])) {
					if ($result['response']['count'] > $offset + count($result['response']['items'])) {
						$offset += $this->_messages_per_request;
						$process_getting = true;
					}
					foreach($result['response']['items'] as $item) {
						if (!isset($item['message']['chat_id']) && $item['message']['user_id'] == $source_id) {
							$process_getting = false;
							$process_getting_history = true;
							$offset_history = 0;
							while($process_getting_history) {								
								$url = 'https://api.vk.com/method/messages.getHistory';			
								$params = [
									'access_token' => $this->_token,
									'v' => '5.60',
									'user_id' => $item['message']['user_id'],
									'rev' => 1,
									'count' => $this->_messages_per_request,
									'offset' => $offset_history									
								];
								$url .= '?' . http_build_query($params);
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
								if (isset($result_history['response']['items']) && isset($result_history['response']['count'])) {
									if ($result_history['response']['count'] > $offset_history + count($result_history['response']['items'])) {
										$offset_history += $this->_messages_per_request;
										$process_getting_history = true;
									}
									foreach($result_history['response']['items'] as $item_history) {
										$return[] = [
											'message_id' => $item_history['id'],
											'from_id' => $item_history['from_id'],
											'date' => date("Y-m-d H:i:s", $item_history['date']),
											'message' => $item_history['body'],
											'read' => $item_history['read_state']
										];
									}
								}
							}
						}						
					}
				}
			}
		}
		return $return;
	}
	
	public function sendMessage($input_params = []){
		$return = null;
		if (isset($input_params['source_id']) && $input_params['source_id'] != 0 && isset($input_params['message']) && trim($input_params['message']) != "") {
			$url = 'https://api.vk.com/method/messages.send';			
			$params = [
				'access_token' => $this->_token,
				'v' => '5.60',
				'user_id' => $input_params['source_id'],
				'random_id' => rand(1000000, 9999999),
				'message' => $input_params['message']
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
			if (isset($result['response']) && $result['response'] > 0) {
				$return = $result['response'];
			}
		}
		return $return;
	}
	
}
?>