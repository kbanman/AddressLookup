<?php

require_once('AddressLookup.php');

$lookup = new AddressLookup();
$lookup->google_api_key = '***INSERT_YOUR_GOOGLE_MAPS_API_KEY_HERE***';

echo '<h3>Reverse Phone</h3><pre>';
print_r($lookup->reverse_phone('(905) 453-9141'));
echo '</pre>';

echo '<h3>Reverse Address</h3><pre>';
print_r($lookup->reverse_address('58 Dawson Cres', 'Brampton', 'ON'));
echo '</pre>';

echo '<h3>Reverse Postal Code</h3><pre>';
print_r($lookup->reverse_postalcode('L6V 3M5'));
echo '</pre>';

echo '<h3>Reverse Geo-Coding</h3><pre>';
print_r($lookup->reverse_geocode('58 Dawson Cres', 'Brampton', 'ON'));
echo '</pre>';