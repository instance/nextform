<?php

/**
 *
 */
trait HtmlTestLogger {
    static protected $allHtml;

    protected function logMethod($method) {
        self::$allHtml .= '<h3>' . $method . "</h3>\n";
    }

    protected function logResult($block, $message = null) {
        self::$allHtml .= '<h4>' . ($message ? htmlentities($message) : '(assertion)') . "</h4>\n";
        $closing = clone $block;
        $closing->close();
        self::$allHtml .= $closing->body . "<hr/>\n";
    }

}
