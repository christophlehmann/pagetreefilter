#!/bin/sh

set -e

# export typo3DatabaseName=typo3testing
# export typo3DatabasePassword=root
# export typo3DatabaseUsername=root

find 'Tests/Functional' -wholename '*Test.php' | parallel --gnu 'echo; echo "Running functional test suite {}"; .Build/bin/phpunit -c Tests/Build/FunctionalTests.xml {}'