<?php
namespace FcmLibrary\Exceptions;

use Exception;

class FcmLibraryException extends Exception{
    
    const TOKEN_EMPTY = 1;
    const PROJECT_NAME_EMPTY = 2;
    const DEVELOPER_KEY_EMPTY = 3;
    const ERROR_GENERATE_TOKEN = 4;
    
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
        
}
