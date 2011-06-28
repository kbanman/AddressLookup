<?php

require_once('AddressLookup.php');

$lookup = new AddressLookup();

echo '<h3>Reverse Phone</h3><pre>';
print_r($lookup->reverse_phone('6048700130'));
echo '</pre>';

echo '<h3>Reverse Address</h3><pre>';
print_r($lookup->reverse_address('32189 Huntingdon Rd', 'Abbotsford', 'BC'));
echo '</pre>';

echo '<h3>Reverse Postal Code</h3><pre>';
print_r($lookup->reverse_postalcode('V2T 5Y7'));
echo '</pre>';
