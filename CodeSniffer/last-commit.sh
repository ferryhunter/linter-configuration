FILES=`git show --pretty="format:" --name-only`


phpcs --standard=tools/CodeSniffer/Standards/TP24/ruleset.xml $FILES

