#!/bin/bash
php-cs-fixer fix ./ --verbose --rules=@PSR2,-method_argument_space,-no_spaces_inside_parenthesis
