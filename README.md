# Etten\Deployment

Simple PHP SSH/FTP deployment tool.

Inspired by [dg/ftp-deployment](https://github.com/dg/ftp-deployment).

**Do not deploy application manually!**

Deploy it automatically with this tool, run jobs before/after deploy. Upload only modified files.


## Other tool compatibility

This packages can easily replace [dg/ftp-deployment](https://github.com/dg/ftp-deployment).

If you have deployed applications with that tool, simply start using **etten/deployment**.

It can read dg/ftp-deployment deployed files database and simply continue only with modified files.

*This is tested with dg/ftp-deployment v2.5.*


## Continuous Delivery, Automatic Deployment

This tool allows you continuously and automatically deliver your application.

You can use i.e. [Shippable](https://shippable.com/) for your CI/CD.

I suggest you tu use [phpdocker/phpdocker](https://github.com/phpdocker/phpdocker) build image for this purposes.

After build is completed, simply run this deploy tool - it's a CLI PHP tool.


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
$ php www/index.php deployment -h
```

It's a Kdyby Console Application, so [read the Docs](https://github.com/Kdyby/Console/blob/master/docs/en/index.md) first.


### Another PHP application

See [demo Symfony Console CMD](demo/deploy.php) and [demo config file](demo/config.php).

For our demo deploy just run via CLI:

```bash
$ php demo\deploy.php -c config.php
```

It's a Symfony Console Application, so [read the Docs](http://symfony.com/doc/current/components/console/introduction.html) first.
