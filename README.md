# yabot-plugins

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

These configs can be
[imported](http://symfony.com/doc/current/service_container/import.html)
by reference:

    # app/config/config.yml
    imports:
        - { resource: '%kernel.root_dir%/vendor/nopolabs/yabot-plugins/config/plugins.yml' }

But you may wish to include them more selectively