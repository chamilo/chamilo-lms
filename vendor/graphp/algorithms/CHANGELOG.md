# Changelog

## 0.8.2 (2020-02-20)

*   Feature: Add max depth parameter to breadth first search.
    (#27 by @phyrwork)

*   Feature: Replace recursive topological sort with iterative algorithm.
    (#25 by @phyrwork)

*   Fix: Fix option to merge parallel edges when creating residual graph.
    (#39 by @clue)

*   Fix: Fix setting upper limit for TSP bruteforce via MST algorithm.
    (#36 by @clue)

*   Minor code style improvements to make PHPStan happy,
    clean up dead code for depth first search and
    automated native_function_invocation fixes.
    (#35 and #40 by @clue and #37 by @draco2003)

*   Improve test suite to support PHPUnit 6 and PHPUnit 5 and
    support running on legacy PHP 5.3 through PHP 7.2 and HHVM.
    (#32 by @clue)

## 0.8.1 (2015-03-08)

*   Support graph v0.9 (while keeping BC)
    ([#16](https://github.com/graphp/algorithms/pull/16))

*   Deprecate internal algorithm base classes
    ([#15](https://github.com/graphp/algorithms/pull/15))

## 0.8.0 (2015-02-25)

*   First tagged release, split off from [clue/graph](https://github.com/clue/graph) v0.8.0
    ([#1](https://github.com/graphp/algorithms/issues/1))
