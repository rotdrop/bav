<?php

namespace rotdrop\bav;

/**
 * Mock for PHP's time() built-in.
 *
 * @license WTFPL
 * @author Markus Malkusch <markus@malkusch.de>
 */
class TimeMock
{

    /**
     * @var int
     */
    private static $time = null;

    public static function time()
    {
        return is_null(self::$time) ? \time() : self::$time;
    }

    /**
     * Disable mocking of the time.
     */
    public static function disable()
    {
        self::$time = null;
    }

    /**
     * Sets the moc time.
     *
     * @param int $time moc time
     */
    public static function setTime($time)
    {
        return self::$time = $time;
    }
}

namespace rotdrop\bav\DataBackend\Update;

use rotdrop\bav\TimeMock;

function time()
{
    return TimeMock::time();
}
