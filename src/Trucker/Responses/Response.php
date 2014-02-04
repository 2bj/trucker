<?php

namespace Trucker\Responses;

use Illuminate\Container\Container;

class Response
{

    /**
     * The IoC Container
     *
     * @var Illuminate\Container\Container
     */
    protected $app;

    /**
     * Response object managed by this
     * class
     * 
     * @var Guzzle\Http\Message\Response
     */
    protected $response;

    /**
     * Build a new RequestManager
     *
     * @param Container $app
     * @param Client    $client
     */
    public function __construct(Container $app, \Guzzle\Http\Message\Response $response = null)
    {
        $this->app = $app;
        $this->response = $response;
    }


    /**
     * Getter to access the IoC Container
     * 
     * @return Container
     */
    public function getApp()
    {
        return $this->app;
    }


    /**
     * Magic getter function to return any properties
     * on the underlying Guzzle\Http\Message\Response object
     * 
     * @param  string $property the property 
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this->response, $property)) {
            return $this->response->{$property};
        }

        return null;
    }


    /**
     * Magic function to pass methods not found
     * on this class down to the guzzle response
     * object that is being wrapped
     * 
     * @param  string $method name of called method
     * @param  array  $args   arguments to the method
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!method_exists($this, $method)) {
            return call_user_func_array(
                array($this->response, $method),
                $args
            );
        }
    }


    /**
     * Get an option from the config file
     *
     * @param  string $option
     *
     * @return mixed
     */
    public function getOption($option)
    {
        return $this->app['config']->get('trucker::'.$option);
    }


    /**
     * Create a new instance of the given model.
     *
     * @param  Container $app
     * @param  \Guzzle\Http\Message\Response $response
     * @return \Trucker\Response
     */
    public function newInstance(Container $app, \Guzzle\Http\Message\Response $response)
    {
    
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        $r = new static($app, $response);
    
        return $r;
    }


    /**
     * Function to take a response object and convert it
     * into an array of data that is ready for use
     *
     * @return array           Parsed array of data
     */
    public function parseResponseToData()
    {
        $transporter = $this->getOption('transporter');

        //convert response data into usable PHP array
        switch ($transporter) {
            case 'json':
                $data = $this->response->json();
                break;
            case 'xml':
                $data = $this->response->xml();
                break;
            default:
                $data = $this->response->json();
                break;
        }

        return $data;
    }


    /**
     * Function to take a response string (as a string) and depending on
     * the type of string it is, parse it into an object.
     *
     * @return object
     */
    public function parseResponseStringToObject()
    {

        $responseStr = $this->response->getBody(true);
        $transporter = $this->getOption('transporter');

        switch ($transporter) {
            case 'json':
                $data = json_decode($responseStr);
                break;

            case 'xml':
                $data = simplexml_load_string($responseStr);
                break;

            default:
                $data = json_decode($responseStr);
                break;
        }

        return $data;
    }
}