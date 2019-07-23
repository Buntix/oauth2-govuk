<?php

namespace League\OAuth2\Client\Provider;

class TxM 
{

  protected static
    $method_requirements = [
      'WEB_APP_VIA_SERVER' => [
        'Gov-Client-Connection-Method',
        'Gov-Client-Public-IP',
        'Gov-Client-Public-Port',
        'Gov-Client-Device-ID',
        'Gov-Client-User-IDs',
        'Gov-Client-Timezone',
        'Gov-Client-Local-IPs',
        'Gov-Client-Screens',
        'Gov-Client-Window-Size',
        'Gov-Client-Browser-Plugins',
        'Gov-Client-Browser-JS-User-Agent',
        'Gov-Client-Browser-Do-Not-Track',
        'Gov-Client-Multi-Factor',
        'Gov-Vendor-Version',
        'Gov-Vendor-License-IDs',
        'Gov-Vendor-Public-IP',
        'Gov-Vendor-Forwarded',
      ],
    ];

  protected
    $data  = [],
    $built = [];


  function __construct($saved = [], $data = []) {
    if (! empty($saved)) $this->built = $saved;
    if (! empty($data))  $this->data  = $data;
  }


  function getHeaders($connection_method = 'WEB_APP_VIA_SERVER', $data = []) {
    if (! empty($data)) $this->data = $data;

    return $this->buildHeaders($connection_method);
  }


  function buildHeaders($connection_method) {
    $this->data['Gov-Client-Connection-Method'] = $connection_method;

    foreach (self::$method_requirements[$connection_method] as $header) {
      $getter = 'get' . str_replace('-', '', $header);

      $this->built[$header] = (method_exists($this, $getter)) 
        ? call_user_func([$this, $getter])
        : $this->getFormattedDataValue($header);
    }

    return $this->built;
  }


  function getGovClientPublicIP() {
    return self::clean(@$_SERVER['REMOTE_ADDR']);
  }


  function getGovClientPublicPort() {
    return self::clean(@$_SERVER['REMOTE_PORT']);
  }


  function getGovClientUserIDs() {
    $ids = $this->getDataValue('user_ids', []);

    if (! empty($_SERVER['REMOTE_USER'])) $ids['os'] = $_SERVER['REMOTE_USER'];

    return self::asQueryString($ids);
  }


  function getGovClientTimezone() {
    return $this->getDataValue('Gov-Client-Timezone', 'UTC+00:00');
  }


  function getGovClientBrowserDoNotTrack() {
    return (empty($_SERVER['HTTP_DNT'])) ? 'false' : 'true';
  }


  function getGovVendorPublicIP() {
    return self::clean(@$_SERVER['SERVER_ADDR']);
  }



  function setData($data) {
    $this->data = $data;
  }


  function getFormattedDataValue($key, $default = '') {
    $val = $this->getDataValue($key, $default);

    if (is_array($val)) {
      $val = count(array_filter(array_keys($val), 'is_string'))
        ? self::asQueryString($val)
        : self::asListString($val);
    }

    return $val;
  }


  function getDataValue($key, $default = '') {
    $val = (isset($this->data[$key])) ? $this->data[$key] : $default;

    return (is_scalar($val)) ? self::clean($val) : $val;
  }




  // RFC3986 encoding.
  static function clean($val) {
    return rawurlencode((string) $val);
  }


  static function asQueryString(array $array) {
    return http_build_query($array, NULL, '&', PHP_QUERY_RFC3986);
  }


  static function asListString(array $array) {
    return implode(',', array_map('self::clean', $array));
  }
}
