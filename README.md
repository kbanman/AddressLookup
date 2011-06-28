Running `test.php` outputs the following:

**Reverse Phone**

	array(1) {
	  [0]=>
		  array(7) {
		    ['name_first']=> 'C'
		    ['name_last']=> 'Banman'
		    ['phone']=> '6048700130'
		    ['address']=> '32189 Huntingdon Rd'
		    ['city']=> 'Abbotsford'
		    ['province']=> 'BC'
		    ['postalcode']=> 'V2T 5Y7'
		  }
	}


**Reverse Address**

	array(1) {
	  [0]=>
		  ['name_first']=> 'C'
		  ['name_last']=> 'Banman'
		  ['address']=> string(19) '32189 Huntingdon Rd'
		  ['city']=> string(10) 'Abbotsford'
		  ['province']=> 'BC'
		  ['postalcode']=> 'V2T 5Y7'
		  ['phone']=> string(10) '6048700130'
		}
	}
Note that this call currently returns only the first result.


**Reverse Postal Code**

	array(2) {
	  [0]=>
	  array(10) {
	    ['building']=> ''
	    ['number_start']=> '32022'
	    ['number_end']=> '32320'
	    ['odd_even']=> 'even'
	    ['delivery_mode']=> ''
	    ['street']=> string(13) 'HUNTINGDON RD'
	    ['suite']=> ''
	    ['city']=> string(10) 'ABBOTSFORD'
	    ['province']=> 'BC'
	    ['postalcode']=> 'V2T 5Y7'
	  }
	  [1]=>
	  array(10) {
	    ['building']=> ''
	    ['number_start']=> '32113'
	    ['number_end']=> '32275'
	    ['odd_even']=> 'odd'
	    ['delivery_mode']=> ''
	    ['street']=> string(13) 'HUNTINGDON RD'
	    ['suite']=> ''
	    ['city']=> string(10) 'ABBOTSFORD'
	    ['province']=> 'BC'
	    ['postalcode']=> 'V2T 5Y7'
	  }
	}