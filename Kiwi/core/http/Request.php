<?php

namespace core\http;

use Exception;

class Request
{
    private $parameters; // ROUTE PARAMETERS

    public function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * GET ALL PARAMETERS FROM ROUTE AS AN ARRAY
     */
    public function getParams()
    {
        return $this->parameters;
    }

    /**
     * GET SINGLE PARAMETER FROM ROUTE
     */
    public function getParameter($index)
    {
        if (is_array($this->parameters)) {
            return $this->parameters[$index] ?? null;
        } elseif ($this->parameters instanceof Next) {
            return $this->parameters->passToRoute()[$index] ?? null;
        }
        return null;
    }

    /**
     * GET URI PATH
     */
    public static function getURIpath()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * GET QUERY PARAMETERS
     */
    public function getQueryParams(): array
    {
        return $_GET;
    }

    /**
     * Get the POST data from a submitted form
     */
    public function getFormData(): ?array
    {
        if ($this->getHTTPMethod() === 'POST') {
            return $_POST;
        }

        return null;
    }

    /**
     * Get a specific value from the POST data
     */
    public function getFormValue(string $key): ?string
    {
        $formData = $this->getFormData();

        return $formData[$key] ?? null;
    }


    /**
     * GET HTTP METHOD
     */
    public static function getHTTPMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * GET HTTP headers
     */
    public function getHeaders(): false|array
    {
        return getallheaders();
    }

    /**
     * Get specific HTTP header by name
     */
    public function getHeader(string $name)
    {
        $headers = $this->getHeaders();
        return $headers[$name] ?? null;
    }

    /**
     * GET REMOTE IP ADDRESS
     */
    public function getIPAddress()
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * GET USER-AGENT
     */
    public function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Get the request body content
     */
    public function getRequestBody(): false|string
    {
        return file_get_contents('php://input');
    }

    /**
     * Get the request content type
     */
    public function getContentType()
    {
        return $_SERVER['CONTENT_TYPE'] ?? null;
    }

    /**
     * Get request content length
     */
    public function getContentLength()
    {
        return $_SERVER['CONTENT_LENGTH'] ?? null;
    }

    /**
     * Get the request scheme (http or https)
     */
    public function getRequestScheme()
    {
        return $_SERVER['REQUEST_SCHEME'] ?? null;
    }

    /**
     * Get request scheme and host
     */
    public function getRequestSchemeAndHost(): ?string
    {
        $scheme = $this->getRequestScheme();
        $host = $this->getRequestHost();

        return $scheme && $host ? $scheme . '://' . $host : null;
    }

    /**
     * Get the request URL
     */
    public function getRequestURL(): ?string
    {
        $schemeAndHost = $this->getRequestSchemeAndHost();
        $uriPath = $this->getURIPath();

        return $schemeAndHost && $uriPath ? $schemeAndHost . $uriPath : null;
    }


    /**
     * Get request port
     */
    public function getRequestPort()
    {
        return $_SERVER['SERVER_PORT'] ?? null;
    }

    /**
     * Get request protocol
     */
    public function getRequestProtocol()
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? null;
    }

    /**
     * Get request time
     */
    public function getRequestTime()
    {
        return $_SERVER['REQUEST_TIME'] ?? null;
    }

    /**
     * Get server name
     */
    public function getServerName()
    {
        return $_SERVER['SERVER_NAME'] ?? null;
    }

    /**
     * Get server address
     */
    public function getServerAddress()
    {
        return $_SERVER['SERVER_ADDR'] ?? null;
    }

    /**
     * Get server port
     */
    public function getServerPort()
    {
        return $_SERVER['SERVER_PORT'] ?? null;
    }

    /**
     * Get server software
     */
    public function getServerSoftware()
    {
        return $_SERVER['SERVER_SOFTWARE'] ?? null;
    }

    /**
     * Get request timestamp
     */
    public function getRequestTimestamp()
    {
        return $_SERVER['REQUEST_TIME'] ?? null;
    }

    /**
     * Check if the request is an AJAX request
     */
    public function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Check if the request is secure (HTTPS)
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * Get request host
     */
    public function getRequestHost()
    {
        return $_SERVER['HTTP_HOST'] ?? null;
    }


    /**
     * Get the referring URL
     */
    public function getReferrer()
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Get the client's IP address
     */
    public function getClientIP()
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get the client's browser language preferences
     */
    public function getClientLanguages(): array
    {
        return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) : [];
    }


    /**
     * Validate and sanitize POST data
     * @throws Exception
     */
    private function validateAndSanitize(array $data): array
    {
        $validated = [];

        foreach ($data as $key => $value) {
            $filteredValue = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            if (empty($filteredValue)) {
                throw new Exception("Value for '$key' is required");
            }

            $validated[$key] = htmlspecialchars($filteredValue);
        }

        return $validated;
    }

    /**
     * Validate and sanitize form POST data
     */
    public function validateFormData(array $keys): ?array
    {
        $formData = $this->getFormData();

        if ($formData) {
            $validatedData = [];
            foreach ($keys as $key) {
                $value = $formData[$key] ?? null;
                if ($value !== null) {
                    $validatedData[$key] = htmlspecialchars($value);
                } else {
                    // Field is missing, consider it as an error
                    return null;
                }
            }
            return $validatedData;
        }

        return null;
    }
}
