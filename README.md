# yabot-plugins

[![Build Status](https://travis-ci.org/nopolabs/yabot-plugins.svg?branch=master)](https://travis-ci.org/nopolabs/yabot-plugins)
[![Code Climate](https://codeclimate.com/github/nopolabs/yabot-plugins/badges/gpa.svg)](https://codeclimate.com/github/nopolabs/yabot-plugins)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nopolabs/yabot-plugins/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nopolabs/yabot-plugins/?branch=master)
[![License](https://poser.pugx.org/nopolabs/yabot-plugins/license)](https://packagist.org/packages/nopolabs/yabot-plugins)
[![Latest Stable Version](https://poser.pugx.org/nopolabs/yabot-plugins/v/stable)](https://packagist.org/packages/nopolabs/yabot-plugins)

This is a collection of plugins for [yabot](https://github.com/nopolabs/yabot)

## Getting Started

Initialize a yabot project using the yabot 
[quick start](https://github.com/nopolabs/yabot#quick-start)

Then add yabot-plugins:

    composer require nopolabs/yabot-plugins

See the
[plugins](https://github.com/nopolabs/yabot#plugins-)
discussion in [yabot](https://github.com/nopolabs/yabot)

After running the
[quick start](https://github.com/nopolabs/yabot#quick-start)
you should have a directory like this:

    .
    |-- composer.json
    |-- composer.lock
    |-- config/
    |   `-- plugins.yml
    |-- config.php
    |-- vendor/
    `-- yabot.php

## Adding plugins to plugins.yml

If you required nopolabs/yabot-plugins then you will be able to find example plugin configs in
vendor/nopolabs/yabot-plugins/config/plugins.yml

These configs can be by reference:

    # app/config/config.yml
    imports:
        - { resource: '../vendor/nopolabs/yabot-plugins/config/plugins.yml' }

But you may wish to include them more selectively.

See the Symfony [import](http://symfony.com/doc/current/service_container/import.html) docs
and remember:

    The resource location, for files, is either a relative path from the current file or an absolute path.
