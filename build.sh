#!/bin/bash
php build/build.php

source build/meta.txt
git add .
git commit -m "Update to $version"
git tag $version

git push && git push --tags