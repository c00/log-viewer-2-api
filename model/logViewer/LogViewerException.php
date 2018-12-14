<?php

namespace c00\logViewer;


use Throwable;

class LogViewerException extends \Exception {

	public function __construct( string $message = "", int $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}

	public static function new(string $message, $code = 0, $previous = null) {
		return new LogViewerException($message, $code, $previous);
	}
}