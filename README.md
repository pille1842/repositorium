# Repositorium &ndash; a powerful PHP wiki engine

Repositorium is a wiki engine written in PHP, based on
the [Slim Framework](http://www.slimframework.com/) and the [Twig template
engine]. It uses a Git repository as its storage backend by default: All
versions of your documents are kept and you can use other programs to interact
with your wiki.

## Features

* Markdown-formatted wiki pages with easy linking
* Support for other file types, e.g. source code with code highlighting, images
  and other binary files
* Simple export of raw document sources and file downloads
* Built-in presentations using [Remark](https://remarkjs.com/)
* Fully featured file storage with subdirectories, all based on Git

## Todo

This project is still in very early beta and should not be used in a production
environment. Better error handling and implementing some of the promised
features (like searching and uploading binary files) is on the TODO list.
If you wish to contribute, feel free to message me or send pull requests.

## Installation

1. Your server should point to the `public/` directory in the project sources.
   You can also test Repositorium by executing `php -S localhost:8000
   routing.php` from within the `public/` directory. This will start a
   development server on port 8000.
2. Initialize a Git repository in the `storage/` directory. Make sure that the
   user that's running the webserver has configured a Git user name and e-mail
   address or that these settings are made in the storage repository. Error
   handling is VERY basic at this point!
3. That's it, have fun exploring the project and making your very own wiki.

## License

This project is distributed under the MIT License. Have a look at LICENSE in the
project sources.
