#!/usr/bin/php
<?php

# Create a symlink with the following name-schema:
# netatmo_$module_$value
# 
# $module is the name of your Netatmo-Module
# $key can be:
#      Temperature
#      Humidity
#      CO2
#      Pressure
#      Noise
#
# Please note that CO2, Pressure and Noise is only avaiable with Indoor-Modules.

$filename=preg_replace('/\.php$/', '', $_SERVER['SCRIPT_FILENAME']);

$get_data=split("_",$filename);
$get_module=$get_data['1'];
$get_value=$get_data['2'];

foreach ($argv as $arg) {
  $e=explode("=",$arg);
  if(count($e)==2)
    $_GET[$e[0]]=$e[1];
  else    
    $_GET[$e[0]]=0;
}

if (key_exists("list",$_GET)) {
  echo($get_value);
  echo "\n";
  die();
}
if (key_exists("nodes",$_GET)) {
  echo(gethostname());
  echo "\n";
  die();
}
if (key_exists("config",$_GET)) {
  echo "graph_title ".$get_value." ".$get_module;
  echo "\n";
  echo "graph_vlabel ".$get_module.$get_value;
  echo "\n";
  switch ($get_value) {
    case "Temperature":
      echo $get_module."Temperature.label Degrees\n";
      echo $get_module."Temperature.colour COLOUR7\n";
      echo "graph_info The Temperature\n";
      break;
    case "Humidity":
      echo $get_module."Humidity.label Percent\n";
      echo $get_module."Humidity.colour COLOUR7\n";
      echo "graph_info The Humidity\n";
      break;
    case "CO2":
      echo $get_module."CO2.label ppm\n";
      echo $get_module."CO2.warning 1000\n";
      echo $get_module."CO2.critical 2000\n";
      echo $get_module."CO2.colour COLOUR7\n";
      echo "graph_info The CO2-Level. Should be not over 2.000. It should drop below 400 once a week for calibration.\n";
      break;
    case "Pressure":
      echo $get_module."Pressure.label mbar\n";
      echo $get_module."Pressure.colour 1\n";
      echo $get_module."Pressure.colour COLOUR7\n";
      echo "graph_info The Air-Pressure.\n";
      echo "graph_args --upper-limit 1100 --lower-limit 900 --rigid";
      break;
    case "Noise":
      echo $get_module."Noise.label dB\n";
      echo $get_module."Noise.warning 65\n";
      echo $get_module."Noise.critical 85\n";
      echo $get_module."Noise.colour COLOUR7\n";
      echo "graph_info The Noise around your Sensor. Should not be over 75dB for a longer time.\n";
      break;
  }
  echo "\n";
  echo "graph_scale no\n";
  echo "graph_category netatmo\n";
  die(); 
}
if (key_exists("version",$_GET)) {
  echo("munin node on ".gethostname()." version: 1.0.0 (munin-netatmo)\n");
  die();
}
if (key_exists("quit",$_GET)) {
  die();
}

if (filemtime('/tmp/netatmo_cache') < time() - 900) {
  unlink('/tmp/netatmo_cache');

  require_once("Netatmo-API/NAApiClient.php");
  require_once("Netatmo-API/Config.php");

  $client = new NAApiClient($config);

  $client->setVariable("username", $test_username);
  $client->setVariable("password", $test_password);

  $helper = new NAApiHelper();
  try {
    $tokens = $client->getAccessToken();        
    
  } catch(NAClientException $ex) {
    echo "An error happend while trying to retrieve your tokens\n";
    die();
  }

  // Retrieve User Info :
  $user = $client->api("getuser", "POST");

  $devicelist = $client->api("devicelist", "POST");
  $devicelist = $helper->SimplifyDeviceList($devicelist);

  $last_mesures = $helper->GetLastMeasures($client,$devicelist);

  file_put_contents('/tmp/netatmo_cache', serialize($last_mesures));
} else {
  echo "# Using cache\n";
  $last_mesures = unserialize(file_get_contents('/tmp/netatmo_cache'));
}

foreach($last_mesures[0]['modules'] as $module) {
  if ($module['module_name'] == $get_module) {
    echo $get_module.$get_value.".value ".floatval($module[$get_value]);
    echo "\n";
    die();
  }
}

?>
