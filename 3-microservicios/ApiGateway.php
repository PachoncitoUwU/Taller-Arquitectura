<?php

// Simulación de un cliente HTTP básico para el Gateway
class SimpleHttpClient {
    public function request(string $url, int $timeout): string {
        $context = stream_context_create([
            'http' => ['timeout' => $timeout, 'ignore_errors' => true]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Timeout o error de red al conectar al servicio.");
        }
        
        return $response;
    }
}

class ApiGateway {
    private array $routes;
    private SimpleHttpClient $httpClient;

    public function __construct() {
        // Mapeo de microservicios a sus respectivas URL de red independientes
        $this->routes = [
            'users'    => 'http://user-service.local/api/',
            'orders'   => 'http://order-service.local/api/',
            'payments' => 'http://payment-service.local/api/'
        ];
        $this->httpClient = new SimpleHttpClient();
    }

    public function routeRequest(string $service, string $endpoint, string $method = 'GET'): array {
        $startTime = microtime(true);

        // Validar si el microservicio destino existe en el catálogo
        if (!isset($this->routes[$service])) {
            return [
                'code' => 404,
                'error' => "Servicio '$service' no encontrado en el API Gateway."
            ];
        }

        $targetUrl = $this->routes[$service] . $endpoint;
        $timeout = 3; // Timeout estricto de 3 segundos solicitado

        try {
            // Se realiza la petición externa a través de la red simulada
            $responseBody = $this->httpClient->request($targetUrl, $timeout);
            $statusCode = 200;
            $result = json_decode($responseBody, true) ?? ['data' => $responseBody];
            
        } catch (Exception $e) {
            $statusCode = 504; // Gateway Timeout
            $result = [
                'code' => 504,
                'error' => 'Gateway Error: El servicio no respondió dentro del límite de tiempo esperado.',
                'details' => $e->getMessage()
            ];
        }

        $responseTime = microtime(true) - $startTime;
        $this->logRequest($method, $service, $responseTime, $statusCode);

        return $result;
    }

    private function logRequest(string $method, string $service, float $responseTime, int $statusCode): void {
        $formattedTime = number_format($responseTime, 4);
        $logLine = sprintf(
            "[%s] UTC - METHOD: %s | SERVICE: %s | RESPONSE TIME: %ss | STATUS: %d\n",
            date('Y-m-d H:i:s'), $method, strtoupper($service), $formattedTime, $statusCode
        );
        
        // Escribe el log en el sistema o salida estándar
        file_put_contents('gateway_access.log', $logLine, FILE_APPEND);
        // Opcional para pruebas en consola: echo $logLine;
    }
}

// Ejemplo de Ejecución:
// $gateway = new ApiGateway();
// $response = $gateway->routeRequest('users', 'v1/profile?id=12', 'GET');
// print_r($response);