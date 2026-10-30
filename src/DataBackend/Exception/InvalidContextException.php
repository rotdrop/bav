<?php

namespace rotdrop\bav\DataBackend\Exception;

use rotdrop\bav\Exception\BAVException;
use rotdrop\bav\Validator\ContextValidation;

/**
 * Missing context during context validation
 *
 * @see ContextValidation
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 */
class InvalidContextException extends BAVException
{

}
