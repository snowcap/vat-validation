<?php

namespace Snowcap\Vat;

class Validation
{
    const WSDL = "http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl";

    /**
     * @var \SoapClient
     */
    private $_client = null;

    /**
     * @var array
     */
    private $_options = array(
        'debug' => false,
    );

    /**
     * @var bool
     */
    private $_valid = false;

    /**
     * @var array
     */
    private $_data = array();

    /**
     * @param array $options
     */
    public function __construct($options = array())
    {

        foreach ($options as $option => $value) {
            $this->_options[$option] = $value;
        }

        if (!class_exists('SoapClient')) {
            throw new \Exception('The Soap library has to be installed and enabled');
        }
    }

    /**
     * @param string $countryCode
     * @param string $vatNumber
     * @return bool
     */
    public function check($countryCode, $vatNumber)
    {
        $this->initClient();

        $vatNumber = preg_replace("/[ .]/", "", $vatNumber);

        try {
            $rs = $this->_client->checkVat(array('countryCode' => $countryCode, 'vatNumber' => $vatNumber));
        }
        catch(\SoapFault $e) {
            if($e->getMessage() === 'INVALID_INPUT') {
                return false;
            }

            throw $e;
        }

        if ($rs->valid) {
            $this->_valid = true;
            list($denomination, $name) = strpos($rs->name, ' ') !== false ? explode(" ", $rs->name, 2) : array('', '');
            list($streetline, $cityline) = strpos($rs->address, "\n" !== false) ? explode("\n", $rs->address) : array('', '');
            preg_match('/(.+) ([^ ]*[0-9]+[^ ]*)/', $this->cleanUpString($streetline), $parts);
            if (count($parts) === 0) {
                $street = $this->cleanUpString($streetline);
                $number = "";
            } else {
                $street = $parts[1];
                $number = $parts[2];
            }

            list($zip, $city) = $cityline !== '' ? explode(' ', $this->cleanUpString($cityline), 2) : array('', '');

            $this->_data = array(
                'denomination' => $denomination,
                'name' => $this->cleanUpString($name),
                'address' => $this->cleanUpString($rs->address),
                'street' => $street,
                'number' => $number,
                'zip' => $zip,
                'city' => $city,
                'country' => $countryCode
            );

            return true;
        } else {
            $this->_valid = false;
            $this->_data = array();

            return false;
        }
    }

    /**
     * @param string $vatNumberWithCountryCode
     * @return bool
     */
    public function checkNumber($vatNumberWithCountryCode)
    {
        $this->initClient();

        $pattern = '/([A-Z]{2})(.+)/i';
        $match = preg_match($pattern, $vatNumberWithCountryCode, $matches);

        if (0 !== $match) {
            $countryCode = $matches[1];
            $number = trim($matches[2]);

            return $this->check($countryCode, $number);
        }

        return false;
    }

    public function isValid()
    {
        return $this->_valid;
    }

    public function getDenomination()
    {
        return $this->_data['denomination'];
    }

    public function getName()
    {
        return $this->_data['name'];
    }

    public function getAddress()
    {
        return $this->_data['address'];
    }

    public function getStreet()
    {
        return $this->_data['street'];
    }

    public function getNumber()
    {
        return $this->_data['number'];
    }

    public function getZip()
    {
        return $this->_data['zip'];
    }

    public function getCity()
    {
        return $this->_data['city'];
    }

    public function getData()
    {
        return $this->_data;
    }

    public function isDebug()
    {
        return ($this->_options['debug'] === true);
    }

    private function initClient()
    {
        if(null === $this->_client) {
            $this->_client = new \SoapClient(self::WSDL, array('trace' => true));
        }
    }

    private function cleanUpString($string)
    {
        for ($i = 0; $i < 100; $i++) {
            $newString = str_replace("  ", " ", $string);
            if ($newString === $string) {
                break;
            } else {
                $string = $newString;
            }
        }

        $newString = "";
        $words = explode(" ", $string);
        foreach ($words as $k => $w) {
            $newString .= ucfirst(strtolower($w)) . " ";
        }

        return trim($newString);
    }
}
