<?php

class OstrovokEchannelAPIClient {

	protected $_auth_token = null;
	protected $_private_token = null;

	public function __construct($endpoint, $auth_token, $private_token) {
		$this->_endpoint = $endpoint;
		$this->_auth_token = $auth_token;
		$this->_private_token = $private_token;
	}

	private function __signaturelizer($data) {
		$is_list = false;
		if (is_array($data)) {
			if (count($data) > 0) {
				if (is_int($data[0])) {
					$is_list = true;
				}
			}
		}

		if (is_array($data) && !$is_list) {
			ksort($data);
			$tmp = array();
			foreach($data as $key => $value) {
				$tmp[] = array($this->__signaturelizer($key), $this->__signaturelizer($data[$key]));
			}
			$result = array();
			foreach($tmp as $key => $value) {
				$result[] = implode("=", $value);
			}
			return implode(";", $result);
		} elseif (is_array($data) && $is_list) {
			$result = array();
			foreach($data as $value) {
				$result[] = $this->__signaturelizer($value);
			}
			$result = implode(";", $result);
			if (count($data) > 1) {
				$result = ("[" . $result . "]");
			}
			return $result;
		} elseif (is_bool($data)) {
			return $data ? "true" : "false";
		}

		return (string)$data;
	}

	private function __getSignature(array $data, $private) {
		$data['private'] = $private;
		return md5($this->__signaturelizer($data));
	}

	private function __callGET($method_url, array $params) {
		$params["token"] = $this->_auth_token;
		$params["sign"] = $this->__getSignature($params, $this->_private_token);
		$final_url = $this->_endpoint . $method_url . "?" . http_build_query($params) . "&";
		return file_get_contents($final_url);
	}

	private function __callPUT($method_url, array $params) {
		$GET_params = array();
		$sign_params = $params;
		$sign_params["token"] = $this->_auth_token;
		$GET_params["token"] = $this->_auth_token;
		$GET_params["sign"] = $this->__getSignature($sign_params, $this->_private_token);

		$final_url = $this->_endpoint . $method_url . "?" . http_build_query($GET_params);
		$curl = curl_init($final_url);

		$data_json = json_encode($params);

		curl_setopt($curl, CURLOPT_RETURN_TRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOM_REQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($curl, CURLOP_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_json),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	private function __callPOST($method_url, array $params) {
		$GET_params = array();
		$sign_params = $params;
		$sign_params["token"] = $this->_auth_token;
		$GET_params["token"] = $this->_auth_token;
		$GET_params["sign"] = $this->__getSignature($sign_params, $this->_private_token);

		$final_url = $this->_endpoint . $method_url . "?" . http_build_query($GET_params);
		$curl = curl_init($final_url);

		$data_json = json_encode($params);

		curl_setopt($curl, CURLOPT_RETURN_TRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_json);
		curl_setopt($curl, CURLOP_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_json),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}	

	public function getHotels(array $params = array()) {
		return $this->__callGET("hotels/", $params);
	}

	public function getRoomCategories(array $params = array()) {
		return $this->__callGET("room_categories/", $params);		
	}

	public function getMealPlans(array $params = array()) {
		return $this->__callGET("meal_plans/", $params);
	}	

	public function getOrders(array $params = array()) {
		return $this->__callGET("orders/", $params);
	}

	public function getBookings(array $params = array()) {
		return $this->__callGET("bookings/", $params);
	}

	public function getRNA($plan_date_start_at, $plan_date_end_at, array $params = array()) {
		$params["plan_date_start_at"] = $plan_date_start_at;
		$params["plan_date_end_at"] = $plan_date_end_at;
		return $this->__callGET("rna/", $params);
	}

	public function updateRNA(array $params = array()) {
		return $this->__callPUT("rna/", $params);
	}

	public function createRNA(array $params = array()) {
		return $this->__callPOST("rna/", $params);
	}

	public function getOccupancySettings($hotel, $room_category, 
		$plan_date_start_at, $plan_date_end_at, array $params = array()) {
		$params["hotel"] = $hotel;
		$params["room_category"] = $room_category;
		$params["plan_date_start_at"] = $plan_date_start_at;
		$params["plan_date_end_at"] = $plan_date_end_at;
		return $this->__callGET("occupancy_settings_plan/", $params);
	}

	public function updateOccupancySettings($id, array $params = array()) {
		$params["id"] = $id;
		return $this->__callPUT("occupancy_settings_plan/", $params);
	}

	public function createOccupancySettings(array $params = array()) {
		return $this->__callPOST("occupancy_settings_plan/", $params);
	}

	public function getRatePlans(array $params = array()) {
		return $this->__callGET("rate_plans/", $params);
	}

	public function createRatePlan(array $rate_plan_params) {
		return $this->__callPOST("rate_plans/", $rate_plan_params);
	}

	public function updateRatePlan($id, array $rate_plan_params) {
		$rate_plan_params["id"] = $id;
		return $this->__callPUT("rate_plans/", $rate_plan_params);
	}
}

/**
 Usage:
   $api_client = new OstrovokEchannelAPIClient("https://extratest.ostrovok.ru/", $auth_token, $private_token);
   print $api_client->getHotels(); // returns json string
 */
