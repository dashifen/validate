<?php

namespace Dashifen\Validator;

interface ValidatorInterface
{
  /**
   * canValidate
   *
   * Returns true if this object can validate data identified by the field
   * label.
   *
   * @param string $field
   *
   * @return bool
   */
  public function canValidate(string $field): bool;
  
  /**
   * isValid
   *
   * Returns true if our value is valid based on the a test dependent on the
   * value's field label.
   *
   * @param string $field
   * @param mixed  $value
   *
   * @return bool
   */
  public function isValid(string $field, $value): bool;
  
  /**
   * isValidPair
   *
   * Sometimes, a value's validity is determined by its field.  This method
   * returns true based on such a relationship using the $pair parameter to
   * identify the validation method to be used.
   *
   * @param string $pair
   * @param string $field
   * @param mixed  $value
   *
   * @return bool
   */
  public function isValidPair(string $pair, string $field, $value): bool;
}
