<?php

spl_autoload_register('site_autoload');
set_error_handler('site_error_handler');

class SiteException extends Exception {}

interface ISite {
  public function run(array $routes, array $albums);
}

class Site implements ISite {

  const SESSION_SECRET = 't664kD21wY5MI=i&o}jg+M19?+E"8o';

  private $startTime = 0;
  private $endTime = 0;
  private $deltaTime = 0;
  private $uniqueId = 0;
  private $request = null;
  private $registry = null;

  public function __construct(Registry $registry) {
    $this->registry = $registry;
  }  

  public function run(array $routes, array $albums) {
    $this->startTime = ceil( microtime(true) * 1000000 ); // use micro seconds precision.
    $this->uniqueId = $this->fetchUniqueId();
    $this->logStartProcessing();

    $this->request = $this->parseRequest();

    $this->logRoute();

    // Setup output buffer to catch everything, so we do not return debug information to client.
    ob_start(null, 0, PHP_OUTPUT_HANDLER_STDFLAGS ^ PHP_OUTPUT_HANDLER_REMOVABLE ^ PHP_OUTPUT_HANDLER_FLUSHABLE);

    try {

      // Process request:
      // 1. Decide route
      // 2. Lookup classname for route.
      // 3. Validate authentication
      // 4. Instantiate classname
      // 5. Process request using class
      // 6. Return response.

      $route = $this->request['route'];
      if (empty($route)) {
        return $this->send(404, null, array(), "");
      }

      $classname = $this->resolveClassnameFromRoute($routes, $route);
      if (empty($classname) ) {
        return $this->send(404, null, array(), "");
      }

      $key = substr(hash('sha256', self::SESSION_SECRET, true), 0, 24); // 192 bit key for 3DES used.
      $session = new SecureSessionHandler($key, $this->request['session_name'], [], __DIR__.'/sessions');      
      $session->start();
      if ( ! $session->isValid() ) {
        $session->forget();
      }
      $authenticated = $session->get('authenticated');

      // Authenticate session
      if ( !$authenticated && strtolower($route) !== '/authenticate' ) {
        $authUrl = $this->request['protocol'] .'://'. $this->request['host'] . '/authenticate?l=' . $route;
        $headers = array(
          'Location: ' . $authUrl,
          'Cache-Control: no-cache'
        );
        return $this->send(302, null, $headers, ""); // Redirect to authenticate
      }

      $class = $this->instantiateClass($classname);
      if ( empty($class) ) {
        return $this->send(404, null, array(), "");
      }

      $response = $class->process($this->uniqueId, $this->request, $session, $this->registry, $albums);
      if ( empty($response) ) {
        return $this->send(404, null, array(), "");
      }

      if ( is_a($response, 'Response', false) ) {
        return $this->send($response->getStatus(), $response->getMessage(), $response->getHeaders(), $response->getBody());
      } else {
        if (is_object($response) ) {
          throw new SiteException("Unknown response type '".get_class($response)."' or not implemented.");
        } else {
          throw new SiteException("Unknown response type '".var_export($response, true)."' or not implemented.");
        }
      }

    } catch (Exception $e) {

      $error  = "[".$this->request['method']."] [".$this->request['route']."] ";
      $error .= get_class($e) .": ".$e->getMessage()." in ".$e->getFile()." on line ".$e->getLine() . " trace is " . $e->getTraceAsString();
      error_log("[".$this->uniqueId."] " . $error);

    }

    return $this->send(500, null, array(), "");
  }

  private function send($status, $message, $headers, $body) {
    // Clean output and prepare for response
    $prematureOutput = ob_get_contents();
    if ($prematureOutput != "") { // Starting an OB buffer already returns atleast empty string, we don't want to log this.
      $this->logPrematureOutput($prematureOutput);
    }
    ob_clean();

    $status = (int)$status;
    if ( empty($message) ) {
      $message = $this->lookupDefaultMessageForStatus($status);
    }

    $statusHeader = $_SERVER['SERVER_PROTOCOL']. " " .$status . " " . $message;
    foreach ($headers as $header) {
      header($header);
    }
    header($statusHeader, true, $status);

    echo $body;

    // Measure php time and log to access log.
    $this->endTime = ceil(microtime(true) * 1000000 );
    $this->deltaTime = ($this->endTime - $this->startTime);
    $this->logStopProcessing();
    return $status;
  }

  private function instantiateClass($classname) {
    if (empty($classname)) {
      return null;
    }

    if ( class_exists($classname) ) {
      return new $classname();
    }
    return null;
  }

  // Simple routing using regex.
  // @return string
  public function resolveClassnameFromRoute($routes, $route) {
    foreach ($routes as $candidate) {
      if (preg_match($candidate['r'], $route)) {
        return $candidate['c'];
      }
    }
    return null;
  }

  // @return array
  private function parseRequest() {
    $row = array();

    if ( isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) ) {
      $row['uri'] = $_SERVER['REQUEST_URI'];
    }

    $row['protocol'] = 'http';
    if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
      $row['protocol'] = 'https';
    }

    if ( isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) ) {
      $row['host'] = $_SERVER['HTTP_HOST'];
    }

    if ( isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) ) {
      $row['client_ip'] = $_SERVER['REMOTE_ADDR'];
    }

    if ( isset($_SERVER['REQUEST_METHOD']) && !empty($_SERVER['REQUEST_METHOD']) ) {
      $row['method'] = $_SERVER['REQUEST_METHOD'];
    }
    if ( isset($_SERVER['HTTP_METHOD']) && !empty($_SERVER['HTTP_METHOD']) ) {
      $row['method'] = $_SERVER['HTTP_METHOD'];
    }
    $row['method'] = strtolower($row['method']);

    if ( isset($_SERVER['Authorization']) && !empty($_SERVER['Authorization']) ) {
      $auth = explode(':', $_SERVER['Authorization']);
      if ( isset($auth[0]) ) {
        $row['public_key'] = $auth[0];
      }
      if ( isset($auth[1]) ) {
        $row['signature'] = $auth[1];
      }
    }

    if ( isset($_SERVER['Request-dtm']) && !empty($_SERVER['Request-dtm']) ) {
      $row['request_dtm'] = $_SERVER['Request-dtm'];
    }

    $body = file_get_contents('php://input');
    if ( isset($body) && !empty($body) ) {
      $row['body'] = $body;
    }

    $row['url'] = $row['protocol'] . "://" . $row['host'] . $row['uri'];

    $p = parse_url($row['uri']);
    $row['route'] = strtolower($p['path']);
    if ( isset($p['query']) ) {
      $row['query'] = strtolower($p['query']);
    }
    $pos = strrpos($row['route'], '.');
    if ($pos !== false) {
      $row['extension'] = substr($row['route'], $pos + 1);
      $row['route'] = substr($row['route'], 0, $pos);
    }

    if ( isset($row['extension']) && $row['extension'] == 'json' ) {
      $row['accept'] = "application/json";
      $row['accept_ext'] = "json";
    } elseif ( isset($_SERVER['HTTP_ACCEPT']) && !empty($_SERVER['HTTP_ACCEPT']) ) {
      if ( strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false ) {
        $row['accept'] = "application/json";
        $row['accept_ext'] = "json";
      } else {
        $row['accept'] = "text/html";
        $row['accept_ext'] = "html";
      }
    }

    if ( isset($_SERVER['CONTENT_TYPE']) && !empty($_SERVER['CONTENT_TYPE']) ) {
      $row['content_type'] = $_SERVER['CONTENT_TYPE'];
    }

    $row['domain'] = implode('.', array_slice(explode('.', $row['host']), -2));

    $row['session_name'] = str_replace('.', '', $row['domain']);

    return $row;
  }

  // @return string
  private function lookupDefaultMessageForStatus($status) {
    // HTTP/1.1 - http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
    switch (intval($status)) {
      case 100: return "Continue";
      case 101: return "Switching Protocols";

      case 200: return "OK";
      case 201: return "Created";
      case 202: return "Accepted";
      case 203: return "Non-Authoritative Information";
      case 204: return "No Content";
      case 205: return "Reset Content";
      case 206: return "Partial Content";

      case 300: return "Multiple Choices";
      case 301: return "Moved Permanently";
      case 302: return "Found";
      case 303: return "See Other";
      case 304: return "Not Modified";
      case 305: return "Use Proxy";
      case 307: return "Temporary Redirect";

      case 400: return "Bad Request";
      case 401: return "Unauthorized";
      case 402: return "Payment Required";
      case 403: return "Forbidden";
      case 404: return "Not Found";
      case 405: return "Method Not Allowed";
      case 406: return "Not Acceptable";
      case 407: return "Proxy Authentication Required";
      case 408: return "Request Timeout";
      case 409: return "Conflict";
      case 410: return "Gone";
      case 411: return "Length Required";
      case 412: return "Precondition Failed";
      case 413: return "Request Entity Too Large";
      case 414: return "Request-URI Too Long";
      case 415: return "Unsupported Media Type";
      case 416: return "Requested Range Not Satisfiable";
      case 417: return "Expectation Failed";

      case 500: return "Internal Server Error";
      case 501: return "Not Implemented";
      case 502: return "Bad Gateway";
      case 503: return "Service Unavailable";
      case 504: return "Gateway Timeout";
      case 505: return "HTTP Version Not Supported";
    }
    return "Unknown";
  }

  private function logStartProcessing() {
    if (function_exists("apache_note")) {
      apache_note('UNIQUE_ID', $this->uniqueId);
    }
  }

  private function logRoute() {
    if (function_exists("apache_note")) {
      apache_note('ROUTE', $this->request['route']);
    }
  }

  private function logStopProcessing() {
    if (function_exists("apache_note")) {
      apache_note('PHP_PROCESS_TIME', $this->deltaTime);
    }
  }

  private function logPrematureOutput($prematureOutput) {
    error_log("[".$this->uniqueId."] Premature Output: " . $prematureOutput);
  }

  // @return string
  private function fetchUniqueId() {
    // UUID v.4 https://gist.github.com/dahnielson/508447
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for "time_low"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),      
      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,                   
      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,                   
      // 48 bits for "node"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }
}

function site_search_include_path($fileName) {
  if (is_file($fileName)) {
    return $fileName;
  }  
  foreach (explode(PATH_SEPARATOR, ini_get("include_path")) as $path) {
    if (strlen($path) > 0 && $path{strlen($path) - 1} != DIRECTORY_SEPARATOR) {
      $path .= DIRECTORY_SEPARATOR;
    }
    $f = realpath($path . $fileName);
    if ($f && is_file($f)) { 
      return $f;
    }
  }
  return false;
}

function site_autoload($className) {
  $fileName = str_replace('_', '/', strtolower($className)).'.php';
  if (site_search_include_path($fileName)) {
    require_once($fileName);
  }
}

function site_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}

function e($str) {
  echo htmlspecialchars($str, ENT_QUOTES);
}