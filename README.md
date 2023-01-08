# folder
Self-hosted photo journal platform

## Requirements
- PHP 8.1 (or newer) with these extensions:
  - PDO
  - intl
- Composer

## Installation
Unzip the release folder to your web root, then run:
```shell
composer install
composer build
```
The `build` command runs `composer dump-autoloader`.

Then go to `/folder` at your domain/IP, and login with the username/password `admin/admin`.