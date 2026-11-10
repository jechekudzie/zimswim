<?php

namespace App\Services\Lenex;

use RuntimeException;

/**
 * Raised when an uploaded file cannot be read as Lenex. The message is shown
 * to whoever uploaded it, so it stays in plain language.
 */
class LenexException extends RuntimeException {}
