<?php
namespace util;

class StringUtils {
    const SHORTEN_NONE = 1;
    const SHORTEN_SIMPLIFIED = 2;
    const SHORTEN_ELLIPSIS = 4;

    private function __construct() {}

    /**
     * Add html links to plain text.
     *
     * Input will be html-escaped.
     *
     * @see http://stackoverflow.com/questions/1188129/replace-urls-in-text-with-html-links
     */
    public static function linkify($string, $withoutProtocol = false, $shorten = self::SHORTEN_NONE) {
        $rexProtocol = '((?:https?|ftp)://)' . ($withoutProtocol ? '?' : '');
        $rexDomain   = '((?:[-a-zA-Z0-9]{1,63}\.)+[-a-z0-9]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})';
        $rexPort     = '(:[0-9]{1,5})?';
        $rexPath     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
        $rexQuery    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
        $rexFragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';

        return preg_replace_callback(
            "&\\b$rexProtocol$rexDomain$rexPort$rexPath$rexQuery$rexFragment(?=[?.!,;:\"]?(\s|$))&",
            function ($match) use ($shorten) {
                // Prepend http:// if no protocol specified
                $href = $match[1] ? $match[0] : "http://{$match[0]}";
                $text = $match[1] . $match[2] . $match[3] . $match[4] . $match[5] . $match[6];

                if ((StringUtils::SHORTEN_SIMPLIFIED & $shorten) == StringUtils::SHORTEN_SIMPLIFIED) {
                    $text = $match[2] . $match[3] . $match[4];
                }
                if ((StringUtils::SHORTEN_ELLIPSIS & $shorten) == StringUtils::SHORTEN_ELLIPSIS && strlen($text) > 40) {
                    $text = substr($text, 0, 30) . '...' . substr($text, -10);
                }

                return '<a href="' . $href . '">' . $text . '</a>';
            },
            htmlspecialchars($string)
        );
    }
}
