#!/bin/bash

if [[ -z $1 ]];then
	exit 1
fi
PHP='
$composer = json_decode(file_get_contents("composer.json"));
$extra = $composer->{"extra"};
$extra->{"installer-types"} = ["component"];
$extra->{"installer-paths"} = ["public/components/" => ["type:component"],];
file_put_contents("composer.json", json_encode($composer, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
echo "Finalizado\n";
'

symfony new $1 --version="6.2.*" --webapp

pushd $1
	composer config --no-plugins allow-plugins.composer/installers true
	composer config --no-plugins allow-plugins.oomphinc/composer-installers-extender true
	composer require -n oomphinc/composer-installers-extender
	php -r "$PHP"
	composer require components/bootstrap

popd


