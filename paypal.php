<?php

function a($string, $start, $end)
{
  $string = ' ' . $string;
  $ini = strpos($string, $start);
  if ($ini == 0) return '';
  $ini += strlen($start);
  $len = strpos($string, $end, $ini) - $ini;
  return substr($string, $ini, $len);
}

require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_REQUEST['email']) || !filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
  die;
}

$email  = $_REQUEST['email'];
$curl   = new \Curl\Curl;

$curl->post('https://www.paypal.com/donate/api/buttons?email=' . $email, http_build_query([
  'button[country_code]'                => 'US',
  'button[type]'                        => 'DONATE',
  'button[sub_type]'                    => 'PRODUCTS',
  'button[language]'                    => 'en',
  'button[button_variables][0][name]'   => 'item_name',
  'button[button_variables][0][value]'  => '',
  'button[button_variables][1][name]'   => 'currency_code',
  'button[button_variables][1][value]'  => 'USD',
  'button[button_variables][2][name]'   => 'business',
  'button[button_variables][2][value]'  => $email,
  'button[button_variables][3][name]'   => 'item_number',
  'button[button_variables][3][value]'  => '',
  'button[button_variables][4][name]'   => 'no_shipping',
  'button[button_variables][4][value]'  => '1',
  'button[button_variables][5][name]'   => 'no_note',
  'button[button_variables][5][value]'  => '0',
  'button[button_image_url]'            => 'https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif',
  'button[button_image]'                => 'CC',
]));

$rawResponse        = $curl->rawResponse;
$result['country']  = a($rawResponse, '"legalCountry":"', '"');
$result['email']    = $email;
if ($curl->httpStatusCode == 200 || $result['country']  != null || strpos($rawResponse, 'COUNTRY_NOT_ALLOWED') || strpos($rawResponse, 'Email is required')) {
  $result['status']   = 1;
} else {
  if (strpos($rawResponse, 'is not matching')) {
    $result['status'] = 2;
  } else {
    $result['status'] = 3;
  }
}
die(json_encode($result));
