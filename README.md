# Markdown PHP Codesniffer

A wrapper around [`squizlabs/PHP_CodeSniffer`](https://github.com/squizlabs/PHP_CodeSniffer) which lets you lint PHP fenced code blocks in markdown files.

## Installation

Unlike `squizlabs/PHP_CodeSniffer`, this isn't intended to be installed globally - you should install it as a dev dependency of your project.

```bash
composer require --dev silverstripe/markdown-php-codesniffer
```

## Usage

To sniff markdown files, run `mdphpcs` from the vendor bin directory:

```bash
# sniff a directory
vendor/bin/mdphpcs /path/to/docs

# sniff a specific file
vendor/bin/mdphpcs /path/to/docs/file.md
```

Most of the options available with the `phpcs` and `phpcbf` commands from `squizlabs/PHP_CodeSniffer` are available with `mdphpcs` as well.
See [PHP_CodeSniffer usage](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Usage) for more details.

### Fixing violations automatically

Some violations can be fixed automatically, and PHP_CodeSniffer will include information about those in the CLI output. To fix them, simply pass the `--fix` option to `mdphpcs`:

```bash
vendor/bin/mdphpcs /path/to/docs --fix
```

This is the equivalent of using the `phpcbf` command on regular PHP files.

### Linting other languages

`squizlabs/PHP_CodeSniffer` supports linting some languages other than PHP. Theoretically that can be done with this tool as well. You'll need to pass the language (as it's written in the markdown language hint) in with the `--linting-language` option.

```bash
vendor/bin/mdphpcs /path/to/docs --linting-language=JS
```

### Linting rules

If you have a [default configuration file](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Advanced-Usage#using-a-default-configuration-file) or explicitly pass in a standard using the `--standard` option, those rules will be used for linting - but be aware that some rules won't be appropriate for linting code blocks.

For example, the `PSR12.Files.FileHeader.HeaderPosition` rule will always fail linting, because we need to include empty lines prior to the content of the code block in the content we pass to `squizlabs/PHP_CodeSniffer` so it can correctly report the line of each violation in the original markdown file.

If you don't specify a standard and have no default configuration file, the default configuration [included in this package](./phpcs.default.xml) will be used. This configuration is based on PSR12, with some exclusions that make it appropriate for use in linting code blocks.
