<?php
namespace xf\core;

/**
 * Encapsulates an exception that can be propagated to the client side.
 * Wraps a server-side exception.
 */
class XFException extends \Exception {
    private $clientErrorCode;
    private $clientErrorMessage;
    public function __construct($message, $code, \Exception $cause = null) {
        if (!isset($cause)) {
            $cause = new \Exception($message, $code);
        }
        parent::__construct($cause->getMessage(), $cause->getCode(), $cause);
        $this->clientErrorCode = $code;
        $this->clientErrorMessage = $message;
    }

    public function getClientErrorCode() {
        return $this->clientErrorCode;
    }

    public function getClientErrorMessage() {
        return $this->clientErrorMessage;
    }

    public static function throwPEARError($err) {
        throw new XFException($err->getMessage(), $err->getCode());
    }

    public static function throwValidationFailure($message = "Validation failed") {
        throw new XFException(
            $message, 
            DATAFACE_E_VALIDATION_CONSTRAINT_FAILED, 
            new \Exception($message, DATAFACE_E_VALIDATION_CONSTRAINT_FAILED)
        );

    }

    public static function throwPermissionDenied($message = "Permission denied") {
        throw new XFException(
            $message, 
            401, 
            new \Exception($message, 401)
        );
    }
    
    public static function throwBadRequest($message = "Bad Request") {
        throw new XFException(
            $message, 
            500, 
            new \Exception($message, 500)
        );
    }
}