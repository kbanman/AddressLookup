<?php

/**
 * Address Lookup Class
 *
 * Perform Reverse Address, Reverse Phone, Postal Code and Reverse Geo-coding
 * (Currently only supports Canadian addresses)
 *
 * @package		AddressLookup
 * @author		Kelly Banman
 * @license		http://philsturgeon.co.uk/code/dbad-license
 * @link		https://github.com/kbanman/AddressLookup
 */

class AddressLookup {

	protected $dom;	 // Instance of DomDocument
	protected $connection_handle; // cURL instance
	public $google_api_key;

	protected $urls = array(
		'wp_base' => 'http://www.whitepages.com',
		'wp_base_mobile' => 'http://m.whitepages.com',
		'wp_reverse_phone' => '/ReversePhone.aspx',
		'cp_base' => 'http://www.canadapost.ca',
		'cp_reverse_postalcode' => '/cpotools/apps/fpc/personal/findAnAddress',
	);

	public function __construct()
	{
		$this->dom = new DOMDocument;
		$this->dom->preserveWhiteSpace = false;
		$this->dom->validateOnParse = true;
		// Do our own error handling
		libxml_use_internal_errors(true);
		$this->connect();
	}

	public function reverse_address($street, $city, $province)
	{
		$this->mobile_useragent(true);
		$url = $this->urls['wp_base'].'/search/ReverseAddress?street='.rawurlencode($street).'&where='.$city.'+'.$province;
		$response = file_get_contents($url);
		if ( ! $this->dom->loadHTML($response))
		{
			die('something bad happened');
		}
		$results = $this->dom->getElementById('listings')->childNodes;

		if ( ! count($results))
		{
			return false;
		}
	
		// Load the first result (TODO: Support multiple results)
		$url = $this->urls['wp_base'].$results->item(1)->getAttribute('data-href');
		$response = file_get_contents($url);
		$this->dom->loadHTML($response);
		$doc = new DOMXpath($this->dom);

		$name_first = $doc->query("//span[@class='given-name']")->item(0);
		$name_last = $doc->query("//span[@class='family-name']")->item(0);
		$street = $doc->query("//span[@class='street-address']")->item(0);
		$city = $doc->query("//span[@class='locality']")->item(0);
		$province = $doc->query("//span[@class='region']")->item(0);
		$postal = $doc->query("//span[@class='postal-code']")->item(0);
		$phone = $doc->query("//p[contains(@class,'landline')]")->item(0);

		$result = array(
			'name_first' => $name_first->textContent,
			'name_last' => $name_last->textContent,
			'address' => trim($street->previousSibling->textContent).' '.$street->textContent,
			'city' => $city->textContent,
			'province' => $province->textContent,
			'postalcode' => $postal->textContent,
			'phone' => $this->digitize_phone($phone->textContent),
		);

		return array($result);
	}

	public function reverse_phone($num)
	{
		$this->mobile_useragent(true);
		$url = $this->urls['wp_base_mobile'].$this->urls['wp_reverse_phone'];
		$response = $this->request($url.'?emvAD=320x480');
		preg_match('/aspx\;jsessionid=(.*?)(\?.*)?"/', $response, $matches);
		$sid = $matches[1];
		preg_match_all('/name="([a-zA-Z]+)" .*?value="(.*?)"/', $response, $matches);
		$data = array_combine($matches[1], $matches[2]);
		$data['PhoneNo'] = $this->digitize_phone($num);
		curl_setopt($this->connection_handle, CURLOPT_FOLLOWLOCATION, false);
		$response = $this->request($url.';jsessionid='.$sid.'?emvAD=320x480', $data);
		curl_setopt($this->connection_handle, CURLOPT_FOLLOWLOCATION, true);
		$url = 'http://m.whitepages.com/Results.aspx'.';jsessionid='.$sid;
		$data = array(
			'Mode' => 'results',
			'Ref' => 'ReversePhone',
			'sid' => $data['sid'],
			'emvAD' => '320x480',
			'emvcc' => '0',
		);
		$response = $this->request($url, $data, 'GET');
		$this->dom->loadHTML($response);
		$listings = $this->dom->getElementById('ListingsAll')->getElementsByTagName('td');
		$results = array();
		foreach ($listings as $listing)
		{
			list($name_first, $name_last) = $this->split_string($listing->childNodes->item(0)->textContent);
			$result = array(
				'name_first' => $name_first,
				'name_last' => $name_last,
				'phone' => $this->digitize_phone($num),
			);
			foreach ($listing->childNodes as $node)
			{
				if ( ! empty($node->tagName)) continue;

				if (count($data = explode(',', $node->textContent)) > 1)
				{
					$result['city'] = $data[0];
					list($prov, $postal) = $this->split_string($data[1]);
					$result['province'] = $prov;
					$result['postalcode'] = $postal;
				}
				else
				{
					$result['address'] = trim($node->textContent);
				}
			}
			$results[] = $result;
		}

		return $results;
	}

	public function reverse_postalcode($postalcode)
	{
		$this->mobile_useragent(false);
		// Get the CSFR values from the form
		$url = $this->urls['cp_base'].$this->urls['cp_reverse_postalcode'];
		$data = array();
		$response = $this->request($url);
		$this->dom->loadHTML($response);
		$form = $this->dom->getElementById('fpcFindAnAddress:reverseSearch');
		foreach ($form->getElementsByTagName('input') as $input)
		{
			$data[$input->getAttribute('name')] = $input->getAttribute('value');
		}
		$data['postalCode'] = $postalcode;
		$response = $this->request($this->urls['cp_base'].$form->getAttribute('action'), $data);
		$this->dom->loadHTML($response);
		$table = $this->dom->getElementById('listPostalCodeResult:fpcResultsTable:tbody_element');
		$results = array();
		if (is_null($table))
		{
			return $results;
		}
		$rows = $table->getElementsByTagName('tr');
		$columns = array('building', 'number', 'delivery_mode', 'street', 'suite', 'city', 'province', 'postalcode');
		foreach ($rows as $row)
		{
			$cells = $row->getElementsByTagName('td');
			$result = array();
			foreach ($columns as $i => $col)
			{
				$cell = $cells->item($i);
				if ($col == 'number') {
					$children = $cell->childNodes;
					$number = explode('-', $children->item(0)->textContent);
					$result['number_start'] = $number[0];
					$result['number_end'] = $number[1];
					$result['odd_even'] = strtolower($children->item(2)->textContent);
				} else {
					$result[$col] = trim($cell->textContent);
				}
			}
			$results[] = $result;
		}
		return $results;
	}

	public function reverse_geocode($street, $city, $province)
	{
		$statae = array(
			200 => 'Success',
			400 => 'Bad Request',
			500 => 'Server Error',
			601 => 'Missing Query',
			602 => 'Unknown Address',
			610 => 'Bad Key',
			620 => 'Too Many Queries',
		);
		$query = array(
			'q' => rawurlencode($street.' '.$city.' '.$province),
			'output' => 'csv',
			'sensor' => 'false',
			'key' => $this->google_api_key,
		);
		$url = 'http://maps.google.com/maps/geo?'.http_build_query($query);
		$response= explode(',',file_get_contents($url));
		$output = array(
			'status' => (int)$response[0],
			'status_message' => $statae[(int)$response[0]],
			'accuracy' => (int)$response[1],
			'coords' => array(
				'lat' => (float)$response[2],
				'lng' => (float)$response[3],
			),
			'street' => $street,
			'city' => $city,
			'province' => $province,
		);
		return $output;
	}

	protected function split_string($str)
	{
		$str = trim($str);
		$split = strpos($str, ' ');
		if ($split === false)
		{
			return array('', $str);
		}
		return array(
			substr($str, 0, $split),
			substr($str, $split+1),
		);
	}

	protected function digitize_phone($phone)
	{
		return preg_replace('/\D/', '', $phone);
	}

	protected function mobile_useragent($bool = true)
	{
		$ua = $bool ? 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7A341 Safari/528.16' : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:5.0) Gecko/20100101 Firefox/5.0';
		curl_setopt($this->connection_handle, CURLOPT_USERAGENT, $ua);
	}

	protected function connect()
	{
		// If there is no connection
		if ( ! is_resource($this->connection_handle)) {
			// Try to create one
			if ( ! $this->connection_handle = curl_init()) {
				trigger_error('Could not start new CURL instance');
				$this->error = true;
				return false;
			}
		}

		curl_setopt_array($this->connection_handle, array(
			CURLOPT_HEADER => false,
			CURLOPT_POST => true,
			CURLOPT_TIMEOUT => 100,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FRESH_CONNECT => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
			CURLOPT_COOKIEJAR => tempnam('/tmp', 'CURLCOOKIE'),
			CURLOPT_COOKIEFILE => tempnam('/tmp', 'CURLCOOKIEFILE'),
		));

		return true;
	}

	/**
	* Close the current cURL session
	*
	* @access protected
	* @return boolean
	*/
	protected function disconnect()
	{
		if (is_resource($this->connection_handle))
		{
			curl_close($this->connection_handle);
		}
	}

	/**
	* Send a request through the current cURL session
	*
	* @access protected
	* @param string      The URL to send it to
	* @param array       The data to be POSTed or attached as GET query parameters
	* @return string     or false on error
	*/
	protected function request($url, $data = array(), $method = 'POST')
	{
		// Set the url to send data to
		curl_setopt($this->connection_handle, CURLOPT_URL, $url);

		if ($method != 'POST')
		{
			curl_setopt($this->connection_handle, CURLOPT_HTTPGET, true);
			curl_setopt($this->connection_handle, CURLOPT_URL, $url.'?'.http_build_query($data));
		}
		else
		{
			curl_setopt($this->connection_handle, CURLOPT_POST, true);
			curl_setopt($this->connection_handle, CURLOPT_POSTFIELDS, http_build_query($data));
		}

		// Send data and grab the result
		$response = curl_exec($this->connection_handle);
		if ($response === false)
		{
			trigger_error(curl_error($this->connection_handle));
			$this->error = true;
			return false;
		}

		return $response;
	}

	protected function google_api_key($key)
	{
		$this->google_api_key = $key;
	}
}
