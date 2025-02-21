<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpNotes\Helpers;

class Response
{
    /**
     * HTTP response status codes
     */
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_NO_CONTENT = 204;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * Content types
     */
    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_XML = 'application/xml';

    /**
     * Send a JSON response
     * 
     * @param mixed $data Data to be sent
     * @param int $status HTTP status code
     * @param array $headers Additional headers
     */
    public static function json($data, $status = self::HTTP_OK, $headers = [])
    {
        http_response_code($status);
        header('Content-Type: ' . self::CONTENT_TYPE_JSON . '; charset=utf-8');

        try {
            ob_clean();
            ob_flush();
        } catch (\Throwable $th) {
            $error = $th->getMessage();
            $data['ob_clean_error'] = $error;
        }

        // Add any additional headers
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send an HTML response
     * 
     * @param string $content HTML content to be sent
     * @param int $status HTTP status code
     * @param array $headers Additional headers
     */
    public static function html($content, $status = self::HTTP_OK, $headers = [])
    {
        http_response_code($status);
        header('Content-Type: ' . self::CONTENT_TYPE_HTML . '; charset=utf-8');

        // Add any additional headers
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        echo $content;
        exit;
    }

    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $status HTTP error status code
     * @param array $details Additional error details
     */
    public static function error($message, $status = self::HTTP_BAD_REQUEST, $details = [])
    {
        $errorResponse = [
            'error' => true,
            'message' => $message,
            'details' => $details,
        ];

        self::json($errorResponse, $status);
    }

    /**
     * Redirect to another page
     * 
     * @param string $url URL to redirect to
     * @param int $status HTTP redirect status code
     */
    public static function redirect($url, $status = 302)
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }

    /**
     * Download file response
     * 
     * @param string $filePath Path to the file
     * @param string|null $fileName Optional custom filename
     * @param bool $deleteAfterSend Whether to delete file after sending
     */
    public static function downloadFile($filePath, $fileName = null, $deleteAfterSend = false)
    {
        if (!file_exists($filePath)) {
            self::error('File not found', self::HTTP_NOT_FOUND);
        }

        $fileName = $fileName ?? basename($filePath);
        $fileSize = filesize($filePath);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: no-cache');

        readfile($filePath);

        if ($deleteAfterSend) {
            unlink($filePath);
        }

        exit;
    }
}
