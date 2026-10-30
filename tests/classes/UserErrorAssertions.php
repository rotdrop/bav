<?php

namespace malkusch\bav;

trait UserErrorAssertions
{
    private function assertUserError(int $level, string $message, callable $operation): void
    {
        $errors = array();
        set_error_handler(function ($severity, $text) use (&$errors) {
            $errors[] = array($severity, $text);
            return true;
        });

        try {
            $operation();
        } finally {
            restore_error_handler();
        }

        $this->assertNotEmpty($errors, 'The expected user error was not triggered.');
        foreach ($errors as $error) {
            $this->assertSame(array($level, $message), $error);
        }
    }
}
