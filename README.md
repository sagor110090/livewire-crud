# Livewire Crud Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sagor110090/livewire-crud.svg?style=flat-square)](https://packagist.org/packages/sagor110090/livewire-crud)

A livewire CRUD Generation package to help scaffold basic site files. Package is autoloaded as per PSR-4 autoloading in any laravel version `^5.6` so no extra config required. However is has been tested on version `^7 & ^8`. It uses ***auth*** middleware thus installs `laravel/ui` just incase you don't have any other auth mechanism, this does not mean you have to use `laravel/ui`.

## Documentation

More detailed documentation can ne found at [livewire-crud](https://sagor110090.github.io/#/)

## Installation

You can install the package via [Composer](https://getcomposer.org/):

```bash
composer require sagor110090/livewire-crud
```

## Usage

After running `composer require sagor110090/livewire-crud` command just run:

```bash
php artisan crud:install
```
**This command will perfom below actions:

    * Compile css/js based on `bootstrap and fontawesome/free`.
    * Run `npm install && run dev`
    * Flush *node_modules* files from you folder.

If you choose to scaffold authentication this command will run `php artisan ui:auth`
to generate Auth scaffolds using `laravel/ui` package. You can skip this step if your app has authentication already.

Then generate Crud by:

```bash
php artisan crud:generate {table-name}
```
**This command will generate:

    * Livewire Component.
    * Model.
    * Views.    
    * Factory.
    
**Remember to customise your genertaed factories and migrations if you need to use them later
 
## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
