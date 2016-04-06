# Etten\Deployment

Simple PHP FTP deployment tool.

Inspired by [dg/ftp-deployment](https://github.com/dg/ftp-deployment).

**Do not deploy application manually!**

Deploy it automatically, with custom jobs. Upload only modified files.


## Usage

Just install via Composer to your project:

```bash
$ composer require etten/deployment
```

### [Nette](https://nette.org) application

Use it via DI Extension. See [demo](demo/config.neon).

It doesn't require comments. You can find directives in the config.

After install, run it via CLI:

```bash
$ php www/index.php deployment
```

It's a Kdyby Console Application, so [read the Docs](https://github.com/Kdyby/Console/blob/master/docs/en/index.md) first.


### Another PHP application

See [demo Symfony Console CMD](demo/deploy.php) and [demo config file](demo/config.php).

For our demo deploy just run via CLI:

```bash
$ php demo\deploy.php -c config.php
```

It's a Symfony Console Application, so [read the Docs](http://symfony.com/doc/current/components/console/introduction.html) first.
