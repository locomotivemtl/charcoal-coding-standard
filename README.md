Charcoal Coding Standard
========================

[![License][badge-license]][charcoal-coding-standard]
[![Latest Stable Version][badge-version]][charcoal-coding-standard]
[![Build Status][badge-travis]][dev-travis]

The [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) ruleset to check that
repositories are following the standards used by the [Charcoal][charcoal-app] framework.

## Standards

The Charcoal Coding Standard extends and expands [PSR-1][psr-1] and [PSR-2][psr-2] ~~PSR-12~~.

For a full reference of enforcements, please consult [`Charcoal/ruleset.xml`](/Charcoal/ruleset.xml).



## Installation

### 1. Project Installation

You can install the Charcoal Coding Standard as a [Composer](https://getcomposer.org/) dependency of your project.

1.  Install the package via Composer:

    ```console
    $ composer require --dev locomotivemtl/charcoal-coding-standard
    ```

2.  Register the new standard with PHP_CodeSniffer:

    ```console
    $ ./vendor/bin/phpcs --config-set installed_paths ./vendor/locomotivemtl/charcoal-coding-standard
    ```

    The second command registers the standard with `phpcs`.
    Ensure existing values of the `installed_paths` option are not overwritten.

3.  Create a `phpcs.xml` file at the base of your repository with the following content:

    ```xml
    <?xml version="1.0"?>
    <ruleset name="Charcoal Package">
        <rule ref="Charcoal" />
    </ruleset>
    ```

    You can include or exclude sniffs from this file. For a reference of customizations, consult the [PHP_CodeSniffer annotated ruleset](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-ruleset.xml).

    > Note: If you did not register the Charcoal Coding Standard with PHP_CodeSniffer, you can reference the standard using its path instead of its name:
    > 
    > ```xml
    > <rule ref="./vendor/locomotivemtl/charcoal-coding-standard/Charcoal/ruleset.xml" />
    > ```
    >
    > Example: [`phpcs.xml.example`](/phpcs.xml.example).

4.  Add scripts to your `composer.json`:
    
    ```json
    "scripts": {
        "cs-check": "phpcs --colors -p ./src ./tests",
        "cs-fix": "phpcbf --colors ./src ./tests"
    }
    ```



### 2. Global Installation

You can also install the Charcoal Coding Standard system-wide:

1.  Install the package via Composer:

    ```console
    $ composer global require locomotivemtl/charcoal-coding-standard
    ```

2.  Register the new standard with PHP_CodeSniffer:

    ```console
    $ phpcs --config-set installed_paths ~/.composer/vendor/locomotivemtl/charcoal-coding-standard
    ```

    The second command registers the standard with `phpcs`.
    Ensure existing values of the `installed_paths` option are not overwritten.



## Usage

### Sniffing

Depending on how you installed the Charcoal Coding Standard, you can do the following:

Detect violations of this standard with one of:

```console
$ phpcs --standard=Charcoal /path/to/some/file/to/sniff.php
$ ./vendor/bin/phpcs --standard=Charcoal /path/to/some/file/to/sniff.php
$ composer cs-check
```

Automatically fix violations of this standard with:

```console
$ phpcbf --standard=Charcoal /path/to/some/file/to/sniff.php
$ ./vendor/bin/phpcbf --standard=Charcoal /path/to/some/file/to/sniff.php
$ composer cs-fix
```

For further usage options, see the [CLI documentation](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Usage).

> Note: If you did not register the Charcoal Coding Standard with PHP_CodeSniffer, you can reference the standard using its path instead of its name:
> 
> ```console
> $ ./vendor/bin/phpcs --standard=./vendor/locomotivemtl/charcoal-coding-standard /path/to/code
> ```



### Linting

The Charcoal Coding Standard provides a simple but convenient bash script wrapper around `php -l` to recursively perform syntax checks on PHP files with several processes at once.

```
Usage:
  phplint [options] [<path>]...

Arguments:
  path            One or more paths to files or directories to search for PHP files.
                  Defaults to ./src and ./tests.

Options:
  -h, --help      Display this help message and exit.
  -V, --version   Display version information and exit.
```

Example:

```console
$ phplint src/Http/Controllers/ src/Providers/RouteServiceProvider.php
```

> Note: The script is not part of the `composer.json`; it is not added to the given project when the package is installed. The script must be called from the vendor package or you must manually symlink it into your `bin-dir`.
> 
> 1.  You can run the script from your project:
> 
>     ```console
>     $ ./vendor/locomotivemtl/charcoal-coding-standard/bin/phplint ./src ./tests
>     ```
> 
> 2.  You can also symlink the script into your project's `bin-dir`:
> 
>     ```console
>     $ cd $(composer config bin-dir)
>     $ ln -s ../locomotivemtl/charcoal-coding-standard/bin/phplint ./phplint
>     ```
> 
> Check out [overtrue/phplint][overtrue/phplint], a more advanced and configurable tool for linting PHP files.



## Credits

-   Chauncey McAskill <chauncey@locomotive.ca>
-   Mathieu Ducharme <mat@locomotive.ca>



## License

Charcoal is licensed under the MIT license. See [LICENSE](/LICENSE) for details.



[charcoal-coding-standard]:  https://packagist.org/packages/locomotivemtl/charcoal-coding-standard
[charcoal-app]:              https://packagist.org/packages/locomotivemtl/charcoal-app

[overtrue/phplint]:          https://packagist.org/packages/overtrue/phplint

[dev-travis]:         https://travis-ci.org/locomotivemtl/charcoal-coding-standard

[badge-license]:      https://img.shields.io/packagist/l/locomotivemtl/charcoal-coding-standard.svg?style=flat-square
[badge-version]:      https://img.shields.io/packagist/v/locomotivemtl/charcoal-coding-standard.svg?style=flat-square
[badge-travis]:       https://img.shields.io/travis/locomotivemtl/charcoal-coding-standard.svg?style=flat-square

[psr-1]:  https://www.php-fig.org/psr/psr-1/
[psr-2]:  https://www.php-fig.org/psr/psr-2/
[psr-12]: https://www.php-fig.org/psr/psr-12/
