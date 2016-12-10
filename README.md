# Conveyor

<p style="text-align: center">
📦 -> 🎰 🎰 🎰 🎰 🎰 🎰 -> ⭐️
</p>


Command-line tool to easily run tests agains multiple versions of [ProcessWire CMF](https://processwire.com).

Are you building a module, or a template and you need to make sure it works in all supported ProcessWire versions? Then `conveyor` is exactly what you need. Write the tests in any fashion you like (PHPUnit, CodeCeption, Kahlan, ...). Then tell `conveyor` which ProcessWire versions you are interested in and it will do the rest for you.

> Tested on **Mac OS X**

[![video](example/asciicast.gif)](https://asciinema.org/a/95368)

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Usage](#usage)
7. [Configuration](#configuration)
7. [Attribution](#attribution)

## Requirements

- PHP 5.6 or greater
- Composer (https://getcomposer.org)
- Git (https://git-scm.com)
- MySQL or MariaDB 5.0.15 or greater

### php.ini

`php.ini` used by `php` cli command must have enabled these extensions:

- curl
- gd2
- mbstring
- mysqli
- openssl
- pdo_mysql

## Installation

> Don't forget to setup all [requirements](#requirements) first.

Install globally:
```
composer global require lostkobrakai/conveyor
```

or install as a project dependency:
```
cd <your-project>
composer require --dev lostkobrakai/conveyor
```

## Usage

Go to your **project's root** directory.

[Create config](#configuration) file `conveyor.yml`,

then if you installed `conveyor` globally:
```
conveyor
```

or if you've installed `conveyor` as projects dependecy:
```
vendor/bin/conveyor
```

## Configuration

Copy example configuration [`conveyor.yml`](conveyor.yml) to your project's root directory and set options according to your needs.

If you like you can also use a [json config](conveyor.json).

## Attribution

Based on [uiii/pw-test](https://github.com/uiii/pw-test).