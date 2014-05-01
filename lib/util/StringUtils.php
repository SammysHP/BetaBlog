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

    /**
     * Format date and time human readable.
     *
     * Currently limited to de_DE.
     *
     * @param int $then The other date and time as Unix timestamp
     * @param int $now The reference date and time
     * @return String Date and time in human readable format
     */
    public static function formatHumanDate($then, $now = -1) {
        $then = (int) $then;
        $now = (int) $now;

        if ($now < 0) {
            $now = time();
        }

        $difference = $now - $then;

        // In the future
        if ($difference < 0) {
            return "zukünftig";
        }

        // < 1 minute
        if ($difference < 60) {
            return "gerade eben";
        }

        // < 1 hour
        if ($difference < 60 * 60) {
            $minutes = (int) ($difference / 60);
            if ($minutes > 1) {
                return "vor $minutes Minuten";
            } else {
                return "vor einer Minute";
            }
        }

        // < 1 day
        if ($difference < 60 * 60 * 24) {
            $hours = (int) ($difference / (60 * 60));
            if ($hours > 1) {
                return "vor $hours Stunden";
            } else {
                return "vor einer Stunde";
            }
        }

        // < 1 week
        if ($difference < 60 * 60 * 24 * 7) {
            $days = (int) ($difference / (60 * 60 * 24));
            if ($days > 2) {
                return "vor $days Tagen";
            } elseif ($days > 1) {
                return "vorgestern";
            } else {
                return "gestern";
            }
        }

        // < 1 month
        if ($difference < 60 * 60 * 24 * 31) {
            $weeks = (int) ($difference / (60 * 60 * 24 * 7));
            if ($weeks > 1) {
                return "vor $weeks Wochen";
            } else {
                return "vor einer Woche";
            }
        }

        // < 1 year
        if ($difference < 60 * 60 * 24 * 365) {
            $months = (int) ($difference / (60 * 60 * 24 * 31));
            if ($months > 1) {
                return "vor $months Monaten";
            } else {
                return "vor einem Monat";
            }
        }

        // everything else
        $years = (int) ($difference / (60 * 60 * 24 * 365));
        if ($years > 1) {
            return "vor $years Jahren";
        } else {
            return "vor einem Jahr";
        }
    }
}
