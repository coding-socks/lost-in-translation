# Lost in Translation

This package helps to find missing translation strings in your Laravel blade files.

## Installation

You can easily install this package using Composer, by running the following command:

```
composer install coding-socks/lost-in-translation
```

## Usage

You can list all missing translation for a specific location using the command below.

```
php artisan lost-in-translation:find {locale}
```

Example:

```
php artisan lost-in-translation:find nl
```

By default, the command detects the following in your blade files:

- `@lang('key')` any call to the Blade directive
- `{{ __('key') }}` any call to the `__` function
- `{{ trans('key') }}` any call to the `trans` function
- `{{ app('translator')->get('key') }}` any direct get call to the `translator`
- `{{ App::make('translator')->get('key') }}` any direct get call to the `translator`
- `{{ Lang::get('key') }}` any get call to the `Lang` facade

## Implementation

This implementation reads all your blade files, compiles them to PHP ([illuminate/view]), parses them as PHP ([nikic/php-parser]), and then finds the relevant nodes in the AST.

This, in my opinion, is a cleaner and less error-prone than using regular expressions.

[illuminate/view]: https://github.com/illuminate/view
[nikic/php-parser]: https://github.com/nikic/PHP-Parser
