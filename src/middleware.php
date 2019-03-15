<?php
// It will add the Access-Control-Allow-Methods header to every request
$app->add(new Tuupola\Middleware\CorsMiddleware(
    [
        "headers.allow" => [
            "Content-Type",
            "Accept",
            "Accept-Encoding",
            "Connection",
            "Host",
            "Origin",
            "Referer",
            "User-Agent"
            ]
        ]
));

class LoggerMiddleware
{
    private $appContext;
    private $logger;

    public function __construct($appContext) {
        $this->appContext = $appContext;
        $this->logger = $appContext->get('logger');
    }

    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        $this->logger->addInfo("   -/-      -/-      -/-      -/-      -/-      -/-      -/-      -/-      -/-      -/-      -/-   ");
        $this->logger->addInfo("----- Request Headers: ------");
        $headers = $request->getHeaders();
        foreach ($headers as $name => $values) {
            $this->logger->addInfo($name . ": " . implode(", ", $values));
        }        
        $this->logger->addInfo("----- Request Body: ------");
        $this->logger->addInfo(print_r($request->getBody(), true));
        $this->logger->addInfo("----- Request Parsed Body: ------");
        $this->logger->addInfo(print_r($request->getParsedBody(), true));

        $response = $next($request, $response);

        $this->logger->addInfo("Response Status: " . $response->getStatusCode());
        $this->logger->addInfo("----- Response Headers: ------");
        $headers = $response->getHeaders();
        foreach ($headers as $name => $values) {
            $this->logger->addInfo($name . ": " . implode(", ", $values));
        }        

        return $response;
    }
}

//$app->add( new LoggerMiddleware($app->getContainer()));