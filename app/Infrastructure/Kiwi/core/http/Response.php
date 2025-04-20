<?php

namespace Infrastructure\Kiwi\core\http;;


use Couchbase\View;
use Exception;
use Infrastructure\Kiwi\core\Router;
use JetBrains\PhpStorm\NoReturn;

class Response
{
    public const HTTP_OK = 200;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;


    /**
     * Render a 404 page.
     * @throws Exception
     */
    #[NoReturn] public static function notFound(): void
    {
        self::setStatusCode(self::HTTP_NOT_FOUND);
        echo "Not found";
        exit();
    }


    /**
     * Render a message if an incorrect HTTP method was used on a route.
     * @param string $method - HTTP Method
     * @throws Exception
     */
    #[NoReturn] public static function wrongMethod(string $method): void
    {
        self::setStatusCode(self::HTTP_METHOD_NOT_ALLOWED);
        header("Allow: GET, POST, PUT, DELETE");
        $view = new View('405');
        $view->add('method', $method);
        self::render($view, true);
        exit();
    }


    /**
     * Render a view.
     * @param string|View $view - View to render
     * @param bool $isErrorRoute - Flag for error route
     * @return void - Returns the rendered view
     * @throws Exception
     */
    public static function render(string|View $view, bool $isErrorRoute = false): void
    {
        $viewPath = $isErrorRoute ? Router::getErrorViews() : Router::getViewsFolder();

        if (!file_exists($viewPath )) {
            Response::notFound();
        }else{
            if(is_string($view)){
                $newView = new View($view);
                $newView->render($viewPath);
            }else{
                $view->render($viewPath);
            }
        }

    }

    private static function getViewErrors(): string{
        return Router::getErrorViews();
    }

    /**
     * REDIRECT RESPONSE
     * @param string $url - URL target to redirect to
     * @param int $statusCode
     */
    #[NoReturn] public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit();
    }

    /**
     * SEND JSON-ENCODED DATA AS RESPONSE
     * @param array $data - Data to send
     * @param int $statusCode - HTTP status code
     */
    #[NoReturn] public static function json(array $data, int $statusCode = self::HTTP_OK): void
    {
        self::setContentType('application/json');
        self::setStatusCode($statusCode);
        echo json_encode($data);
        exit();
    }

    /**
     * SEND TEXT AS RESPONSE
     * @param string $text - Text to send
     * @param int $statusCode - HTTP status code
     */
    #[NoReturn] public static function text(string $text, int $statusCode = self::HTTP_OK): void
    {
        self::setContentType('text/plain');
        self::setStatusCode($statusCode);
        echo $text;
        exit();
    }

    /**
     * SEND FILE AS RESPONSE
     * @param string $filePath - Path to file to send
     * @param string $fileName - Name of file to send (optional)
     * @return void - Sends file as response
     * @throws Exception
     */
    #[NoReturn] public static function file(string $filePath, string $fileName = ''): void
    {
        if (!file_exists($filePath)) {
            self::notFound();
        }

        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="' . ($fileName ?: basename($filePath)) . '"');

        readfile($filePath);
        exit();
    }

    /**
     * Set HTTP response status code
     * @param int $statusCode - HTTP status code
     */
    public static function setStatusCode(int $statusCode): void
    {
        http_response_code($statusCode);
    }

    /**
     * Set content type for the response
     * @param string $contentType - Content type for the response
     */
    private static function setContentType(string $contentType): void
    {
        header('Content-Type: ' . $contentType);
    }
}


