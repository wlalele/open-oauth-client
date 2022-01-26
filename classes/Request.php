<?php

/**
 * Request class
 *
 * Avoid using php globals directly and store some const
 */
class Request
{
    public const METHOD_POST = 'POST';
    public const METHOD_GET = 'GET';

    public const MIME_JSON = 'application/json';
    public const MIME_FORM_URLENCODED = 'application/x-www-form-urlencoded';

    final public static function getQueryParameter(string $key)
    {
        return $_GET[$key] ?? null;
    }

    final public static function getPostParameter(string $key)
    {
        return $_POST[$key] ?? null;
    }

    final public static function getPostParameters(): array
    {
        return $_POST;
    }

    final public static function getUri(): string
    {
        return $_SERVER['REQUEST_URI'];
    }

    final public static function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }
}
