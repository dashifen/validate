# Validators

Here we define an interface for Validators as well as an Abstract class from which concrete objects that validate data based on field names can be built.  The goal:  to standardize the way that Dash creates validators in their work.

## Installation

`composer require dashifen/validator`

## Usage

You can either extend the `AbstractValidator` object or simply implement the `AbstractInterface` on your own.  The interface defines two methods:

1. `canValidate` - returns a Boolean value to tell the calling scope if data can be validated based on a `$field` parameter.
2. `isValid` - returns a Boolean regarding the validity of a `$value` based on its `$field` label. 

The `AbstractValidator` implements both of these for you while requiring that you define a third method: a protected `getValidationMethod` method.  It returns the name of another method that is assumed to be of the same object that can validate data labeled by `$field`.

## Example

In this example, we're assuming that the naming convention for the application's fields is to use kebab-case.     

```php
class Validator extends AbstractValidator {
    protected function getValidationMethod(string $field): string {
      
        // to convert a kebab-case $field to a function name, we want to 
        // convert it to StudlyCaps.  so, first, we convert from kebab-case to 
        // camelCase and then we ucfirst() the camelCase string to make it 
        // studly.  finally, we add the word "validate."  Thus, a start-date
        // field becomes startDate, then StartDate, and finally we return 
        // transformStartDate.
  
        $camelCase = preg_replace_callback("/-([a-z])/", function (array $matches): string {
            return strtoupper($matches[1]);
        }, $field);
      
        return "validate" . ucfirst($camelCase);
    }

    private function validateStartDate(string $date): bool {
      
        // what we want to do is make sure that our date appears valid.
        // we're less concerned with format (a transform can standardize 
        // that).  here, we just want to know that what we have might be
        // a date which is a good job for strtotime()!
  
        return strtotime($date) !== false;
    }
}
```

The above little class represents a simple, concrete object based on the functionality of the `AbstractValidator` found within this repo.  The abstract object's implementation of the `canValidate` and `isValid` methods of our interface make sure that we use the `getValidationMethod` to identify the name of a method that can transform data labeled by `$field` and then will call that method when we need it returning its result.