<?php

declare(strict_types=1);

namespace SermonBrowser\Http;

use SermonBrowser\Ajax\LegacyAjaxHandler;
use SermonBrowser\Frontend\StyleOutput;
use SermonBrowser\Http\Handler\FileDownloadHandler;
use SermonBrowser\Http\Handler\FileRedirectHandler;
use SermonBrowser\Http\Handler\UrlDownloadHandler;
use SermonBrowser\Http\Handler\UrlRedirectHandler;

/**
 * Intercepts WordPress requests at the earliest opportunity.
 *
 * Routes requests to appropriate handlers for:
 * - AJAX data requests
 * - CSS stylesheet output
 * - File downloads (local and external)
 * - File redirects (local and external)
 *
 * @since 0.6.0
 */
class RequestInterceptor
{
    /**
     * Intercept and handle special requests.
     *
     * This method is called early in the WordPress lifecycle to handle
     * requests that don't require the full WordPress framework.
     */
    public static function intercept(): void
    {
        // AJAX handling (delegate to existing handler)
        if (self::isAjaxRequest()) {
            LegacyAjaxHandler::handle();
            return;
        }

        // CSS handling (delegate to existing handler)
        if (self::isCssRequest()) {
            StyleOutput::output();
            return;
        }

        // File download handling
        if (self::isDownloadRequest()) {
            self::handleDownload();
            return;
        }

        // File show/redirect handling
        if (self::isShowRequest()) {
            self::handleShow();
        }
    }

    /**
     * Check if this is an AJAX request.
     */
    private static function isAjaxRequest(): bool
    {
        return isset($_POST['sermon']) && $_POST['sermon'] == 1;
    }

    /**
     * Check if this is a CSS stylesheet request.
     */
    private static function isCssRequest(): bool
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        return stripos($requestUri, 'sb-style.css') !== false || isset($_GET['sb-style']);
    }

    /**
     * Check if this is a file download request.
     */
    private static function isDownloadRequest(): bool
    {
        if (!isset($_GET['download'])) {
            return false;
        }
        return isset($_GET['file_name']) || isset($_REQUEST['url']);
    }

    /**
     * Check if this is a file show/redirect request.
     */
    private static function isShowRequest(): bool
    {
        if (!isset($_GET['show'])) {
            return false;
        }
        return isset($_GET['file_name']) || isset($_REQUEST['url']);
    }

    /**
     * Handle download requests.
     *
     * Routes to appropriate handler based on whether it's a local file or URL.
     */
    private static function handleDownload(): void
    {
        if (isset($_GET['file_name'])) {
            FileDownloadHandler::handle();
        } elseif (isset($_REQUEST['url'])) {
            UrlDownloadHandler::handle();
        }
    }

    /**
     * Handle show/redirect requests.
     *
     * Routes to appropriate handler based on whether it's a local file or URL.
     */
    private static function handleShow(): void
    {
        if (isset($_GET['file_name'])) {
            FileRedirectHandler::handle();
        } elseif (isset($_REQUEST['url'])) {
            UrlRedirectHandler::handle();
        }
    }
}
