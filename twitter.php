<?
/**
 * Plugin Name: Twitter For Revenginet
 * Plugin URI: http://revengi.net
 * Description: Adds Twitter posts to Revenginet
 * Version: 0.1
 * Author: Jesse Conner
 * Author URI: http://m-i-rite.com
 * License: GPL2
 */
function tweets()
	{
		
		class TwitterAPIExchange
		{
			private $oauth_access_token;
			private $oauth_access_token_secret;
			private $consumer_key;
			private $consumer_secret;
			private $postfields;
			private $getfield;
			protected $oauth;
			public $url;

			/**
		* Create the API access object. Requires an array of settings::
		* oauth access token, oauth access token secret, consumer key, consumer secret
		* These are all available by creating your own application on dev.twitter.com
		* Requires the cURL library
		*
		* @param array $settings
		*/
			public function __construct(array $settings)
			{
				if (!in_array('curl', get_loaded_extensions()))
				{
					throw new Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
				}
				
				if (!isset($settings['oauth_access_token'])
					|| !isset($settings['oauth_access_token_secret'])
					|| !isset($settings['consumer_key'])
					|| !isset($settings['consumer_secret']))
				{
					throw new Exception('Make sure you are passing in the correct parameters');
				}

				$this->oauth_access_token = $settings['oauth_access_token'];
				$this->oauth_access_token_secret = $settings['oauth_access_token_secret'];
				$this->consumer_key = $settings['consumer_key'];
				$this->consumer_secret = $settings['consumer_secret'];
			}
			
			/**
		* Set postfields array, example: array('screen_name' => 'J7mbo')
		*
		* @param array $array Array of parameters to send to API
		*
		* @return TwitterAPIExchange Instance of self for method chaining
		*/
			public function setPostfields(array $array)
			{
				if (!is_null($this->getGetfield()))
				{
					throw new Exception('You can only choose get OR post fields.');
				}
				
				if (isset($array['status']) && substr($array['status'], 0, 1) === '@')
				{
					$array['status'] = sprintf("\0%s", $array['status']);
				}
				
				$this->postfields = $array;
				
				return $this;
			}
			
			/**
		* Set getfield string, example: '?screen_name=J7mbo'
		*
		* @param string $string Get key and value pairs as string
		*
		* @return \TwitterAPIExchange Instance of self for method chaining
		*/
			public function setGetfield($string)
			{
				if (!is_null($this->getPostfields()))
				{
					throw new Exception('You can only choose get OR post fields.');
				}
				
				$search = array('#', ',', '+', ':');
				$replace = array('%23', '%2C', '%2B', '%3A');
				$string = str_replace($search, $replace, $string);
				
				$this->getfield = $string;
				
				return $this;
			}
			
			/**
		* Get getfield string (simple getter)
		*
		* @return string $this->getfields
		*/
			public function getGetfield()
			{
				return $this->getfield;
			}
			
			/**
		* Get postfields array (simple getter)
		*
		* @return array $this->postfields
		*/
			public function getPostfields()
			{
				return $this->postfields;
			}
			
			/**
		* Build the Oauth object using params set in construct and additionals
		* passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
		*
		* @param string $url The API url to use. Example: https://api.twitter.com/1.1/search/tweets.json
		* @param string $requestMethod Either POST or GET
		* @return \TwitterAPIExchange Instance of self for method chaining
		*/
			public function buildOauth($url, $requestMethod)
			{
				if (!in_array(strtolower($requestMethod), array('post', 'get')))
				{
					throw new Exception('Request method must be either POST or GET');
				}
				
				$consumer_key = $this->consumer_key;
				$consumer_secret = $this->consumer_secret;
				$oauth_access_token = $this->oauth_access_token;
				$oauth_access_token_secret = $this->oauth_access_token_secret;
				
				$oauth = array(
					'oauth_consumer_key' => $consumer_key,
					'oauth_nonce' => time(),
					'oauth_signature_method' => 'HMAC-SHA1',
					'oauth_token' => $oauth_access_token,
					'oauth_timestamp' => time(),
					'oauth_version' => '1.0'
				);
				
				$getfield = $this->getGetfield();
				
				if (!is_null($getfield))
				{
					$getfields = str_replace('?', '', explode('&', $getfield));
					foreach ($getfields as $g)
					{
						$split = explode('=', $g);
						$oauth[$split[0]] = $split[1];
					}
				}
				
				$base_info = $this->buildBaseString($url, $requestMethod, $oauth);
				$composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
				$oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
				$oauth['oauth_signature'] = $oauth_signature;
				
				$this->url = $url;
				$this->oauth = $oauth;
				
				return $this;
			}
			
			/**
		* Perform the acual data retrieval from the API
		*
		* @param boolean $return If true, returns data.
		*
		* @return json If $return param is true, returns json data.
		*/
			public function performRequest($return = true)
			{
				if (!is_bool($return))
				{
					throw new Exception('performRequest parameter must be true or false');
				}
				
				$header = array($this->buildAuthorizationHeader($this->oauth), 'Expect:');
				
				$getfield = $this->getGetfield();
				$postfields = $this->getPostfields();

				$options = array(
					CURLOPT_HTTPHEADER => $header,
					CURLOPT_HEADER => false,
					CURLOPT_URL => $this->url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_SSL_VERIFYPEER => false
				);

				if (!is_null($postfields))
				{
					$options[CURLOPT_POSTFIELDS] = $postfields;
				}
				else
				{
					if ($getfield !== '')
					{
						$options[CURLOPT_URL] .= $getfield;
					}
				}

				$feed = curl_init();
				curl_setopt_array($feed, $options);
				$json = curl_exec($feed);
				curl_close($feed);

				if ($return) { return $json; }
			}
			
			/**
		* Private method to generate the base string used by cURL
		*
		* @param string $baseURI
		* @param string $method
		* @param string $params
		*
		* @return string Built base string
		*/
			private function buildBaseString($baseURI, $method, $params)
			{
				$return = array();
				ksort($params);
				
				foreach($params as $key=>$value)
				{
					$return[] = "$key=" . $value;
				}
				
				return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $return));
			}
			
			/**
		* Private method to generate authorization header used by cURL
		*
		* @param array $oauth Array of oauth data generated by buildOauth()
		*
		* @return string $return Header used by cURL for request
		*/
			private function buildAuthorizationHeader($oauth)
			{
				$return = 'Authorization: OAuth ';
				$values = array();
				
				foreach($oauth as $key => $value)
				{
					$values[] = "$key=\"" . rawurlencode($value) . "\"";
				}
				
				$return .= implode(', ', $values);
				return $return;
			}

		}
		
		$settings = array(
		'oauth_access_token' => "53284017-uoqr01OdoYrKbSz2jQfMfebYFSRatP7cgzVuKMddg",
		'oauth_access_token_secret' => "awUTeJysGiAsvY6vTlnI7yIQgQnAxXJJwNzYAAwFi4",
		'consumer_key' => "BCrexZWyrTdEdoev4FfrVw",
		'consumer_secret' => "T7qUEn8AeHZH0M29nsC8zSlfBLxTcuHBpkZiDHmQ"
		);	
		
		$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
		$getfield = "?screen_name=m_i_rite&count=20";
		$requestMethod = 'GET';
		$twitter = new TwitterAPIExchange($settings);
		$output= $twitter->setGetfield($getfield)
             ->buildOauth($url, $requestMethod)
             ->performRequest();
		
		$output=json_decode($output,true);
	

		if(isset($output['error']))
		{
			echo $output['error'];
		}
		else
		{
			foreach($output as $status)
			{
				foreach ($status['entities']['urls'] as $url) 
				{
				   $status['text']=str_replace($url['url'], '<a href="' . $url['url'] . '">' . $url['url'] . '</a>',$status['text']);
				}
				
                do_action("revenginet_add", $status['text'], $status['created_at'], 0);
                
			}
		}
	}
	
	function time_elapsed_string($ptime) {
    $etime = time() - strtotime($ptime);
    
    if ($etime < 1) {
        return '0 seconds';
    }
    
    $a = array( 12 * 30 * 24 * 60 * 60  =>  'year',
                30 * 24 * 60 * 60       =>  'month',
                24 * 60 * 60            =>  'day',
                60 * 60                 =>  'hour',
                60                      =>  'minute',
                1                       =>  'second'
                );
    
    foreach ($a as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's ago' : '');
        }
		}
	}	
	
    add_action('revenginet_update', 'tweets');

?>


