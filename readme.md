<p align="center"><img src="https://laravel.com/assets/img/components/logo-passport.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/passport"><img src="https://travis-ci.org/laravel/passport.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/passport"><img src="https://poser.pugx.org/laravel/passport/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/passport"><img src="https://poser.pugx.org/laravel/passport/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/passport"><img src="https://poser.pugx.org/laravel/passport/license.svg" alt="License"></a>
</p>

## Introduction

Laravel Passport is an OAuth2 server and API authentication package that is simple and enjoyable to use.

## Official Documentation

Documentation for Passport can be found on the [Laravel website](http://laravel.com/docs/master/passport).

For Multi Guards usage
* Add guard name to your client on "guard_name" column.
* Using different guard you should send it on request as well as setting it up
 
    request()->route()->middleware('guardName');
 
## License

Laravel Passport is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
