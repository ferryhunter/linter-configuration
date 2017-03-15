FILES=`git ls-files -m --others --exclude-standard | grep .php$`


phpcs --standard=tools/CodeSniffer/Standards/TP24/ruleset.xml $FILES

