<?php

require_once __DIR__ . '/vendor/autoload.php';

function input($message)
{
  echo $message . ' ';
  return trim(fgets(fopen('php://stdin', 'r')));
}

function clean(array $list)
{
  $array    = [];
  foreach ($list as $key => $email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      continue;
    }
    array_push($array, $email);
  }
  return array_unique($array);
}

// $listArray  = clean(explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/' . input('Tell me your list file?')))));

// foreach ($listArray as $key => $email) {
// }

use \RollingCurl\RollingCurl as Rolling;
use \RollingCurl\Request;

class Run
{

  public $path;
  public $listArray;
  public $rolling;
  public $live;
  public $die;
  public $unknown;

  public function __construct()
  {
    $this->rolling  = new Rolling;
    $this->path     = self::input('Your email list pls?');

    if (!file_exists('result/live.txt')) {
      @mkdir('result');
    }

    $this->live     = fopen(__DIR__ . '/result/live.txt', 'a');
    $this->die      = fopen(__DIR__ . '/result/die.txt', 'a');
    $this->unknown  = fopen(__DIR__ . '/result/unknown.txt', 'a');
  }

  private function cleanList(array $list)
  {
    $array    = [];
    foreach ($list as $email) {
      if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        continue;
      }
      array_push($array, $email);
    }
    return array_unique($array);
  }

  public function start()
  {
    if (!file_exists($this->path)) {
      throw new Exception('Cant open ' . $this->path);
      return;
    }

    $this->listArray  = $this->cleanList(explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/' . $this->path))));
    foreach ($this->listArray as $email) {
      $this->rolling->get('https://mwemwekque.herokuapp.com/paypal.php?email=' . $email);
    }

    self::message('Total list: ' .  count($this->listArray));
    self::input('Its everything right? CTRL + C For cancel proccess.');

    $current  = 1;
    $self     = $this;
    $this->rolling->setCallback(function (Request $request) use (&$self) {
      global $current;
      $current++;
      parse_str(parse_url($request->getUrl(), PHP_URL_QUERY), $params);

      $response   = json_decode($request->getResponseText());
      echo $current . ' - ';
      if (!$response) {
        echo $self->result($params['email'], 'UNKNOWN');
      } else {
        if ($response->status == 1) {
          echo $self->result($response->email, 'LIVE', $response->country);
        } else if ($response->status == 2) {
          echo $self->result($response->email, 'DIE');
        } else {
          echo $self->resilt($response->email, 'UNKNOWN');
        }
      }
    })
      ->setSimultaneousLimit(100)
      ->execute();;
  }

  public function result($message, $status, $country = '')
  {
    $country  = !empty($country) ? ' (' . $country . ')' : '';
    switch ($status) {
      case 'LIVE':
        fwrite($this->live, $message . $country . PHP_EOL);
        break;

      case 'DIE':
        fwrite($this->die, $message . PHP_EOL);
        break;

      default:
        fwrite($this->unknown, $message . PHP_EOL);
        break;
    }

    $mask = "| %5s | %40s\n";
    printf($mask, $status, $message);
  }

  private static function message($message)
  {
    echo PHP_EOL;
    echo '  ' . $message;
    echo PHP_EOL;
  }

  public static function input($message)
  {
    echo '  ' . $message . ' ';
    return trim(fgets(fopen('php://stdin', 'r')));
  }
}

try {
  $cok  = new Run;
  $cok->start();
} catch (\Exception $e) {
  echo $e->getMessage() . PHP_EOL;
}
