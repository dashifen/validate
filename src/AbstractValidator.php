<?php

namespace Dashifen\Validator;

use FileEye\MimeMap\Extension;
use FileEye\MimeMap\MappingException;

abstract class AbstractValidator implements ValidatorInterface
{
  /**
   * @var bool[]
   */
  protected $requirements = [];
  
  /**
   * @var string[]
   */
  protected $messages = [];
  
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
   *
   * @return void
   * @throws ValidatorException
   */
  public function setRequirements(array $requirements): void
  {
    // each member of our requirements array should be a string.  we assume
    // those strings describe the names of fields that we're about to try and
    // validate.  we'll check to be sure that each of the requirements we
    // received are not empty, they are strings, and those strings are not
    // numbers.
    
    foreach ($requirements as $requirement) {
      if (
        $this->isEmpty($requirements)
        || !$this->isString($requirements)
        || $this->isNumber($requirements)
      ) {
        throw new ValidatorException(
          'Invalid requirement: ' . $requirement,
          ValidatorException::INVALID_REQUIREMENT
        );
      }
    }
    
    // if we're here, then all of our requirements were valid.  therefore, we
    // we'll prepare a map that links requirement names to a Boolean false
    // value.  then, as we validate fields, we'll set these values to true.
    
    $this->requirements = array_fill_keys($requirements, false);
  }
  
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
  public function canValidate(string $field): bool
  {
    $canValidate = method_exists($this, $this->getValidationMethod($field));
    
    if ($canValidate) {
      
      // if we can validate this field, then we may want to store the results
      // of our validity testing.  we'll assume that all values are valid for
      // the moment.  in the extension of this object, if it wants to use this
      // feature, it can replace these messages with other data that's more
      // specific to its needs.
      
      $this->messages[$field] = 'This field has a valid value.';
    }
    
    return $canValidate;
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
   * isValid
   *
   * Passes the value through a validator based on the field name.  Additional
   * information to assist with the validation of the value can follow the
   * $value parameter and will be accumulated into the $parameters array.
   *
   * @param string $field
   * @param mixed  $value
   * @param array  $parameters
   *
   * @return bool
   */
  public function isValid(string $field, $value, ...$parameters): bool
  {
    // this is a somewhat opinionated implementation.  it assumes that if we
    // can't validate our data, that we shouldn't report that it's invalid as
    // that's likely to cause an error when we can't be sure that it's
    // warranted.  if a concrete extension of this object wants to reverse
    // this opinion, it's free to do so :)
    
    $isValid = true;
    if ($this->canValidate($field)) {
      $isValid = $this->isArray($value)
        
        // if we receive $parameters from the calling scope, then we want to
        // pass them on to the validation method here.  we'll unpack the array
        // to pass its values as separate parameters.  if the array is empty,
        // that means no additional parameters are passed, but if there are
        // any data within $parameters it allows the validation methods that
        // concrete versions of this object will contain to have signatures
        // which are similar to the way we call this method.
        
        ? $this->isValidArray($value, $this->getValidationMethod($field), ...$parameters)
        : $this->{$this->getValidationMethod($field)}($value, ...$parameters);
    }
    
    if ($isValid && array_key_exists($field, $this->requirements)) {
      
      // if we have validated this field and if this field is required, then we
      // want to set the flag that indicates this requirement has been met.
      
      $this->requirements[$field] = true;
    }
    
    return $isValid;
  }
  
  /**
   * isArray
   *
   * Returns true if $value passes through is_array().
   *
   * @param $value
   *
   * @return bool
   */
  protected function isArray($value): bool
  {
    return is_array($value);
  }
  
  /**
   * isValidArray
   *
   * When the data that we're validating is contained within an array, this
   * method gets called to iterate over that array and apply the specified
   * function to each value within it.  We return true if every value passes
   * validation; false otherwise.
   *
   * @param array  $values
   * @param string $function
   * @param array  $parameters
   *
   * @return bool
   */
  protected function isValidArray(array $values, string $function, ...$parameters): bool
  {
    foreach ($values as $value) {
      if (!$this->{$function}($value, ...$parameters)) {
        
        // if something doesn't pass our test, then we return false
        // immediately.  if the array is large enough, this could save us a bit
        // of time.
        
        return false;
      }
    }
    
    // if we made it to the end of the array and everything passed, then
    // we end up here.  that means the array is valid, so we return true.
    
    return true;
  }
  
  /**
   * isValidPair
   *
   * Sometimes, a value's validity is determined by its field.  This method
   * returns true based on such a relationship using the $pair parameter to
   * identify the validation method to be used.  Additional information to
   * assist with the validation of the value can follow the $value parameter
   * and will be accumulated into the $parameters array.
   *
   * @param string $pair
   * @param string $field
   * @param mixed  $value
   * @param array  $parameters
   *
   * @return bool
   */
  public function isValidPair(string $pair, string $field, $value, ...$parameters): bool
  {
    // like isValid above, this is opinionated in that the inability to
    // validate this pair shouldn't necessarily be seen as a marker of its
    // invalidity.  note that we use $pair to identify if and how to check
    // these data so that we can pass both $field and $value to the
    // identified method.
    
    if ($this->canValidate($pair)) {
      return $this->isArray($value)
        
        // if we receive $parameters from the calling scope, then we want to
        // pass them on to the validation method here.  we'll unpack the array
        // to pass its values as separate parameters.  if the array is empty,
        // that means no additional parameters are passed, but if there are
        // any data within $parameters it allows the validation methods that
        // concrete versions of this object will contain to have signatures
        // which are similar to the way we call this method.
        
        ? $this->isValidArrayPair($field, $value, $this->getValidationMethod($pair), ...$parameters)
        : $this->{$this->getValidationMethod($pair)}($field, $value, ...$parameters);
    }
    
    return true;
  }
  
  /**
   * isValidArrayPair
   *
   * When the data that we're validating is contained within an array, this
   * method gets called to iterate over that array and apply the specified
   * function to each value within it.  It differs from the above method
   * named isValidArray because it passes the field to the function and not
   * just the value.
   *
   * @param string $field
   * @param array  $values
   * @param string $function
   * @param array  $parameters
   *
   * @return bool
   */
  protected function isValidArrayPair(string $field, array $values, string $function, ...$parameters): bool
  {
    foreach ($values as $value) {
      if (!$this->{$function}($field, $value, ...$parameters)) {
        
        // if something fails our test, then we return false immediately.  for
        // large arrays, this could save us a few nanoseconds that would be
        // lost if we had to process the rest of the array when we've already
        // proven it to be invalid.
        
        return false;
      }
    }
    
    // if we made it to the end of the array and everything passed, then
    // we end up here.  that means the array is valid, so we return true.
    
    return true;
  }
  
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
  public function getValidationMessages(): array
  {
    return $this->messages;
  }
  
  /**
   * getValidationMessage
   *
   * This method returns a specific validation message.
   *
   * @param string $field
   *
   * @return string
   * @throws ValidatorException
   */
  public function getValidationMessage(string $field): string
  {
    if (!array_key_exists($field, $this->messages)) {
      throw new ValidatorException(
        'Unknown field: ' . $field,
        ValidatorException::UNKNOWN_FIELD
      );
    }
    
    return $this->messages[$field];
  }
  
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
  public function isComplete(): bool
  {
    // as we validated fields above, when we encountered a required one, we
    // switched the flags within the requirements property from false to true.
    // because there's only two Boolean values, when we pass that property
    // through the array_unique function, the results will have either one or
    // two values.  if it has only one value, then that value must be either
    // true or false.  so, if we have one value and that value is true, then
    // our data set is complete.  otherwise, there must be at least one false
    // in the set somewhere and it's incomplete.  we use the reset function
    // because it rewinds the internal array pointer and returns the first
    // value in it and because it's much faster than array_shift.
    
    $completeness = array_unique($this->requirements);
    return sizeof($completeness) === 1 && reset($completeness);
  }
  
  /**
   * isFloat
   *
   * Returns true if $value is a number and not an integer; false otherwise.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isFloat($value): bool
  {
    return !$this->isArray($value)
      ? $this->isNumber($value) && !$this->isInteger($value)
      : $this->isValidArray($value, "isFloat");
  }
  
  /**
   * isNumber
   *
   * Returns true if $value is numeric.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isNumber($value): bool
  {
    return !$this->isArray($value)
      ? is_numeric($value)
      : $this->isValidArray($value, "isNumber");
  }
  
  /**
   * isInteger
   *
   * Returns true if $value is a number and if the floor() of that number
   * matches itself.  E.g., floor(3) == 3 but floor(3.14159) != 3.14159.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isInteger($value): bool
  {
    // at first glance, we could use intval instead of floor.  then, we could
    // also tighten up our comparison by using === instead of ==.  but,
    // intval("4.0") === "4.0" would report false.  by using floor(), instead,
    // we get a true result in such cases.
    
    return !$this->isArray($value)
      ? $this->isNumber($value) && floor($value) == $value
      : $this->isValidArray($value, "isInteger");
  }
  
  /**
   * isPositive
   *
   * Returns true if $value is a number and if it's greater than zero.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isPositive($value): bool
  {
    return !$this->isArray($value)
      ? $this->isNumber($value) && $value > 0
      : $this->isValidArray($value, "isPositive");
  }
  
  /**
   * isNegative
   *
   * Returns true if $value is a number and if it's less than zero.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isNegative($value): bool
  {
    return !$this->isArray($value)
      ? $this->isNumber($value) && $value < 0
      : $this->isValidArray($value, "isNegative");
  }
  
  /**
   * isZero
   *
   * Returns true if $value is a number and if it's zero.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isZero($value): bool
  {
    // like when we tested our integer, we won't use === here
    // because 0.0 === 0 is actually false.  but, 0.0 == 0 is
    // true, so that's our comparison.
    
    return !$this->isArray($value)
      ? $this->isNumber($value) && $value == 0
      : $this->isValidArray($value, "isZero");
  }
  
  /**
   * isNonZero
   *
   * Returns true if $value is a number and if it's not zero.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isNonZero($value): bool
  {
    // sometimes it's handy to test that something is not zero, just
    // like we want to test above that it is.
    
    return !$this->isArray($value)
      ? $this->isNumber($value) && !$this->isZero($value)
      : $this->isValidArray($value, "isNonZero");
  }
  
  /**
   * isNotTooLong
   *
   * Returns true if the length of $value is less than or equal to the
   * specified maximum length.
   *
   * @param     $value
   * @param int $maxLength
   *
   * @return bool
   */
  protected function isNotTooLong($value, int $maxLength): bool
  {
    return !$this->isArray($value)
      ? $this->isString($value) && strlen($value) <= $maxLength
      : $this->isValidArray($value, "isNotTooLong", $maxLength);
  }
  
  /**
   * isString
   *
   * Returns true if $value passes the is_string() PHP function.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isString($value): bool
  {
    return !$this->isArray($value)
      ? is_string($value)
      : $this->isValidArray($value, "isString");
  }
  
  /**
   * isNotEmpty
   *
   * Returns true if $value isn't empty.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isNotEmpty($value): bool
  {
    return $this->isArray($value)
      ? $this->isNotEmptyArray($value)
      : $this->isNotEmptyString($value);
  }
  
  /**
   * isNotEmptyArray
   *
   * Returns true if $value is an array and if it's not empty.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isNotEmptyArray($value): bool
  {
    return $this->isArray($value) && !$this->isEmptyArray($value);
  }
  
  /**
   * isNotEmptyString
   *
   * Returns true if $value is a string and if it's not empty.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isNotEmptyString($value): bool
  {
    return $this->isString($value) && !$this->isEmpty($value);
  }
  
  /**
   * isEmpty
   *
   * Returns true if $value is empty.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isEmpty($value): bool
  {
    return $this->isArray($value)
      ? $this->isEmptyArray($value)
      : $this->isEmptyString($value);
  }
  
  /**
   * isEmptyArray
   *
   * Returns true if $value is an array and if it's empty.  An empty array
   * is defined (here) as an array either with zero indices or with values
   * each of zero length.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isEmptyArray($value): bool
  {
    if ($this->isArray($value)) {
      
      // if there's nothing in the array, then we can feel
      // confident that it's empty.  we'll return true here
      // to avoid doing the work below.
      
      if (sizeof($value) === 0) {
        return true;
      }
      
      // just because an array has indices, doesn't mean they
      // contain values.  if we're here, we're going to flatten
      // the array and then join it into a string.  if that
      // string is empty, then the values in the array were
      // empty, too.
      
      $flattenedArray = [];
      array_walk_recursive(
        $value, function ($x) use (&$flattenedArray) {
        $flattenedArray[] = $x;
      }
      );
      
      // now, if we join $flattenedArray using the empty string as
      // our separator, we can test if the resulting string is empty.
      // if that's true, then the array that contained those empty
      // values making up this string was also empty.
      
      return $this->isEmptyString(join("", $flattenedArray));
    }
    
    // if we're here, then $value wasn't even an array.  we'll just
    // return false because if it's not an array, it can't be an empty
    // one.
    
    return false;
  }
  
  /**
   * isEmptyString
   *
   * Returns true if $value is a string and if it's empty.  Note:  a string
   * that is nothing but whitespace is considered empty to help avoid people
   * getting around required fields by entering a bunch of spaces.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isEmptyString($value): bool
  {
    // for our purposes, being comprised entirely of whitespace is
    // just as good as being empty.  so, we replace \s characters
    // with nothing and see if the length of that string is zero.
    
    return $this->isString($value) && strlen(preg_replace("/\s+/", "", $value)) === 0;
  }
  
  /**
   * isTime
   *
   * Returns true if $value appears to be a time in the specified format.
   *
   * @param        $value
   * @param string $format
   *
   * @return bool
   */
  protected function isTime($value, string $format = "g:i A"): bool
  {
    // times can be validated just like dates; we just specify our
    // format when we call the other function.
    
    return $this->isDate($value, $format);
  }
  
  /**
   * isDate
   *
   * Returns true if $value appears to be a date matching the specified format.
   *
   * @param        $value
   * @param string $format
   *
   * @return bool
   */
  protected function isDate($value, string $format = "m/d/Y"): bool
  {
    return !$this->isArray($value)
      
      // strtotime should give us a timestamp or false.  if it's
      // false, then the date becomes 12/31/1969 00:00:00.  since they
      // probably didn't enter that date, it wont' match and the
      // validation fails.
      
      ? date($format, strtotime($value)) === $value
      : $this->isValidArray($value, "date", $format);
  }
  
  /**
   * isEmail
   *
   * Returns true if $value passes through filter_var's FILTER_VALIDATE_EMAIL
   * validation.
   *
   * @param $value
   *
   * @return bool
   */
  protected function isEmail($value): bool
  {
    return !$this->isArray($value)
      ? (bool) filter_var($value, FILTER_VALIDATE_EMAIL)
      : $this->isValidArray($value, "email");
  }
  
  /**
   * isUrl
   *
   * Returns true if $value passes filter_var's FILTER_VALIDATE_URL validation.
   *
   * @param          $value
   *
   * @return bool
   */
  protected function isUrl($value): bool
  {
    return !$this->isArray($value)
      ? (bool) filter_var($value, FILTER_VALIDATE_URL)
      : $this->isValidArray($value, "url");
  }
  
  /**
   * isFileNotTooLarge
   *
   * Returns true if $name is a file and if it's file size is less than $size.
   *
   * @param string $name
   * @param int    $size
   *
   * @return bool
   */
  protected function isFileNotTooLarge(string $name, int $size): bool
  {
    $valid = false;
    if ($this->isUploadedFile($name)) {
      
      // now that we know this file exists, we'll see if it's size is
      // less than the $size we were sent here.  since we can't always
      // trust that the posted information hasn't been messed with,
      // we'll get the size right from the disk.
      
      $valid = filesize($_FILES[$name]["tmp_name"]) <= $size;
    }
    
    return $valid;
  }
  
  /**
   * isUploadedFile
   *
   * Returns true if $name is an uploaded file (i.e. if it's in $_FILES).
   *
   * @param string $name
   *
   * @return bool
   */
  protected function isUploadedFile(string $name): bool
  {
    // the existence of an uploaded file is determined by the existence
    // of the $name index within $_FILES.  so, this is a problem for
    // isset().
    
    return isset($_FILES[$name]);
  }
  
  /**
   * isUploadedFileTypeValid
   *
   * Returns true if $name is filename and it's type is in the $types array.
   *
   * @param string $name
   * @param array  $types
   *
   * @return bool
   * @throws ValidatorException
   */
  protected function isUploadedFileTypeValid(string $name, ...$types): bool
  {
    // first, PHP will make sure that $name identifies an uploaded file.  if
    // it doesn't, the && operation is short circuited and we return false.
    // if it is, then we call the method below passing it the path to that
    // uploaded file as well as our list of types and it'll take over from
    // there.
    
    return $this->isUploadedFile($name)
      && $this->isFileTypeValid($_FILES[$name]["tmp_name"], $types);
  }
  
  /**
   * isFileTypeValid
   *
   * Given the name of a file, checks to see if its type is in the list of
   * types.  Unlike the above method, this one is not required to be a recently
   * uploaded file.
   *
   * @param string $name
   * @param array  $types
   *
   * @return bool
   * @throws ValidatorException
   */
  protected function isFileTypeValid(string $name, ...$types): bool
  {
    $valid = false;
    if (is_file($name)) {
      
      // the list of $types has MIME types against which we need to test the
      // uploaded file's type.  if the PHP file info extension is available to
      // us, we'll prefer to use that.  but, since not all servers will have it
      // enabled, we'll fallback on the MimeMap dependency.
      
      $valid = class_exists("finfo")
        ? $this->checkFileTypeWithFinfo($name, $types)
        : $this->checkFileTypeWithMimeMap($name, $types);
    }
    
    return $valid;
  }
  
  /**
   * checkFileTypeWithFinfo
   *
   * If the fileinfo extension is available, we use it here to check our
   * file type.  This is preferred if only because it isn't based on a file's
   * extension.
   *
   * @param string $name
   * @param array  $types
   *
   * @return bool
   * @throws ValidatorException
   */
  private function checkFileTypeWithFinfo(string $name, array $types): bool
  {
    // this is the preferred method to check file types because we can
    // pass it the direct path to the file itself and it identifies the
    // type from there.  this should mean that even files from Macs,
    // i.e. without extensions, should be identifiable.
    
    $info = finfo_open(FILEINFO_MIME_TYPE);
    $type = finfo_file($info, $name);
    
    if ($type === false) {
      throw new ValidatorException(
        "Cannot identify file type.",
        ValidatorException::MIME_TYPE_NOT_FOUND
      );
    }
    
    return in_array($type, $types);
  }
  
  /**
   * checkFileTypeWithMimey
   *
   * If the fileinfo extension is not available, this uses the fileeye/mimemap
   * package to make a best guess at the type of our file here.
   *
   * @param string $name
   * @param array  $types
   *
   * @return bool
   * @throws ValidatorException
   */
  private function checkFileTypeWithMimeMap(string $name, array $types): bool
  {
    // the MimeMap package is not as robust as the file info PHP extension
    // because it needs to know a file's extension (e.g. docx or jpg) in order
    // to identify the type.  this means that files from Macs, which typically
    // lack an extension, will probably confuse it.  but, if we don't have the
    // PHP extension on this server, we'll at least try this.
    
    $extension = pathinfo($name, PATHINFO_EXTENSION);
    
    if (empty($extension)) {
      throw new ValidatorException(
        "Cannot identify file extension.",
        ValidatorException::NO_EXTENSION
      );
    }
    
    try {
      
      // if we have an extension, we'll try to use the getTypes method of our
      // MimeMap package to get a list of possible types for it.  then, if
      // there is any intersection between the possible types and the valid
      // types that we received as our second parameter, this file's type is
      // valid.
      
      $possibleTypes = (new Extension($extension))->getTypes(true);
      return sizeof(array_intersect($types, $possibleTypes)) !== 0;
    } catch (MappingException $mappingException) {
      
      // if no possible type could be found by the MimeMap package, it throws
      // this exception.  to limit the types of exceptions that the scope using
      // this validator needs to know about, we'll "convert" it to one of ours
      // but include the MappingException as the previously thrown object
      // within it.
      
      throw new ValidatorException(
        "Cannot identify file type.",
        ValidatorException::MIME_TYPE_NOT_FOUND,
        $mappingException
      );
    }
  }
}
