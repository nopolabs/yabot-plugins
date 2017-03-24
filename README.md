# yabot-plugins

This is a collection of plugins for [yabot](https://github.com/nopolabs/yabot)

## Getting started

    composer init \
        --stability dev \
        --repository '{"type":"vcs","url":"https://github.com/nopolabs/slack-client"}' \
        --repository '{"type":"vcs","url":"https://github.com/nopolabs/phpws.git"}'    
    composer require nopolabs/yabot.plugins
    
NOTE: Yabot is still under development and it depends on updates to coderstephen/slack-client
and devristo/phpws that are available in forks of those packages in the repositories above.

