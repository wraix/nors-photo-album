<?php

// Copied from  https://gist.github.com/eddmann/10262795

class SecureSessionHandler extends SessionHandler {

  protected $key, $name, $cookie, $savePath;

  public function __construct($key, $name = 'MY_SESSION', $cookie = [], $savePath = "") {
    $this->key = $key;
    $this->name = $name;
    $this->cookie = $cookie;
    $this->savePath = $savePath;

    $this->cookie += [
      'lifetime' => 0,
      'path'     => ini_get('session.cookie_path'),
      'domain'   => ini_get('session.cookie_domain'),
      'secure'   => isset($_SERVER['HTTPS']),
      'httponly' => true
    ];

    $this->setup();
  }

  private function setup() {
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.save_handler', 'files');
    if ( !empty($this->savePath) ) {
      session_save_path($this->savePath);
    }

    session_name($this->name);

    session_set_cookie_params(
      $this->cookie['lifetime'],
      $this->cookie['path'],
      $this->cookie['domain'],
      $this->cookie['secure'],
      $this->cookie['httponly']
    );

    session_set_save_handler($this, true);
  }

  public function start() {
    if (session_id() === '') {
      if (session_start()) {
        return $this->refresh();
      }
    }
    return false;
  }

  public function forget() {
    if (session_id() === '') {
      return false;
    }

    $_SESSION = [];

    setcookie(
      $this->name,
      '',
      time() - 42000,
      $this->cookie['path'],
      $this->cookie['domain'],
      $this->cookie['secure'],
      $this->cookie['httponly']
    );

    return session_destroy();
  }

  public function refresh() {
    return session_regenerate_id(true);
  }

  public function read($id) {
    return mcrypt_decrypt(MCRYPT_3DES, $this->key, parent::read($id), MCRYPT_MODE_ECB);    
  }

  public function write($id, $data) {
    return parent::write($id, mcrypt_encrypt(MCRYPT_3DES, $this->key, $data, MCRYPT_MODE_ECB));    
  }

  public function isExpired($ttl = 30) {
    $last = (isset($_SESSION['_last_activity']) ? $_SESSION['_last_activity'] : false);
    if ($last !== false && time() - $last > $ttl * 60) {
      return true;
    }

    $_SESSION['_last_activity'] = time();

    return false;
  }

  private function ipToHex($ipAddress) {
    $hex = '';
    if(strpos($ipAddress, ',') !== false) {
        $splitIp = explode(',', $ipAddress);
        $ipAddress = trim($splitIp[0]);
    }
    $isIpV6 = false;
    $isIpV4 = false;
    if(filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
        $isIpV6 = true;
    }
    else if(filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $isIpV4 = true;
    }
    if(!$isIpV4 && !$isIpV6) {
        return false;
    }
    // IPv4 format
    if($isIpV4) {
        $parts = explode('.', $ipAddress);
        for($i = 0; $i < 4; $i++) {
            $parts[$i] = str_pad(dechex($parts[$i]), 2, '0', STR_PAD_LEFT);
        }
        $ipAddress = '::'.$parts[0].$parts[1].':'.$parts[2].$parts[3];
        $hex = join('', $parts);
    }
    // IPv6 format
    else {
        $parts = explode(':', $ipAddress);
        // If this is mixed IPv6/IPv4, convert end to IPv6 value
        if(filter_var($parts[count($parts) - 1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $partsV4 = explode('.', $parts[count($parts) - 1]);
            for($i = 0; $i < 4; $i++) {
                $partsV4[$i] = str_pad(dechex($partsV4[$i]), 2, '0', STR_PAD_LEFT);
            }
            $parts[count($parts) - 1] = $partsV4[0].$partsV4[1];
            $parts[] = $partsV4[2].$partsV4[3];
        }
        $numMissing = 8 - count($parts);
        $expandedParts = array();
        $expansionDone = false;
        foreach($parts as $part) {
            if(!$expansionDone && $part == '') {
                for($i = 0; $i <= $numMissing; $i++) {
                    $expandedParts[] = '0000';
                }
                $expansionDone = true;
            }
            else {
                $expandedParts[] = $part;
            }
        }
        foreach($expandedParts as &$part) {
            $part = str_pad($part, 4, '0', STR_PAD_LEFT);
        }
        $ipAddress = join(':', $expandedParts);
        $hex = join('', $expandedParts);
    }
    // Validate the final IP
    if(!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
        return false;
    }
    return strtolower(str_pad($hex, 32, '0', STR_PAD_LEFT));
  }

  public function isFingerprint() {
    $ip = $this->ipToHex($_SERVER['REMOTE_ADDR']);
    $hash = md5($_SERVER['HTTP_USER_AGENT'] . $ip);
    if (isset($_SESSION['_fingerprint'])) {
      return $_SESSION['_fingerprint'] === $hash;
    }

    $_SESSION['_fingerprint'] = $hash;

    return true;
  }

  public function isValid() {
    return ! $this->isExpired() && $this->isFingerprint();
  }

  public function get($name) {
    $parsed = explode('.', $name);

    $result = $_SESSION;

    while ($parsed) {
      $next = array_shift($parsed);

      if (isset($result[$next])) {
        $result = $result[$next];
      } else {
        return null;
      }
    }

    return $result;
  }

  public function put($name, $value) {
    $parsed = explode('.', $name);

    $session =& $_SESSION;

    while (count($parsed) > 1) {
      $next = array_shift($parsed);

      if ( ! isset($session[$next]) || ! is_array($session[$next])) {
        $session[$next] = [];
      }

      $session =& $session[$next];
    }

    $session[array_shift($parsed)] = $value;
  }

}
