# Vat Validation #

## About ##
Vat-Validation is a PHP class allowing you to

- Validate a VAT number
- Retrieve information like the name or the address of the company

The data is extracted from a European Commission webservice

__It actually only works for European countries__

## Usage ##

See <code>examples/example.php</code>

## Some available methods ##

	$validation->isValid()
	$validation->getName()
	$validation->getDenomination()
	$validation->getAddress() 

## Requirements ##

* PHP 5.3>
* Soap extension enabled

## Disclaimer ##

Take a look at http://ec.europa.eu/taxation_customs/vies/viesdisc.do to know when/how you're allowed to use this service and his information