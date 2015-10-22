<?php

namespace DavidDel\RestApi\RestApiExtension;

use Behat\Behat\Context\Context;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Features context.
 */
class RestApiContext implements Context
{
    protected $format;

    public function __construct($baseUrl)
    {
        $config = ['base_url' => $baseUrl];

        $this->client = new Client($config);
    }

    /**
     * @When /^I request "(GET|PUT|POST|DELETE) ([^"]*)"$/
     */
    public function iRequest($httpMethod, $resource)
    {
        $method = strtolower($httpMethod);

        try {
            switch ($httpMethod) {
                case 'PUT':
                    $this->response = $this
                        ->client
                        ->$method($resource, null, $this->requestPayload);
                    break;
                case 'POST':
                    $post = json_decode($this->requestPayload, true);
                    $this->response = $this
                        ->client
                        ->$method($resource, array('body' => $post));
                    break;
                default:
                    $this->response = $this
                        ->client
                        ->$method($resource);
            }
        } catch (BadResponseException $e) {

            $response = $e->getResponse();

            // Sometimes the request will fail, at which point we have
            // no response at all. Let Guzzle give an error here, it's
            // pretty self-explanatory.
            if ($response === null) {
                throw $e;
            }

            $this->response = $e->getResponse();
        }
    }







    /**
     * @Then /^the response format is "([^"]*)"$/
     */
    public function theResponseFormatIs($format)
    {
        $this->format = $format;

        $this->getDataResponse();
    }

    /**
     * @Given /^the response contains at least one transaction$/
     */
    public function theResponseContainsAtLeastOneTransaction()
    {
        $data = json_decode($this->getRawResponse());

        if (count($data) < 1) {
            throw new Exception("Response did not contain at least one transaction");
        }
    }

    /**
     * @Given /^the response has a "([^"]*)" property$/
     */
    public function theResponseHasAProperty($propertyPath)
    {
        $this->hasProperty($propertyPath);
    }

    /**
     * @Given /^the response has no "([^"]*)" property$/
     */
    public function theResponseHasNoProperty($propertyPath)
    {
        $this->hasNoProperty($propertyPath);
    }

    /**
     * @Given /^the response has "([^"]*)" "([^"]*)"$/
     */
    public function theResponseHasCountProperty($count, $propertyPath)
    {
        $this->hasProperty($propertyPath, $count);
    }

    /**
     * @Then /^the "([^"]*)" property equals "([^"]*)"$/
     */
    public function thePropertyEquals($propertyPath, $propertyValue)
    {
        $this->hasProperty($propertyPath, null, $propertyValue);
    }

    protected function getRawResponse()
    {
        return $this->getSession()->getDriver()->getContent();
    }

    protected function getDataResponse($response = null, $format = null)
    {
        $response = $response ?: $this->getRawResponse();
        $format = $format ?: $this->format;

        $data = null;
        switch ($format) {
            case 'json':
                $data = $this->getJsonData($response);
                break;
            case 'xml':
                $data = $this->getXmlData($response);
                break;
            default:
                throw new \Exception(sprintf('The format "%s" does not supported', $format));
        }

        return $data;
    }

    protected function getJsonData($response = null)
    {
        $response = $response ?: $this->getRawResponse();

        $data = json_decode($response, true);

        if (empty($data)) {
            throw new \Exception('The response format is not JSON\n' . $response);
        }

        return $data;
    }

    protected function getXmlData($response = null)
    {
        $response = $response ?: $this->getRawResponse();

        libxml_use_internal_errors();
        $data = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (!empty(libxml_get_errors())) {
            throw new \Exception('The response format is not XML\n' . $response);
        }

        return $this->getJsonData(json_encode($data));
    }

    protected function getProperty($propertyPath)
    {
        $data = $this->getDataResponse();

        $properties = explode('.', $propertyPath);

        $current = $data;
        foreach ($properties as $property) {
            if ($property != '') {
                if (!isset($current[$property])) {
                    throw new Exception("Property '" . $propertyPath . "' is not set!\n");
                } else {
                    $current = $current[$property];
                }
            }
        }

        return $current;
    }

    protected function hasProperty($propertyPath, $count = null, $equal = null)
    {
        $property = $this->getProperty($propertyPath);

        if ($count !== null && $property !== null) {
            if (count($property) != intval($count)) {
                throw new Exception('Nb Properties "'.$propertyPath.'" mismatch! (given: '.$count.', match: '.count($property).')');
            }
        }

        if ($equal !== null && $property !== null) {
            if ($property != $equal) {
                throw new Exception('Property value mismatch! (given: '.$equal.', match: '.$property.')');
            }
        }
    }

    protected function hasNoProperty($propertyPath)
    {
        try {
            $property = $this->getProperty($propertyPath);
        } catch (Exception $e) {
            return true;
        }

        throw new Exception("Property '" . $propertyPath . "' is set!\n");
    }
}