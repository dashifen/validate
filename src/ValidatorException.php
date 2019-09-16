<?php

namespace Dashifen\Validator;

use Dashifen\Exception\Exception;

class ValidatorException extends Exception {
  public const NO_EXTENSION        = 1;
  public const MIME_TYPE_NOT_FOUND = 2;
}