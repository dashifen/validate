<?php

namespace Dashifen\Validator\FieldAndValueValidator;

use Dashifen\Validator\AbstractValidator;
use Dashifen\Validator\ValidatorException;

abstract class AbstractFieldAndValueValidator extends AbstractValidator
{
    /**
     * isValid
     *
     * Passed the field and value through a validation method based on the
     * field name.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return bool
     * @throws ValidatorException
     */
    public function isValid (string $field, $value): bool {
        
        // this is a somewhat opinionated implementation.  it assumes that if we
        // can't validate our data, that we shouldn't report that it's invalid as
        // that's likely to cause an error when we can't be sure that it's
        // warranted.
        
        if ($this->canValidate($field)) {
            return $this->isArray($value)
                
                // we can't alter the method signature of isValidArray, but we
                // also need it to know the field that we're working with.  so,
                // we'll pass it as the first of the parameters for the method
                // below.
                
                ? $this->isValidArray($value, $this->getValidationMethod($field), $field)
                : $this->{$this->getValidationMethod($field)}($field, $value);
        }
        
        return true;
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
     * @throws ValidatorException
     */
    protected function isValidArray (array $values, string $function, ...$parameters): bool
    {
    
        // the field that we're validating (this is a FieldAndValueValidator
        // object after all) must be passed as the first parameter to this
        // method.  if we didn't receive parameters or if the first one isn't
        // a string, we throw an exception.
    
        if (sizeof($parameters) === 0) {
            throw new ValidatorException(
                'FieldAndValueValidator::isValidArray must be sent the field as its third argument',
                ValidatorException::UNABLE_TO_IDENTIFY_FIELD
            );
        }
    
        $field = array_shift($parameters);
    
        if (!is_string($field)) {
            throw new ValidatorException(
                'Invalid field: ' . $field,
                ValidatorException::UNABLE_TO_IDENTIFY_FIELD
            );
        }
    
        foreach ($values as $value) {
        
            // for each $value in the array, we see if it passes the
            // specified function.  because not all validators require
            // additional parameters, we test to see if we should pass
            // that information along.
        
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
}
