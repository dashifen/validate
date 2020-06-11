<?php

namespace Dashifen\Validator;

use finfo;
use Mimey\MimeTypes;

abstract class AbstractValidator implements ValidatorInterface
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
    public function canValidate(string $field): bool
    {
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
     * isValid
     *
     * Passed the value through a validator based on the field name.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return bool
     */
    public function isValid(string $field, $value): bool
    {
        
        // this is a somewhat opinionated implementation.  it assumes that if we
        // can't validate our data, that we shouldn't report that it's invalid as
        // that's likely to cause an error when we can't be sure that it's
        // warranted.
        
        if ($this->canValidate($field)) {
            return $this->isArray($value)
                ? $this->isValidArray($value, $this->getValidationMethod($field))
                : $this->{$this->getValidationMethod($field)}($value);
        }
        
        return true;
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
            
            // for each $value in the array, we see if it passes the
            // specified function.  because not all validators require
            // additional parameters, we test to see if we should pass
            // that information along.
            
            $passed = $this->isNotEmptyArray($parameters)
                ? $this->{$function}($value, ...$parameters)
                : $this->{$function}($value);
            
            // if something didn't pass the test, we return false immediately.
            // this should save us a few nanoseconds.
            
            if (!$passed) {
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
     * returns true based on such a relationship.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return bool
     */
    public function isValidPair(string $field, $value): bool
    {
        // like isValid above, this is opinionated in that the inability to
        // validate this pair shouldn't necessarily be seen as a marker of its
        // invalidity.  the chief difference between this method and isValid
        // above is that this one passes both the field and value parameters to
        // the validation method.
        
        if ($this->canValidate($field)) {
            return $this->isArray($value)
                ? $this->isValidArrayPair($field, $value, $this->getValidationMethod($field))
                : $this->{$this->getValidationMethod($field)}($field, $value);
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
            
            // for each $value in the array, we see if it passes the specified
            // function.  because not all validators require additional
            // parameters, we test to see if we should pass that information
            // along.  note that here is where we pass the field to our
            // validation method unlike isValidArray above.
            
            $passed = $this->isNotEmptyArray($parameters)
                ? $this->{$function}($field, $value, ...$parameters)
                : $this->{$function}($field, $value);
            
            // if something didn't pass the test, we return false immediately.
            // this should save us a few nanoseconds.
            
            if (!$passed) {
                return false;
            }
        }
        
        // if we made it to the end of the array and everything passed, then
        // we end up here.  that means the array is valid, so we return true.
        
        return true;
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
        
        // at first glance, we could use intval instead of floor.
        // then, we could also tighten up our comparison by using ===
        // instead of ==.  but, intval("4.0") === "4.0" would report
        // false.  by using floor(), instead, we get a true result in
        // such cases.
        
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
    protected function isDate($value, $format = "m/d/Y"): bool
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
            ? (bool)filter_var($value, FILTER_VALIDATE_EMAIL)
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
            ? (bool)filter_var($value, FILTER_VALIDATE_URL)
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
        $valid = false;
        
        if ($this->isUploadedFile($name)) {
            
            // the list of $types has MIME types against which we need to
            // test the uploaded file's type.  we'll have the Mimey object
            // to get its type since we can't always rely on the file info
            // extension being available.
            
            $valid = class_exists("finfo")
                ? $this->checkFileTypeWithFinfo($name, $types)
                : $this->checkFileTypeWithMimey($name, $types);
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
        // pass it the direct link to the file itself and it identifies the
        // type from there.  this should mean that even files from Macs,
        // i.e. without extensions, should be identifiable.
        
        $info = new finfo(FILEINFO_MIME_TYPE);
        $type = $info->file($_FILES[$name]["tmp_name"]);
        
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
     * If the fileinfo extension is not available, this uses the Mimey library
     * to give it a go.
     *
     * @param string $name
     * @param array  $types
     *
     * @return bool
     * @throws ValidatorException
     */
    private function checkFileTypeWithMimey(string $name, array $types): bool
    {
        
        // Mimey isn't as slick as finfo because it focuses on extensions.
        // since Macs don't use extensions, this isn't foolproof.  hence,
        // the need to test and, maybe, throw an Exception.
        
        $mimey = new MimeTypes();
        $extension = pathinfo($_FILES[$name]["name"], PATHINFO_EXTENSION);
        
        if (empty($extension)) {
            throw new ValidatorException(
                "Cannot identify file extension.",
                ValidatorException::NO_EXTENSION
            );
        }
        
        $type = $mimey->getMimeType($extension);
        
        if (empty($type)) {
            throw new ValidatorException(
                "Cannot identify file type.",
                ValidatorException::MIME_TYPE_NOT_FOUND
            );
        }
        
        return in_array($type, $types);
    }
}
