<?php

namespace Infrastructure\Kiwi\core\http;

use Exception;
use JetBrains\PhpStorm\NoReturn;

class Response
{
    // === Common HTTP Codes ===
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_INTERNAL_ERROR = 500;

    // === JSON Response ===
    #[NoReturn]
    public static function json(array $data, int $statusCode = self::HTTP_OK): void
    {
        self::send('application/json', json_encode($data, JSON_THROW_ON_ERROR), $statusCode);
    }

    // === Plain Text Response ===
    #[NoReturn]
    public static function text(string $text, int $statusCode = self::HTTP_OK): void
    {
        self::send('text/plain', $text, $statusCode);
    }

    // === File Download Response ===
    #[NoReturn]
    public static function file(string $filePath, string $fileName = ''): void
    {
        if (!file_exists($filePath)) {
            self::notFound("File not found");
        }

        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="' . ($fileName ?: basename($filePath)) . '"');

        readfile($filePath);
        exit();
    }

    // === 404 Response ===
    #[NoReturn]
    public static function notFound(string $message = 'Not Found'): void
    {
        self::text($message, self::HTTP_NOT_FOUND);
    }

    // === Wrong Method ===
    #[NoReturn]
    public static function wrongMethod(string $method): void
    {
        self::setStatusCode(self::HTTP_METHOD_NOT_ALLOWED);
        header("Allow: GET, POST, PUT, DELETE");
        self::json(['error' => "Method $method not allowed"], self::HTTP_METHOD_NOT_ALLOWED);
    }

    // === Redirect ===
    #[NoReturn]
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit();
    }

    // === Internal Helpers ===

    public static function setStatusCode(int $statusCode): void
    {
        http_response_code($statusCode);
    }

    private static function setContentType(string $contentType): void
    {
        header('Content-Type: ' . $contentType);
    }

    #[NoReturn]
    private static function send(string $contentType, string $body, int $statusCode): void
    {
        self::setContentType($contentType);
        self::setStatusCode($statusCode);
        echo $body;
        exit();
    }
}
