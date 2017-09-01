# Laravel-Validate

<p align="center">
<a href="https://packagist.org/packages/tbence/validate"><img src="https://poser.pugx.org/tbence/validate/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/tbence/validate"><img src="https://poser.pugx.org/tbence/validate/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/tbence/validate"><img src="https://poser.pugx.org/tbence/validate/license.svg" alt="License"></a>
</p>

Adds an `AutoValidation` trait to your project.

If you use that trait on your models, it will automatically vaildate it by your DB scheme.
These validation rules can be overridden manually from the model.

## Installation

```bash
composer require tbence/validate
```

> If Laravel version < 5.5, you have to manually include this line in your config/app.php:
```php
TBence\Validate\Provider::class,
```

## Usage

###Using migration files
Add the trait and the interface to your model. (Product is just an example.)
```php
<?php

namespace App;

use TBence\Validate\AutoValidation;
use TBence\Validate\Validates;

class Product extends Model implements Validates
{
    use AutoValidation;
    
    //...
}
```

That's it. If you try to create or update a Product model with data that's not compatible with your database schema
the package will throw a `ValidationException` which is handled by laravel automatically.
So the system will not fail with `something went wrong` when you are missing a value for a not null column.
It will return with standard validation error messages instead.

###Defining special validation rules for the model
 * Add the trait and the interface to your model.
 * Create a `rules()` method.
 
```php
<?php

namespace App;

use TBence\Validate\AutoValidation;
use TBence\Validate\Validates;

class Product extends Model implements Validates
{
    use AutoValidation;
    
    public function rules() {
        return [
            'name'        => 'required|max:255',
            'description' => 'required',
        ];
    }
    
    //...
}
```

`AutoValidation` will use the array defined above to validate the model on insert or update. 

> For example: `The name field is required.`

## Warning
This package is still in early in development use it at your own risk!
