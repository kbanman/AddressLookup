Running `test.php` outputs something like the following:

**Reverse Phone**

	array(1) {
		[0] => array {
			['name_first'] => 'J'
			['name_last'] => 'Smith'
			['phone'] => '9048700130'
			['address'] => '32189 Winding Rd'
			['city'] => 'Smallville'
			['province'] => 'BC'
			['postalcode'] => 'V4T 5Y7'
		}
	}


**Reverse Address**

	array {
		[0]=> array {
			['name_first']=> 'J'
			['name_last']=> 'Smith'
			['address']=> '32189 Winding Rd'
			['city']=> 'Smallville'
			['province']=> 'BC'
			['postalcode']=> 'V4T 5Y7'
			['phone']=> '9048700130'
		}
	}
Note that this call currently returns only the first result.
(Each result currently requires a separate http request, so I'm limiting it)


**Reverse Postal Code**

	array {
		[0] => array {
			['building'] => ''
			['number_start'] => '32022'
			['number_end'] => '32320'
			['odd_even'] => 'even'
			['delivery_mode'] => ''
			['street'] => 'WINDING RD'
			['suite'] => ''
			['city'] => 'SMALLVILLE'
			['province'] => 'BC'
			['postalcode'] => 'V2T 5Y7'
		}
		[1] => array {
			['building'] => ''
			['number_start'] => '32113'
			['number_end'] => '32275'
			['odd_even'] => 'odd'
			['delivery_mode'] => ''
			['street'] => 'WINDING RD'
			['suite'] => ''
			['city'] => 'SMALLVILLE'
			['province'] => 'BC'
			['postalcode'] => 'V4T 5Y7'
		}
	}


**Reverse Geo-code**

	array {
		['status'] => 200
		['accuracy'] => 5
		['coords'] => array {
			['lat'] => 49.38823233,
			['lng'] => 101.8237732
		}
	}
