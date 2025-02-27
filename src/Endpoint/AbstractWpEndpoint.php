<?php

namespace Vnn\WpApiClient\Endpoint;

use GuzzleHttp\Psr7\Request;
use RuntimeException;
use Vnn\WpApiClient\WpClient;

/**
 * Class AbstractWpEndpoint
 * @package Vnn\WpApiClient\Endpoint
 */
abstract class AbstractWpEndpoint
{
    /**
     * @var WpClient
     */
    protected $client;

    /**
     * Users constructor.
     * @param WpClient $client
     */
    public function __construct(WpClient $client)
    {
        $this->client = $client;
    }

    abstract protected function getEndpoint();
    
    /**
     * @param int $id
     * @param array $params - parameters that can be passed to GET
     *        e.g. for tags: https://developer.wordpress.org/rest-api/reference/tags/#arguments
     * @return array
     * @throws \RuntimeException
     */
    public function getResponse($id = null, array $params = null)
    {
        $uri = $this->getEndpoint();
        $uri .= (is_null($id)?'': '/' . $id);
        $uri .= (is_null($params)?'': '?' . http_build_query($params));

        $request = new Request('GET', $uri);
        $response = $this->client->send($request);

        if ($response->hasHeader('Content-Type')
            && substr($response->getHeader('Content-Type')[0], 0, 16) === 'application/json') {
            return $response;
        }

        throw new RuntimeException('Unexpected response');
    }

    /**
     * @return int
     * @throws \RuntimeException
     */
    public function numFound(array $params = array('per_page' => 1))
    {
    	try {
		$response = $this->getResponse(null, $params);
		$num = (int) $response->getHeader('X-WP-Total')[0];
		return $num;
	}
	catch (RuntimeException $e) {
		throw $e;
	}
    }

    /**
     * @param int $id
     * @param array $params - parameters that can be passed to GET
     *        e.g. for tags: https://developer.wordpress.org/rest-api/reference/tags/#arguments
     * @return array
     * @throws \RuntimeException
     */
    public function get($id = null, array $params = null)
    {
    	try {
	$response = $this->getResponse($id, $params);
	return json_decode($response->getBody()->getContents(), true);
	}
	catch (RuntimeException $e) {
		throw $e;
	}
    }

    /**
     * @param array $data
     * @return array
     * @throws \RuntimeException
     */
    public function save(array $data)
    {
        $url = $this->getEndpoint();

        if (isset($data['id'])) {
            $url .= '/' . $data['id'];
            unset($data['id']);
        }

        $request = new Request('POST', $url, ['Content-Type' => 'application/json'], json_encode($data));
        $response = $this->client->send($request);

        if ($response->hasHeader('Content-Type')
            && substr($response->getHeader('Content-Type')[0], 0, 16) === 'application/json') {
            return json_decode($response->getBody()->getContents(), true);
        }

        throw new RuntimeException('Unexpected response');
    }
}
