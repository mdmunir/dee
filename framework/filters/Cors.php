<?php

namespace dee\filters;

use Dee;
use dee\base\Filter;

/**
 * Description of Cors
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Cors extends Filter
{
    /**
     * @var array Basic headers handled for the CORS requests.
     */
    public $cors = [
        'Origin' => ['*'],
        'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        'Access-Control-Request-Headers' => ['*'],
        'Access-Control-Allow-Credentials' => null,
        'Access-Control-Max-Age' => 86400,
        'Access-Control-Expose-Headers' => [],
    ];

    public function before(): boolean
    {
        $request = Dee::$app->request;
        $response = Dee::$app->response;

        $requestHeaders = $responseHeaders = [];
        foreach (array_keys($this->cors) as $name) {
            $sname = 'HTTP_' . strtoupper(str_replace([' ', '-'], ['_', '_'], $name));
            if (isset($_SERVER[$sname])) {
                $requestHeaders[$name] = $_SERVER[$sname];
            }
        }

        if (isset($requestHeaders['Origin'], $this->cors['Origin'])) {
            if (in_array($requestHeaders['Origin'], $this->cors['Origin'], true)) {
                $responseHeaders['Access-Control-Allow-Origin'] = $requestHeaders['Origin'];
            }
            if (in_array('*', $this->cors['Origin'], true)) {
                if (!isset($this->cors['Access-Control-Allow-Credentials']) || !$this->cors['Access-Control-Allow-Credentials']) {
                    $responseHeaders['Access-Control-Allow-Origin'] = '*';
                }
            }
        }

        $requestHeaderField = 'Access-Control-Request-Headers';
        $responseHeaderField = 'Access-Control-Allow-Headers';
        if (!isset($requestHeaders[$requestHeaderField], $this->cors[$requestHeaderField])) {
            return;
        }
        if (in_array('*', $this->cors[$requestHeaderField])) {
            $responseHeaders[$responseHeaderField] = $this->headerize($requestHeaders[$requestHeaderField]);
        } else {
            $requestedData = preg_split('/[\\s,]+/', $requestHeaders[$requestHeaderField], -1, PREG_SPLIT_NO_EMPTY);
            $acceptedData = array_uintersect($requestedData, $this->cors[$requestHeaderField], 'strcasecmp');
            if (!empty($acceptedData)) {
                $responseHeaders[$responseHeaderField] = implode(', ', $acceptedData);
            }
        }

        if (isset($requestHeaders['Access-Control-Request-Method'])) {
            $responseHeaders['Access-Control-Allow-Methods'] = implode(', ', $this->cors['Access-Control-Request-Method']);
        }

        if (isset($this->cors['Access-Control-Allow-Credentials'])) {
            $responseHeaders['Access-Control-Allow-Credentials'] = $this->cors['Access-Control-Allow-Credentials'] ? 'true'
                    : 'false';
        }

        if (isset($this->cors['Access-Control-Max-Age']) && $request->getMethod() === 'OPTIONS') {
            $responseHeaders['Access-Control-Max-Age'] = $this->cors['Access-Control-Max-Age'];
        }

        if (isset($this->cors['Access-Control-Expose-Headers'])) {
            $responseHeaders['Access-Control-Expose-Headers'] = implode(', ', $this->cors['Access-Control-Expose-Headers']);
        }

        if (isset($this->cors['Access-Control-Allow-Headers'])) {
            $responseHeaders['Access-Control-Allow-Headers'] = implode(', ', $this->cors['Access-Control-Allow-Headers']);
        }

        foreach ($responseHeaders as $name => $value) {
            $response->setHeader($name, $value);
        }

        if ($request->getMethod() === 'OPTIONS' && $request->header('Access-Control-Request-Method')) {
            $response->statusCode = 200;
            return false;
        }
        return true;
    }
}
