This is superproject for Adeira subpackages. You can read about superprojects and submodules in Git here:
- https://git-scm.com/docs/git-submodule
- https://git-scm.com/docs/git-push#git-push---recurse-submodulescheckon-demandno

Adding new submodules:

    git submodule add git@github.com:adeira/compiler-extension.git Component/compiler-extension
    git submodule add git@github.com:adeira/monolog.git Component/monolog
    ...

Initializing this repository:

    git submodule update --init

Commits should be performed in submodule (PhpStorm can handle it), but push should be executed from root superproject with flag `--recurse-submodules=on-demand`:

    git submodule status
    git push --recurse-submodules=on-demand|check

Removing submodules from superproject
-----
http://stackoverflow.com/questions/1260748/how-do-i-remove-a-submodule/1260982

Running tests
-----
    vendor/bin/tester Component/ -C

Composer commands
-----
    composer adeira:collect
    composer adeira:eject
