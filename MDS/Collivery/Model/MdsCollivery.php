<?php
namespace MDS\Collivery\Model;

use Exception;
use SoapFault;  // Use PHP Soap Fault

class MdsCollivery
{
    public $objectManager;
    protected $token;
    protected $client;
    protected $config;
    protected $errors = [];
    protected $check_cache = 2;

    protected $default_address_id;
    protected $default_address_town_id;
    protected $default_address_suburb_id;
    protected $default_address_location_type_id;

    protected $client_id;
    protected $user_id;

    /**
     * Setup class with basic Config
     *
     * @param Array   $config Configuration Array
     * @param Class   $cache  Caching Class with functions has, get, put, forget
     */
    public function __construct(array $config = [], $cache = null)
    {
        if (is_null($cache)) {
            $cache_dir = array_key_exists('cache_dir', $config) ? $config['cache_dir'] : null;
            $this->cache = new Cache($cache_dir);
        } else {
            $this->cache = $cache;
        }

        $this->config = (object) $config;

        foreach ($config as $key => $value) {
            $this->config->$key = $value;
        }

        if ($this->config->demo) {
            $this->config->user_email    = 'api@collivery.co.za';
            $this->config->user_password = 'api123';
        }
        $this->config->api_url= 'https://api.collivery.co.za/v3/';
        $this->authenticate();
    }


    /**
     * Authenticate and set the token
     *
     * @return string
     */
    protected function authenticate()
    {
        if (
            $this->check_cache == 2 &&
            $this->cache->has('collivery.auth') &&
            $this->cache->get('collivery.auth')['email_address'] == $this->config->user_email
        ) {
            $authenticate = $this->cache->get('collivery.auth');
            $this->assignAuthValues($authenticate);
            return true;
        } else {

            $user_email    = $this->config->user_email;
            $user_password = $this->config->user_password;

            try {
                $authenticate = $this->consumeAPI('login', [
                    "email" => $user_email,
                    "password" => $user_password
                ], 'POST', true);
                $authenticate = $authenticate['data'];
                $this->setError("Auth-result",print_r($authenticate));
                if (is_array($authenticate) && isset($authenticate['api_token'])) {
                    if ($this->check_cache != 0) {
                        $this->cache->put('collivery.auth', $authenticate, 50);
                    }

                    $this->assignAuthValues($authenticate);

                    return $authenticate;
                } else {
                    if (isset($authenticate['error'])) {
                        $this->setError($authenticate['error']['http_code'], $authenticate['error']['message']);
                    } else {
                        $this->setError('result_unexpected', 'No result returned.');
                    }
                    return false;
                }
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }
        }
    }

    /**
     * assign values to authentification variables
     * @param array   $authenticate authentification result array
     *
     */
    private function assignAuthValues($authenticate)
    {
        $this->default_address_id = $authenticate['client']['primary_address']['id'];
        $this->default_address_town_id = $authenticate['client']['primary_address']['town_id'];
        $this->default_address_suburb_id = $authenticate['client']['primary_address']['suburb_id'];
        $this->default_address_location_type_id = $authenticate['client']['primary_address']['location_type']['id'];
        $this->client_id = $authenticate['client']['id'];
        $this->user_id = $authenticate['id'];
        $this->token = $authenticate['api_token'];
    }

    /**
     * Consumes API
     *
     * @param  string  $url               The URL you're accessing
     * @param  array   $data              The params or query the URL requires.
     * @param  string  $type              ~ Defines how the data is sent (POST / GET)
     * @param  bool    $isAuthenticating  Whether the API requires the api_token
     *
     * @return array $result
     * @throws Exception
     */
    private function consumeAPI($url,$data, $type, $isAuthenticating = false) {

        $url = $this->config->api_url.$url;

        if (!$isAuthenticating) {
            $data["api_token"] = $this->token ?: $this->authenticate()['api_token'];
        }

        $client  = curl_init($url);

        if ($type == 'POST') {
            curl_setopt($client, CURLOPT_POST, 1);
            $data = json_encode($data);
            curl_setopt($client, CURLOPT_POSTFIELDS, $data);
        } else if ($type == 'PUT') {
            curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'PUT');
            $data = json_encode($data);
            curl_setopt($client, CURLOPT_POSTFIELDS, $data);
        } else {
            $query = http_build_query($data);
            $client = curl_init($url.'?'.$query);
        }

        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);

        $headerArray = [
            'X-App-Name:'.$this->config->app_name.' mds/collivery/class',
            'X-App-Version:'.$this->config->app_version,
            'X-App-Host:'.$this->config->app_host,
            'X-App-Url'     => $this->config->app_url,
            'X-App-Lang:'.'PHP '.phpversion(),
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        curl_setopt($client, CURLOPT_HTTPHEADER, $headerArray);

        $result = curl_exec($client);

        if (curl_errno($client)) {
            $errno = curl_errno($client);
            $errmsg = curl_error($client);
            curl_close($client);

            throw new Exception($errmsg, $errno);
        }

        if (isset($result['error'])) {
            $error = $result['error'];
            throw new Exception($error['message'], $error['http_code']);
        }

        curl_close($client);

        // If $result is already an array.
        if (is_array($result)) {
            return $result;
        }

        return json_decode($result, true);
    }


    /**
     * Returns a list of towns and their ID's for creating new addresses.
     * Town can be filtered by country of province (ZAF Only).
     *
     * @param string  $country  Filter towns by Country
     * @param string  $province Filter towns by South African Provinces
     * @return array            List of towns and their ID's
     */
    public function getTowns($country = "ZAF", $province = null)
    {
        if (($this->check_cache == 2) && is_null($province) && $this->cache->has('collivery.towns.' . $country)) {
            return $this->cache->get('collivery.towns.' . $country);
        } elseif (($this->check_cache == 2) && ! is_null($province) && $this->cache->has('collivery.towns.' . $country . '.' . $province)) {
            return $this->cache->get('collivery.towns.' . $country . '.' . $province);
        } else {
            try {

                $param = ["country" => $country, "per_page" => "0"];
                if($province!=null){
                    $param['province'] = $province;}

                $result = $this->consumeAPI("towns", $param, 'GET');

            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data'])) {
                if (is_null($province)) {
                    if ($this->check_cache != 0) {
                        $this->cache->put('collivery.towns.' . $country, $result['data'], 60*24);
                    }
                } else {
                    if ($this->check_cache != 0) {
                        $this->cache->put('collivery.towns.' . $country . '.' . $province, $result['data'], 60*24);
                    }
                }
                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }

                return false;
            }
        }
    }

    /**
     * Allows you to search for town and suburb names starting with the given string.
     * The minimum string length to search is two characters.
     * Returns a list of towns, suburbs, and the towns the suburbs belong to with their ID's for creating new addresses.
     * The idea is that this could be used in an auto complete function.
     *
     * @param string  $name Start of town/suburb name
     * @return array          List of towns and their ID's
     */
    public function searchTowns($name)
    {
        if (strlen($name) < 2) {
            return $this->get_towns();
        } elseif (($this->check_cache == 2) && $this->cache->has('collivery.search_towns.' . $name)) {
            return $this->cache->get('collivery.search_towns.' . $name);
        } else {
            try {
                $result = $this->client()->search_towns($name, $this->token);
            } catch (SoapFault $e) {
                $this->catchSoapFault($e);
                return false;
            }

            if (isset($result)) {
                if ($this->check_cache != 0) {
                    $this->cache->put('collivery.search_towns.' . $name, $result, 60*24);
                }

                return $result;
            } else {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns all the suburbs of a town.
     *
     * @param int     $town_id ID of the Town to return suburbs for
     * @return array
     */
    public function getSuburbs($town_id)
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.suburbs.' . $town_id)) {
            return $this->cache->get('collivery.suburbs.' . $town_id);
        } else {
            try {
                $param = [];
                if ($town_id > 0) {
                    $param['town_id'] = $town_id;
                } else {
                    $param['country'] = "ZAF";
                }
                $result = $this->consumeAPI("suburbs", $param, 'GET');

            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data'])) {
                if ($this->check_cache != 0) {
                    $this->cache->put('collivery.suburbs.' . $town_id, $result['data'], 60*24*7);
                }
                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }
                return false;
            }
        }
    }

    /**
     * Returns the type of Address Locations.
     * Certain location type incur a surcharge due to time spent during
     * delivery.
     *
     * @return array
     */
    public function getLocationTypes()
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.location_types')) {
            return $this->cache->get('collivery.location_types');
        } else {
            try {
                $param =["api_token" => ""];
                if($this->token!=null) {
                    $param["api_token"] = $this->token;
                }
                $result = $this->consumeAPI("location_types",$param, 'GET');
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data'])) {
                if ($this->check_cache != 0) {
                    $this->cache->put('collivery.location_types', $result['data'], 60*24*7);
                }
                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No results returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns the available Collivery services types.
     *
     * @return array
     */
    public function getServices()
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.services')) {
            return $this->cache->get('collivery.services');
        } else {
            try {
                $param = ["api_token" => ""];
                if ($this->token != null)
                    $param["api_token"] = $this->token;
                $result = $this->consumeAPI("service_types", $param, 'GET');
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }
            $this->setError("Service-result",print_r($result));
            if (isset($result['data'])) {
                if ($this->check_cache != 0) {
                    $this->cache->put('collivery.services', $result['data'], 60 * 24 * 7);
                }
                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No services returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns the available Parcel Type ID and value array for use in adding a collivery.
     *
     * @return array  Parcel  Types
     */
    public function getParcelTypes()
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.parcel_types')) {
            return $this->cache->get('collivery.parcel_types');
        } else {
            try {
                $result = $this->client()->get_parcel_types($this->token);
            } catch (SoapFault $e) {
                $this->catchSoapFault($e);
                return false;
            }

            if (is_array($result)) {
                if ($this->check_cache != 0) {
                    $this->cache->put('collivery.parcel_types', $result, 60*24*7);
                }
                return $result;
            } else {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                } else {
                    $this->setError('result_unexpected', 'No results returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns the available Parcel Type ID and value array for use in adding a collivery.
     *
     * @param int     $address_id The ID of the address you wish to retrieve.
     * @return array               Address
     */
    public function getAddress($address_id)
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.address.' . $this->client_id . '.' . $address_id)) {
            return $this->cache->get('collivery.address.' . $this->client_id . '.' . $address_id);
        } else {
            try {
                $param = ["api_token" => ""];
                if ($this->token != null)
                    $param["api_token"] = $this->token;
                $result = $this->consumeAPI("address/".$address_id, $param, 'GET');
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data'])) {
                if ($this->check_cache != 0) {
                    $this->cache->put('collivery.address.' . $this->client_id . '.' . $address_id, $result['data'], 60*24);
                }
                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No address_id returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns all the addresses belonging to a client.
     *
     * @param array   $filter Filter Addresses
     * @return array
     */
    public function getAddresses(array $filter = [])
    {
        if (($this->check_cache == 2) && empty($filter) && $this->cache->has('collivery.addresses.' . $this->client_id)) {
            return $this->cache->get('collivery.addresses.' . $this->client_id);
        } else {
            try {
                if (empty($filter)) {
                    $filter= ["per_page" => "0"];
                }
                $result = $this->consumeAPI("address", $filter, 'GET');
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data'])) {
                if ($this->check_cache != 0 && empty($filter)) {
                    $this->cache->put('collivery.addresses.' . $this->client_id, $result['data'], 60*24);
                }
                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No address_id returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns the Contact people of a given Address ID.
     *
     * @param int     $address_id Address ID
     * @return array
     */
    public function getContacts($address_id)
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.contacts.' . $this->client_id . '.' . $address_id)) {
            return $this->cache->get('collivery.contacts.' . $this->client_id . '.' . $address_id);
        } else {
            try {
                $result = $this->consumeAPI("contacts", ["address_id" => $address_id], 'GET');
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data'])) {
                if ($this->check_cache != 0) {
                    $this->cache->put('collivery.contacts.' . $this->client_id . '.' . $address_id, $result['data'], 60*24);
                }
                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns the POD image for a given Waybill Number.
     *
     * @param int     $collivery_id Collivery waybill number
     * @return array
     */
    public function getPod($collivery_id)
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.pod.' . $this->client_id . '.' . $collivery_id)) {
            return $this->cache->get('collivery.pod.' . $this->client_id . '.' . $collivery_id);
        } else {
            try {
                $result = $this->consumeAPI("proofs_of_delivery/", ["waybill_id" => $collivery_id, "per_page" => "0"], 'GET');
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data'])) {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                } elseif ($this->check_cache != 0) {
                    $this->cache->put('collivery.pod.' . $this->client_id . '.' . $collivery_id, $result['data'], 60*24);
                }

                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns a list of avaibale parcel images for a given Waybill Number.
     *
     * @param int     $collivery_id Collivery waybill number
     * @return array
     */
    public function getParcelImageList($collivery_id)
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.parcel_image_list.' . $this->client_id . '.' . $collivery_id)) {
            return $this->cache->get('collivery.parcel_image_list.' . $this->client_id . '.' . $collivery_id);
        } else {
            try {
                $result = $this->client()->get_parcel_image_list($collivery_id, $this->token);
            } catch (SoapFault $e) {
                $this->catchSoapFault($e);
                return false;
            }

            if (isset($result['images'])) {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                } elseif ($this->check_cache != 0) {
                    $this->cache->put('collivery.parcel_image_list.' . $this->client_id . '.' . $collivery_id, $result['images'], 60*12);
                }

                return $result['images'];
            } else {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns the image of a given parcel-id of a waybill.
     * If the Waybill number is 54321 and there are 3 parcels, they would
     * be referenced by id's 54321-1, 54321-2 and 54321-3.
     *
     * @param string  $parcel_id Parcel ID
     * @return array               Array containing all the information
     *                             about the image including the image
     *                             itself in base64
     */
    public function getParcelImage($parcel_id)
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.parcel_image.' . $this->client_id . '.' . $parcel_id)) {
            return $this->cache->get('collivery.parcel_image.' . $this->client_id . '.' . $parcel_id);
        } else {
            try {
                $result = $this->client()->get_parcel_image($parcel_id, $this->token);
            } catch (SoapFault $e) {
                $this->catchSoapFault($e);
                return false;
            }

            if (isset($result['image'])) {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                } elseif ($this->check_cache != 0) {
                    $this->cache->put('collivery.parcel_image.' . $this->client_id . '.' . $parcel_id, $result['image'], 60*24);
                }

                return $result['image'];
            } else {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }

                return false;
            }
        }
    }

    /**
     * Returns the status tracking detail of a given Waybill number.
     * If the collivery is still active, the estimated time of delivery
     * will be provided. If delivered, the time and receivers name (if availble)
     * with returned.
     *
     * @param int     $collivery_id Collivery ID
     * @return array                 Collivery Status Information
     */
    public function getStatus($collivery_id)
    {
        if (($this->check_cache == 2) && $this->cache->has('collivery.status.' . $this->client_id . '.' . $collivery_id)) {
            return $this->cache->get('collivery.status.' . $this->client_id . '.' . $collivery_id);
        } else {
            try {
                $result = $this->consumeAPI("status_tracking/".$collivery_id, [], 'GET');
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data'])) {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                } elseif ($this->check_cache != 0) {
                    $this->cache->put('collivery.status.' . $this->client_id . '.' . $collivery_id, $result, 60*12);
                }

                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }

                return false;
            }
        }
    }

    /**
     * Create a new Address and Contact
     *
     * @param array   $data Address and Contact Information
     * @return array         Address ID and Contact ID
     */
    public function addAddress(array $data)
    {
        $location_types = $this->getLocationTypes();
        $towns = $this->getTowns();
        $suburbs = $this->getSuburbs($data['town_id']);

        if (! isset($data['location_type'])) {
            $this->setError('missing_data', 'location_type not set.');
        } elseif (! isset($location_types[ $data['location_type'] ])) {
            $this->setError('invalid_data', 'Invalid location_type.');
        }

        if (! isset($data['town_id'])) {
            $this->setError('missing_data', 'town_id not set.');
        } elseif (! isset($towns[ $data['town_id'] ])) {
            $this->setError('invalid_data', 'Invalid town_id.');
        }

        if (! isset($data['suburb_id'])) {
            $this->setError('missing_data', 'suburb_id not set.');
        } elseif (! isset($suburbs[ $data['suburb_id'] ])) {
            $this->setError('invalid_data', 'Invalid suburb_id.');
        }

        if (! isset($data['street'])) {
            $this->setError('missing_data', 'street not set.');
        }

        if (! isset($data['full_name'])) {
            $this->setError('missing_data', 'full_name not set.');
        }

        if (! isset($data['phone']) and ! isset($data['cellphone'])) {
            $this->setError('missing_data', 'Please supply ether a phone or cellphone number...');
        }

        if (! $this->hasErrors()) {
            try {
                $result = $this->consumeAPI("address", $data, 'POST');
                $this->cache->forget('collivery.addresses.' . $this->client_id);
            } catch (Exception $e) {
                $this->catchException($e);
                return false;
            }

            if (isset($result['data']['id'])) {
                return $result['data'];
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No address_id returned.');
                }

                return false;
            }
        }
    }

    /**
     * Add's a contact person for a given Address ID
     *
     * @param array   $data New Contact Data
     * @return int           New Contact ID
     */
    public function addContact(array $data)
    {
        if (!isset($data['address_id'])) {
            $this->setError('missing_data', 'address_id not set.');
        } elseif (!is_array($this->getAddress($data['address_id']))) {
            $this->setError('invalid_data', 'Invalid address_id.');
        }

        if (!isset($data['full_name'])) {
            $this->setError('missing_data', 'full_name not set.');
        }

        if (!isset($data['phone']) and !isset($data['cellphone'])) {
            $this->setError('missing_data', 'Please supply ether a phone or cellphone number... 1');
        }

        if (!isset($data['email'])) {
            $this->setError('missing_data', 'email not set.');
        }

        if (!$this->hasErrors()) {
            try {
                $result = $this->consumeAPI("contacts", $data, 'POST');
                $this->cache->forget('collivery.addresses.' . $this->client_id);
            } catch (Exception $e) {
                $this->catchException($e);

                return false;
            }

            if (isset($result['data'])) {
                return $result;
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No contact_id returned.');
                }
                return false;            }
        } else {
            foreach ($this->getErrors() as $key => $val) {
                $this->setError($key, $val);
            }
        }
    }

    /**
     * Returns the price based on the data provided.
     *
     * @param array   $data Your Collivery Details
     * @return array         Pricing for details supplied
     */
    public function getPrice(array $data)
    {
        $towns = $this->make_key_value_array($this->getTowns());

        if (! isset($data['collection_address']) && ! isset($data['collection_town'])) {
            $this->setError('missing_data', 'collection_address/collection_town not set.');
        } elseif (isset($data['collection_address']) && ! is_array($this->getAddress($data['collection_address']))) {
            $this->setError('invalid_data', 'Invalid Address ID for: collection_address.');
        } elseif (isset($data['collection_town']) && ! isset($towns[ $data['collection_town'] ])) {
            $this->setError('invalid_data', 'Invalid Town ID for: collection_town.');
        }

        if (! isset($data['delivery_address']) && ! isset($data['delivery_town'])) {
            $this->setError('missing_data', 'delivery_address/delivery_town not set.');
        } elseif (isset($data['delivery_address']) && ! is_array($this->getAddress($data['delivery_address']))) {
            $this->setError('invalid_data', 'Invalid Address ID for: delivery_address.');
        } elseif (isset($data['delivery_town']) && ! isset($towns[ $data['delivery_town'] ])) {
            $this->setError('invalid_data', 'Invalid Town ID for: delivery_town.');
        }

        if (! isset($data['service'])) {
            $this->setError('missing_data', 'service not set.');
        }

        if ($this->hasErrors()) {
            foreach ($this->getErrors() as $key => $val) {
                $this->setError($key, $val);
            }
            return false;
        }

        try {
            $result = $this->consumeAPI("quote", $data, 'POST');
        } catch (Exception $e) {
            $this->catchException($e);
            return false;
        }

        if (isset($result['data'])) {
            return $result['data'];
        } else {
            if (isset($result['error'])) {
                $this->setError($result['error']['http_code'], $result['error']['message']);
            } else {
                $this->setError('result_unexpected', 'No price returned.');
            }
        }
    }

    /**
     * Validate Collivery
     *
     * Returns the validated data array of all details pertaining to a collivery.
     * This process validates the information based on services, time frames and parcel information.
     * Dates and times may be altered during this process based on the collection and delivery towns service parameters.
     * Certain towns are only serviced on specific days and between certain times.
     * This function automatically alters the values.
     * The parcels volumetric calculations are also done at this time.
     * It is important that the data is first validated before a collivery can be added.
     *
     * @param array   $data Properties of the new Collivery
     * @return array         The validated data
     */
    public function validate(array $data)
    {
        $contacts_from = $this->getContacts($data['collivery_from']);
        $contacts_to   = $this->getContacts($data['collivery_to']);
        $parcel_types  = $this->getParcelTypes();
        $services      = $this->getServices();

        if (! isset($data['collivery_from'])) {
            $this->setError('missing_data', 'collivery_from not set.');
        } elseif (! is_array($this->getAddress($data['collivery_from']))) {
            $this->setError('invalid_data', 'Invalid Address ID for: collivery_from.');
        }

        if (! isset($data['contact_from'])) {
            $this->setError('missing_data', 'contact_from not set.');
        } elseif (! isset($contacts_from[ $data['contact_from'] ])) {
            $this->setError('invalid_data', 'Invalid Contact ID for: contact_from.');
        }

        if (! isset($data['collivery_to'])) {
            $this->setError('missing_data', 'collivery_to not set.');
        } elseif (! is_array($this->getAddress($data['collivery_to']))) {
            $this->setError('invalid_data', 'Invalid Address ID for: collivery_to.');
        }

        if (! isset($data['contact_to'])) {
            $this->setError('missing_data', 'contact_to not set.');
        } elseif (! isset($contacts_to[ $data['contact_to'] ])) {
            $this->setError('invalid_data', 'Invalid Contact ID for: contact_to.');
        }

        if (! isset($data['collivery_type'])) {
            $this->setError('missing_data', 'collivery_type not set.');
        } elseif (! isset($parcel_types[ $data['collivery_type'] ])) {
            $this->setError('invalid_data', 'Invalid collivery_type.');
        }

        if (! isset($data['service'])) {
            $this->setError('missing_data', 'service not set.');
        } elseif (! isset($services[ $data['service'] ])) {
            $this->setError('invalid_data', 'Invalid service.');
        }

        if (! $this->hasErrors()) {
            try {
                $result = $this->client()->validate_collivery($data, $this->token);
            } catch (SoapFault $e) {
                $this->catchSoapFault($e);
                return false;
            }

            if (is_array($result)) {
                if (isset($result['error_id'])) {
                    $this->setError($result['error_id'], $result['error']);
                }

                return $result;
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error_id'], $result['error']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }

                return false;
            }
        }
    }

    /**
     * Creates a new Collivery based on the data array provided.
     * The array should first be validated before passing to this function.
     * The Waybill No is return apon successful creation of the collivery.
     *
     * @param array   $data Properties of the new Collivery
     * @return int           New Collivery ID
     */
    public function addCollivery(array $data)
    {

        $contacts_from = $this->make_key_value_array($this->getContacts($data['collivery_from']), 'id', '', true);
        $contacts_to = $this->make_key_value_array($this->getContacts($data['collivery_to']), 'id', '', true);
        $parcel_types  = $this->getParcelTypes();
        $services = $this->make_key_value_array($this->getServices(), 'id', 'text');

        if (!isset($data['collivery_from'])) {
            $this->setError('missing_data', 'collivery_from not set.');
        } elseif (!is_array($this->getAddress($data['collivery_from']))) {
            $this->setError('invalid_data', 'Invalid Address ID for: collivery_from.');
        }

        if (!isset($data['contact_from'])) {
            $this->setError('missing_data', 'contact_from not set.');
        } elseif (!isset($contacts_from[$data['contact_from']])) {
            $this->setError('invalid_data', 'Invalid Contact ID for: contact_from.');
        }

        if (!isset($data['collivery_to'])) {
            $this->setError('missing_data', 'collivery_to not set.');
        } elseif (!is_array($this->getAddress($data['collivery_to']))) {
            $this->setError('invalid_data', 'Invalid Address ID for: collivery_to.');
        }

        if (!isset($data['contact_to'])) {
            $this->setError('missing_data', 'contact_to not set.');
        } elseif (!isset($contacts_to[$data['contact_to']])) {
            $this->setError('invalid_data', 'Invalid Contact ID for: contact_to.');
        }

        if (! isset($data['collivery_type'])) {
            $this->setError('missing_data', 'collivery_type not set.');
        } elseif (! isset($parcel_types[ $data['collivery_type'] ])) {
            $this->setError('invalid_data', 'Invalid collivery_type.');
        }

        if (! isset($data['service'])) {
            $this->setError('missing_data', 'service not set.');
        } elseif (! isset($services[ $data['service'] ])) {
            $this->setError('invalid_data', 'Invalid service.');
        }

        if (!$this->hasErrors()) {
            $newObject = [
                "service" => $data["service"],
                "parcels" => $data["parcels"],
                "collection_address" => $data["collivery_from"],
                "collection_contact" => $data["contact_from"],
                "delivery_address" => $data["collivery_to"],
                "delivery_contact" => $data["contact_to"],
                "collection_time" => $data["collection_time"],
                "exclude_weekend" => true,
                "risk_cover" => $data["cover"],
                "special_instructions" => $data["instructions"],
                "reference" => $data["cust_ref"]
            ];

            try {
                $result = $this->consumeAPI("waybill", $newObject, 'POST');
            } catch (Exception $e) {
                $this->catchException($e);

                return false;
            }
            if (isset($result['data']['id'])) {
                return $result;
            } else {
                if (isset($result['error'])) {
                    $this->setError($result['error']['http_code'], $result['error']['message']);
                } else {
                    $this->setError('result_unexpected', 'No result returned.');
                }            }
        } else {
            foreach ($this->getErrors() as $key => $val) {
                $this->setError($key, $val);
            }
        }
    }

    /**
     * Accepts the newly created Collivery, moving it from Waiting Client Acceptance
     * to Accepted so that it can be processed.
     *
     * @param int     $collivery_id ID of the Collivery you wish to accept
     * @return boolean                 Has the Collivery been accepted
     */
    public function acceptCollivery($collivery_id)
    {
        try {
            $result = $this->consumeAPI("status_tracking/".$collivery_id, ["status_id" => 3], 'PUT');
        } catch (Exception $e) {
            $this->catchException($e);
            return false;
        }
        if (isset($result['data'])) {
            if (strpos($result['data']['message'], 'accepted')) {
                return true;
            } else {
                return false;
            }
        } else {
            if (isset($result['error'])) {
                $this->setError($result['error_id'], $result['error']);
            } else {
                $this->setError('result_unexpected', 'No result returned.');
            }

            return false;
        }
    }

    /**
     * @param Array $data - Contains the array you want to modify
     * @param string $key - This is the name of the Id field, defaults to 'id'
     * @param string $value - This is the name of the Value field, defaults to 'name'
     * @param boolean $isContact - The contact array has a lot of text as it's value that isn't inherently known.
     *
     * @return Array $key_value_array - {key:value, key:value} - Used for setting up dropdown lists.
     */
    public function make_key_value_array($data, $key = 'id', $value = 'name', $isContact = false) {
        $key_value_array = [];
        if (!is_array($data)) {
            return [];
        }

        if ($isContact) {
            foreach ($data as $item) {
                $key_value_array[$item[$key]] = $item['full_name'].", ".$item['cell_no'].", ".$item['work_no'].", ".$item['email'];
            }
        } else {
            foreach ($data as $item) {
                $key_value_array[$item[$key]] = $item[$value];
            }
        }

        return $key_value_array;
    }

    /**
     * Handle error messages in Exception.
     *
     * @param Exception $e Exception Object
     */
    protected function catchException($e)
    {
        $this->setError($e->getCode(), $e->getMessage());
    }

    /**
     * Handle error messages in SoapFault
     *
     * @param SoapFault $e SoapFault Object
     */
    protected function catchSoapFault($e)
    {
        $this->setError($e->faultcode, $e->faultstring);
    }

    /**
     * Add a new error
     *
     * @param string  $id   Error ID
     * @param string  $text Error text
     */
    protected function setError($id, $text)
    {
        $this->errors[ $id ] = $text;
    }

    /**
     * Retrieve errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if this instance has an error
     */
    public function hasErrors()
    {
        return ! empty($this->errors);
    }

    /**
     * Clears all the Errors
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * Disable Cached completely and retrieve data directly from the webservice
     */
    public function disableCache()
    {
        $this->check_cache = 0;
    }

    /**
     * Ignore Cached data and retrieve data directly from the webservice
     * Save returned data to Cache
     */
    public function ignoreCache()
    {
        $this->check_cache = 1;
    }

    /**
     * Check if cache exists before querying the webservice
     * If webservice was queried, save returned data to Cache
     */
    public function enableCache()
    {
        $this->check_cache = 2;
    }

    /**
     * Returns the clients default address
     *
     * @return int Address ID
     */
    public function getDefaultAddressId()
    {
        if (! $this->default_address_id) {
            $this->authenticate();
        }
        return $this->default_address_id;
    }
}
