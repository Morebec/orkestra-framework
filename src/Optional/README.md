# Optional
The optional type was designed in order to simplify some constructs where we juggle between null values
and non-null values.

## Value Object based on nullable values.
When working with value objects based on strings, it is very common to need to construct them conditionally.
Let's take the example of a description field that is modeled as a value object `Description`.
The value we will need to apply comes from an HTTP request as a String or null value.
We want to have `null` if the description string we received is falsy, otherwise we want to have
a `Description` instance.
Note that in PHP "" is falsy, but " " is not. In both cases however we'd want them to be mapped to null,
meaning that we will need to perform a trim on the value:

```
"" -> null
" " -> null
" hello world" -> new Description("hello world");
"hello world" -> new Description("hello world");
```

One way to write this would be the following:

```php
$desc = $data->description ? trim($data->description) : $data->description;
return $desc ? new Description($desc) : null;
```

This does, the job but is not very legible.
Instead, we could do the following:

```php
Optional::of((string) $data->description)
    ->ifPresentCall(fn($v) => trim($v))
    ->ifPresentCall(fn($v) => new Description($v))
    ->getOrElse(null)
```