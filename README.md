# Lost in Translation

This package helps to find missing translation strings in your Laravel blade files.

## Installation

You can easily install this package using Composer, by running the following command:

```
composer require coding-socks/lost-in-translation
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

### Publish

Publish the configuration file only:

```
php artisan vendor:publish --tag=lost-in-translation-config
```

Publish the Command class only:

```
php artisan vendor:publish --tag=lost-in-translation-commands
```

## Defaults

The command considers `app.locale` as your applications default locale.

The command scans your `resources/views` and `app` directory.

The command detects the following in your blade and application files:

- `@lang('key')` Blade directives are compiled to `app('translator')->get('key')`
- `__('key')` any call to the `__` function
- `trans('key')` any call to the `trans` function
- `app('translator')->get('key')` any direct `get`method call on the `translator`
- `App::make('translator')->get('key')` any direct `get` method call on the `translator`
- `Lang::get('key')` any `get` static call on the `Lang` facade

## Implementation

This implementation reads all your blade files, compiles them to PHP ([illuminate/view]), parses them as PHP ([nikic/php-parser]), and then finds the relevant nodes in the AST.

This, in my opinion, is a cleaner and less error-prone than using regular expressions.

[illuminate/view]: https://github.com/illuminate/view
[nikic/php-parser]: https://github.com/nikic/PHP-Parser
