# This file has something to lint

```php
class MyClass {
    private string $myProperty='this is the value';
}
```

There's plenty of problems with that one.

```php
namespace App;

class MyClass
{
    private string $myProperty = 'this is the value';
}
```

There should be no linting issues with that block.

```js
// This one's just here so that we can check the num counter works correctly.
// This block should be skipped entirely.
```

```PHP
class AnotherClass {
    private string $anotherProperty='this is the value';
}
```

And then that one should have linting failures again - note the capital PHP, which should still be picked up.

For some reason this one doesn't get auto-fixed.

- this one will be indented
  ```php
  class FinalClass {
      private string $lastProperty='this is the value';
  }
  ```
- And it'll also have linting failures
