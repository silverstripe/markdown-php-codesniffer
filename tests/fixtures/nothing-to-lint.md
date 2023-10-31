# Nothing to see here

This file has no relevant code blocks.

    class NothingHere
    {
        function($bad){exit(1);}
    }

^ That's a code block with PHP - but it won't be linted.

And this one has PHP too, but no code hint so no linting

```
class NothingHere
{
    function($bad){exit(1);}
}
```

This one has javascript and IT won't be linted either.

```js
class NothingHere
{
    function($bad){exit(1);}
}
```
