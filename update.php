<?php

// check if the config.ini file exists
if (!file_exists('config.ini')) {
    exit('config.ini file does not exist');
}

// read config.ini file
$config = parse_ini_file('config.ini');

// check if the config.ini file contains the required fields
if (!isset($config['base_url']) || !isset($config['plugin_url'])) {
    exit('config.ini file does not contain the required fields');
}

// check if base_url has a trailing slash and add if it doesn't
if (substr($config['base_url'], -1) != '/') {
    $config['base_url'] .= '/';
}


// get a list of all zip files in the current directory
$zip_files = glob('*.zip');

// if there are no zip files, exit
if (empty($zip_files)) {
    exit('No zip files found');
}

// look for any zip files that have a filename in the format filename_1.1.1.zip
$pattern = '/^([a-z0-9-_]+)([_\.])([0-9]+\.[0-9]+\.[0-9]+)\.zip$/';

$separator = '';
foreach ($zip_files as $zip_file) {
    if (preg_match($pattern, $zip_file, $matches)) {
        // if we find a match, extract the filename and version number
        $filename = $matches[1];
        $separator = $matches[2];
        $version = $matches[3];

        //append the version number to the array
        $versions[$filename][] = $version;
        //$versions[$filename] = $version;
    }
}

// if we don't find any zip files in the correct format, exit
if (empty($versions)) {
    exit('No valid zip files found');
}

// there should be only one plugin entry in versions array
if (count($versions) > 1) {
    exit('More than one plugin found');
}

$filename = key($versions);
$plugin_versions = $versions[$filename];

// find the latest version number for the plugin
// store first version number as the latest version
$latest_version = $plugin_versions[0];
foreach ($plugin_versions as $version) {
    if (version_compare($version, $latest_version) > 0) {
        $latest_version = $version;
    }
}
#echo $latest_version;


// find the main plugin file, the one that contains the plugin header information and read all header information fields
$zip = new ZipArchive;
if ($zip->open($filename . $separator . $latest_version . '.zip') === TRUE) {
    $plugin_info = $zip->getFromName($filename . '/' . $filename . '.php');
    $zip->close();
}

// parse the plugin information
if (isset($plugin_info)) {
    $plugin_info = explode("\n", $plugin_info);
    foreach ($plugin_info as $line) {
        if (preg_match('/^Plugin Name: (.+)$/', $line, $matches)) {
            $plugin_name = $matches[1];
        }
        if (preg_match('/^Version: (.+)$/', $line, $matches)) {
            $plugin_version = $matches[1];
        }
        if (preg_match('/^Description: (.+)$/', $line, $matches)) {
            $plugin_description = $matches[1];
        }
        if (preg_match('/^Author: (.+)$/', $line, $matches)) {
            $plugin_author = $matches[1];
        }
        if (preg_match('/^Author URI: (.+)$/', $line, $matches)) {
            $plugin_author_uri = $matches[1];
        }
        if (preg_match('/^Plugin URI: (.+)$/', $line, $matches)) {
            $plugin_uri = $matches[1];
        }
    }
}

//set http headers for a json response
header('Content-Type: application/json');

// if the plugin_uri is not set, use the plugin_url from the config.ini file
if (!isset($plugin_uri)) {
    $plugin_uri = $config['plugin_url'];
}

// return a json response with the plugin information

$json_response = json_encode(array(
    'id' => 1,
    'slug' => $filename,
    'plugin' => $filename . "/" . $filename . '.php',
    'new_version' => $latest_version,
    'url' => $plugin_uri,
    'package' => $config['base_url'] . $filename . $separator . $latest_version . '.zip',
), JSON_PRETTY_PRINT);

echo $json_response;

// save the json_response to a file
file_put_contents('update.json', $json_response);

// end of update.php
