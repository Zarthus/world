# Novus

[world.liefland.net](https://world.liefland.net) is a php-powered development environment
combined with a static-page-compiled output resource, allowing you to host your own static
with minimal effort, while supporting live-compilation while developing. Since it's a static
site, it can run on github-pages, and we do. Check it out :-) https://world.liefland.net

In my particular case, I use it for world building.

#### How do I use it for myself?

Provided you're comfortable with a stack of PHP (twig), sass, javascript, and markdown - 
fork this repository, remove the contents in `www/`, and you're pretty much good-to-go.

See the installation instructions for how to install & run this software.

## Installation

### Prerequisites

- You should have `sass` as a binary in your `$PATH` environment.
- PHP 8.1 or higher is required.

### Installing..

```bash
git clone git@github.com:zarthus/world
cd world/
composer install

# optional
bin/gen-ca # generates the TLS certificates for serving via HTTPS
```

## Running

```bash
LIEFLAND_ENVIRONMENT=Development composer serve
```

### Compiling

```bash
composer build
```

### Development

For developers, I recommend running the webserver so that you're always served the latest versions of a file.

```bash
LIEFLAND_ENVIRONMENT=Development composer serve
```

## License

Powered by the [MIT License](LICENSE).
