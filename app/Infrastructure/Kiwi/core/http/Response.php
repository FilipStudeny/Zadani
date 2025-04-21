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

    private static bool $sent = false;

    #[NoReturn]
    public static function json(array $data, int $statusCode = self::HTTP_OK): void
    {
        self::send('application/json', json_encode($data, JSON_THROW_ON_ERROR), $statusCode);
    }

    #[NoReturn]
    public static function text(string $text, int $statusCode = self::HTTP_OK): void
    {
        self::send('text/plain', $text, $statusCode);
    }

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

    #[NoReturn]
    public static function notFound(string $message = 'Not Found'): void
    {
        self::json(['error' => $message], self::HTTP_NOT_FOUND);
    }

    #[NoReturn]
    public static function badRequest(string $message = 'Bad Request'): void
    {
        self::json(['error' => $message], self::HTTP_BAD_REQUEST);
    }

    #[NoReturn]
    public static function wrongMethod(string $method): void
    {
        header("Allow: GET, POST, PUT, DELETE");
        self::json(['error' => "Method $method not allowed"], self::HTTP_METHOD_NOT_ALLOWED);
    }

    #[NoReturn]
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit();
    }

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
        if (self::$sent) return;

        self::$sent = true;
        self::setContentType($contentType);
        self::setStatusCode($statusCode);
        echo $body;
    }
}
