# Pocket Connector
> Helpers for Pocket

## Installation
1. add to composer.json
```
    "repositories": [
        { "type": "vcs", "url": "git@github.com:skydiver/pocket-connector.git" }
    ],
```
2. `composer update skydiver/pocket-connector`
3. `php artisan vendor:publish --provider="Skydiver\PocketConnector\PocketConnectorServiceProvider"`
