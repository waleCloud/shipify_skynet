<?php

	namespace phpish\http;

	const USERAGENT = 'phpish/http';

	// TODO: https://github.com/sandeepshetty/wcurl/issues/1

	function client($base_uri='', $instance_request_headers=array(), $instance_curl_opts=array())
	{
		return function ($method_uri, $query='', $payload='', &$response_headers=array(), $request_headers_override=array(), $curl_opts_override=array()) use ($base_uri, $instance_request_headers, $instance_curl_opts)
		{
			list($method, $uri) = explode(' ', $method_uri, 2);
			$uri = ('/' == $uri[0]) ? rtrim($base_uri, '/').$uri : $uri;
			$request_headers = $request_headers_override + $instance_request_headers;
			$curl_opts = $curl_opts_override + $instance_curl_opts;
			return request("$method $uri", $query, $payload, $response_headers, $request_headers, $curl_opts);
		};
	}

	function request($method_uri, $query='', $payload='', &$response_headers=array(), $request_headers=array(), $curl_opts=array())
	{
		list($method, $uri) = explode(' ', $method_uri, 2);
		$url = _http_client_request_uri($uri, $query);
		$ch = curl_init($url);
		_http_client_setopts($ch, $method, $payload, $request_headers, $curl_opts);
		$response = curl_exec($ch);
		$curl_info = curl_getinfo($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		$headers = $request_headers;
		$request = compact('method', 'uri', 'query', 'headers', 'payload');

		if ($errno) throw new CurlException($error, $errno, $request);

		$header_size = $curl_info["header_size"];
		$msg_header = substr($response, 0, $header_size);
		$msg_body = substr($response, $header_size);
		$response_headers = _http_client_response_headers($msg_header);
		$http_status_message = $response_headers['http_status_message'];
		$http_status_code = $response_headers['http_status_code'];
		$response = array('headers'=>$response_headers, 'body'=>$msg_body);

		if ($http_status_code >= 400) throw new ResponseException($http_status_message, $http_status_code, $request, $response);

		$msg_body = (false !== strpos($response_headers['content-type'], 'application/json')) ? json_decode($msg_body, true) : $msg_body;

		return $msg_body;
	}

		function _http_client_request_uri($uri, $query)
		{
			if (empty($query)) return $uri;
			if (is_array($query)) return "$uri?".http_build_query($query);
			return "$uri?$query";
		}

		function _http_client_setopts($ch, $method, $payload, $request_headers_assoc, $curl_opts)
		{
			$default_curl_opts = array
			(
				CURLOPT_HEADER => true,
				CURLOPT_RETURNTRANSFER => true,
				# http://www.php.net/manual/en/function.curl-setopt.php#71313
				# CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS => 3,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_USERAGENT => USERAGENT,
				CURLOPT_CONNECTTIMEOUT => 30,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSLVERSION => 1
			);

			$request_headers = $request_headers_assoc_lower = array();
			foreach ($request_headers_assoc as $key=>$val) { $request_headers_assoc_lower[strtolower($key)] = $val; }
			$request_headers_assoc = $request_headers_assoc_lower;

			if ('GET' == $method)
			{
				$default_curl_opts[CURLOPT_HTTPGET] = true;
			}
			else
			{
				$default_curl_opts[CURLOPT_CUSTOMREQUEST] = $method;

				// This disables cURL's default 100-continue expectation
				if ('POST' == $method) $request_headers[] = 'Expect:';

				if (is_array($payload))
				{
					if (isset($request_headers_assoc['content-type']))
					{
						if (false !== strpos($request_headers_assoc['content-type'], 'application/x-www-form-urlencoded'))
						{
							$payload = http_build_query($payload);
						}
						elseif (false !== strpos($request_headers_assoc['content-type'], 'application/json'))
						{
							$payload = str_replace('\\/', '/', json_encode($payload));
						}
					}
					else
					{
						$payload = http_build_query($payload);
						$request_headers[] = 'Content-Type: application/x-www-form-urlencoded';
					}
				}

				if (!empty($payload)) $default_curl_opts[CURLOPT_POSTFIELDS] = $payload;
			}

			foreach ($request_headers_assoc as $key=>$val) { $request_headers[] = "$key: $val"; }
			if (!empty($request_headers)) $default_curl_opts[CURLOPT_HTTPHEADER] = $request_headers;

			$overriden_opts = $curl_opts + $default_curl_opts;
			foreach ($overriden_opts as $curl_opt=>$value) curl_setopt($ch, $curl_opt, $value);
		}

		function _http_client_response_headers($msg_header)
		{

			$multiple_headers = preg_split("/\r\n\r\n|\n\n|\r\r/", trim($msg_header));
			$last_response_header_lines = array_pop($multiple_headers);
			$response_headers = array();

			$header_lines = preg_split("/\r\n|\n|\r/", $last_response_header_lines);
			list(, $response_headers['http_status_code'], $response_headers['http_status_message']) = explode(' ', trim(array_shift($header_lines)), 3);
			foreach ($header_lines as $header_line)
			{
				list($name, $value) = explode(':', $header_line, 2);
				$name = trim(strtolower($name));
				$value = trim($value);
				if (isset($response_headers[$name])) {
					$response_headers[$name] = array (
						$response_headers[$name],
						$value
					);
				}
				else $response_headers[$name] = $value;
			}

			return $response_headers;
		}


	class Exception extends \Exception
	{
		protected $request, $response;

		function __construct($message, $code, $request, $response=array(), Exception $previous=null)
		{
			$this->request = $request;
			$this->response = $response;
			parent::__construct($message, $code, $previous);
		}

		public function getRequest() { return $this->request; }
		public function getResponse() { return $this->response; }

		public function __toString()
		{
			$backtrace = $this->getTrace();
			return get_class($this) . ": [{$this->code}] {$this->message} in {$backtrace[0]['file']} on line {$backtrace[0]['line']}";
		}
	}

	class CurlException extends Exception { }
	class ResponseException extends Exception
	{
		function __construct($message, $code, $request, $response=array(), Exception $previous=null)
		{
			$url = _http_client_request_uri($request['uri'], $request['query']);
			$this->message = "$message ($url)";
			parent::__construct($this->message, $code, $request, $response, $previous);
		}
	}
?>