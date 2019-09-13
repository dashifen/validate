<?php

namespace Dashifen\Validator;

abstract class AbstractValidator implements ValidatorInterface {
  /**
   * canTransform
   *
   * Returns true if this object can transform data identified by the field
   * label.
   *
   * @param string $field
   *
   * @return bool
   */
  public function canValidate (string $field): bool {
    return method_exists($this, $this->getValidationMethod($field));
  }

  /**
   * getValidationMethod
   *
   * Returns the name of a method assumed to be defined within the concrete
   * extension of this abstract class that will validate data labeled by our
   * field parameter.
   *
   * @param string $field
   *
   * @return string
   */
  abstract protected function getValidationMethod(string $field): string;

  /**
   * transform
   *
   * Passed the value through a transformation based on the field name.
   *
   * @param string $field
   * @param mixed  $value
   *
   * @return bool
   */
  public function isValid (string $field, $value): bool {

    // this is a somewhat opinionated implementation.  it assumes that if we
    // can't validate our data, that we shouldn't report that it's invalid as
    // that's likely to cause an error when we can't be sure that it's
    // warranted.

    return $this->canValidate($field)
      ? $this->{$this->getValidationMethod($field)}($value)
      : true;
  }
}