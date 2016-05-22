<?php

class DeadDropException extends Exception {}

class HTTPException extends DeadDropException {
    const CODE = 500;
    const MESSAGE = 'Internal Server Error';

    public function __construct($message = '', $code = 0, $previous = null) {
        return parent::__construct($message ?: $this::MESSAGE, $code ?: $this::CODE, $previous);
    }
}

class BadRequestException extends HTTPException {
    const CODE = 400;
    const MESSAGE = 'Bad Request';
}

class StorageException extends DeadDropException {}
