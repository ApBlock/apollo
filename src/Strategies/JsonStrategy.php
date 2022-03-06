<?php

namespace ApBlock\Apollo\Strategies;

use ApBlock\Apollo\Utils\APIResponseBuilder;
use \Exception;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception as HttpException;
use League\Route\Route;
use League\Route\Strategy\StrategyInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class JsonStrategy implements StrategyInterface
{
    private $content_type = 'application/json';

    /**
     * {@inheritdoc}
     */
    public function getCallable(Route $route, array $vars)
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($route, $vars) {
            $response = call_user_func_array($route->getCallable(), array($request, $response, $vars));

            if (!$response instanceof ResponseInterface) {
                throw new RuntimeException(
                    'Route callables must return an instance of (Psr\Http\Message\ResponseInterface)'
                );
            }

            $response = $this->setHeader($response);

            return $next($request, $response);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecorator(NotFoundException $exception)
    {
        return function /** @noinspection PhpUnusedParameterInspection */ (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            $apiResponseBuilder = new APIResponseBuilder(404, "Method not found");
            $response->getBody()->write($apiResponseBuilder->build());
            return $this->setHeader($response)->withStatus(404);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception)
    {
        return function /** @noinspection PhpUnusedParameterInspection */ (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            $apiResponseBuilder = new APIResponseBuilder(405, "Method not allowed");
            $response->getBody()->write($apiResponseBuilder->build());
            return $this->setHeader($response)->withStatus(405);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionDecorator(Exception $exception)
    {
        return function /** @noinspection PhpUnusedParameterInspection */ (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            $response = $this->setHeader($response);

            if ($exception instanceof UnauthorizedException) {
                $apiResponseBuilder = new APIResponseBuilder(401, "Unauthorized");
                $response->getBody()->write($apiResponseBuilder->build());
                return $response;
            }

            if ($exception instanceof HttpException) {
                $response = $response->withStatus($exception->getStatusCode());
                $message = $exception->getMessage();
                $data = array();
                try {
                    $messageDecoded = json_decode($message, true);
                    if (!empty($messageDecoded)) {
                        if (isset($messageDecoded["message"])) {
                            $message = $messageDecoded["message"];
                        }
                        if (isset($messageDecoded["data"])) {
                            $data = $messageDecoded["data"];
                        }
                    }
                } catch (Exception $e) {
                }
                
                $apiResponseBuilder = new APIResponseBuilder($exception->getStatusCode(), $message, $data);
                $response->getBody()->write($apiResponseBuilder->build());

                return $response;
            }
            $apiResponseBuilder = new APIResponseBuilder(500, "Internal server error");
            $response->getBody()->write($apiResponseBuilder->build());
            return $response;
        };
    }

    public function setHeader(ResponseInterface $response)
    {
        if (!$response->hasHeader('content-type')) {
            $response = $response->withHeader('content-type', $this->content_type);
        }
        return $response;
    }
}
