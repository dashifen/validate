<?php

namespace Dashifen\Validator;

interface ValidatorInterface
{
  /**
   * setRequirements
   *
   * When a validator is used to determine the complete-ness of a data set,
   * the requirements for that state are described to it here.  The array
   * parameter should describe the names of fields that this object can
   * validate and must both encounter and find valid during the validation
   * process.
   *
   * @param array $requirements
   */
  public function setRequirements(array $requirements): void;
  
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
  
  /**
   * getValidationMessages
   *
   * As the validator does its work, it compiles a set of messages describing
   * the results of its efforts.  Typically, these can be used as error or
   * warning messages, but perhaps a success message can use our test results,
   * too.  This method returns the entire set of messages.
   *
   * @return array
   */
  public function getValidationMessages(): array;
  
  /**
   * getValidationMessage
   *
   * This method returns a specific validation message.
   *
   * @param string $field
   *
   * @return string
   */
  public function getValidationMessage(string $field): string;
  
  /**
   * isComplete
   *
   * A validator determines a data set to be complete if, after it validates
   * the data within the set, all required items in the set have been found and
   * are valid.  This method returns true when that is the case, but it returns
   * false otherwise, i.e. when either the validator did not encounter a
   * required item or at least one required item was invalid.
   *
   * @return bool
   */
  public function isComplete(): bool;
}
