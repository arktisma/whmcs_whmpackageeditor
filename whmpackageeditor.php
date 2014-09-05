<?php

if (!defined("WHMCS"))
  die("This file cannot be accessed directly");

function whmpackageeditor_config() {
    $configarray = array(
    "name" => "WHM Package Editor",
    "description" => "Edit WHM and WHMCS packages together",
    "version" => "1.0",
    "author" => "Serversaurus",
    "language" => "english");
    return $configarray;
}

function whmpackageeditor_activate() {
  return array("status" => "success", "description" => "Successfully initialised.");
}

function whmpackageeditor_deactivate() {
    return array("status" => "success", "description" => "Successfully deactivated.");
}

function whmpackageeditor_output() {
  $tplFile = dirname(__FILE__) . "/whmpackageeditor.tpl";
  $fp = fopen($tplFile, "r");
  if($fp === false) {
    echo "<h1>Couldn't open the template file: $tplFile - </h1><code>" . print_r(error_get_last(), true) . "</code>";
    return;
  }
  fpassthru($fp);
  fclose($fp);
}


?>
