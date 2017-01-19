[![Build Status](https://travis-ci.org/adeira/superproject.svg?branch=master)](https://travis-ci.org/adeira/superproject)

This is superproject for Adeira subpackages. You can read about superprojects and submodules in Git here:
- https://git-scm.com/docs/git-submodule
- https://git-scm.com/docs/git-push#git-push---recurse-submodulescheckon-demandno

---

**Warning!**

_Do you want to contribute into Adeira namespace?_

_In this case please contribute directly into `adeira/<package-name>`. This superproject is intended only for development across all Adeira packages therefore installing and configuring this project is unnecessarily complicated just because of one subproject._

_Just in case you really want to install this beast you can continue. Just make sure that you really know what you are doing. It usually means that you have to know how Git works... :)_

---

Initializing this repository (you have to do this if you want to clone this repository):

    git clone git@github.com:adeira/superproject.git
    cd superproject
    composer install
    git submodule update --init [--jobs=8]

Commits should be performed in submodule (PhpStorm can handle it), but push should be executed from root superproject with flag `--recurse-submodules=on-demand`:

    git submodule status
    git push --recurse-submodules=on-demand|check

You should take into account that submodules behaves like ordinary Git repositories so you not be able to push without changing their remote origin. I warned you...

Updating this repo via `git pull` should be quite straightforward:

    git pull
    git submodule update
    composer adeira:collect
    composer update
    ./run-tests.sh

Adding and Removing submodules
-----
Adding new submodules:

    git submodule add git@github.com:adeira/compiler-extension.git Component/compiler-extension
    git submodule add git@github.com:adeira/monolog.git Component/monolog
    ...

Removing submodules:
http://stackoverflow.com/questions/1260748/how-do-i-remove-a-submodule/1260982

Running tests
-----
    chmod +x run-tests.sh
    ./run-tests.sh

Composer commands
-----
There are two commands to help maintain submodules in the superproject:

    composer adeira:collect
    composer adeira:eject

Collect command will generate root `composer.json` with all rependencies, autoloaders and replace definitions. This is because I want only one vendor with one dependency versions. It's ispired by [this article](http://www.whitewashing.de/2015/04/11/monolithic_repositories_with_php_and_composer.html) and [this project](https://github.com/beberlei/composer-monorepo-plugin).

Eject command works conversely. It replaces dependency requirements in submodules based on previous collection. Thanks to this process you'll have dependencies always unified across all repositories in Adeira superproject.
