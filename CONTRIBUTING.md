# Contributing to ACF SVG Icon Picker

We welcome contributions to the ACF SVG Icon Picker plugin. This document outlines the process for contributing to the plugin.

## Pull Requests

Pull requests are highly appreciated. To contribute to the plugin, you can fork the repository and create a new branch for your changes. When you’re done, you can create a pull request to merge your changes into the main branch.

1. **Solve a problem** – Features are great, but even better is cleaning-up and fixing issues in the code that you discover.
2. **Write tests** – This helps preserve functionality as the codebase grows and demonstrates how your change affects the code.
3. **Write documentation** – Timber is only useful if its features are documented. This covers inline documentation of the code as well as documenting functionality and use cases in the Guides section of the documentation.
4. **Small > big** – Better to have a few small pull requests that address specific parts of the code, than one big pull request that jumps all over.
5. **Comply with Coding Standards** – See next section.

## Preparations

After you’ve forked the ACF SVG Icon Picker repository, you should install all Composer dependencies.

```
composer install
```

## Coding Standards

We use [EasyCodingStandard](https://github.com/symplify/easy-coding-standard) for Timber’s code and code examples in the documentation, which follows the [PSR-12: Extended Coding Styles](https://www.php-fig.org/psr/psr-12/).

We use tools to automatically check and apply the coding standards to our codebase (including the documentation), reducing the manual work to a minimum.

### Check and apply coding standards

We use EasyCodingStandard to automatically check and apply the coding standards.

```bash
composer cs
```

And to automatically fix issues:
```bash
composer cs:fix
```

We also use PHPStan to check for errors in the codebase.

```bash
composer analyse
```

## Unit tests

### Install WordPress test suite

Run the following command to install the test suite on your local environment:

```bash
bash bin/install-wp-tests.sh {db_name} {db_user} {db_password} {db_host} {wp_version}
```

Replace variables with appropriate values. for example:

```bash
bash bin/install-wp-tests.sh acf_icon_test root root localhost 6.6
```

### Run unit tests

Run PHPUnit test suite with the default settings and ensure your code does not break existing features.

```bash
composer test
```

## Process

All PRs receive a review from at least one maintainer. We’ll do our best to do that review as soon as possible, but we’d rather go slow and get it right than merge in code with issues that just lead to trouble.