#!/usr/bin/env bash

CURRENT_DIRECTORY=`pwd`
BIN_DIRECTORY="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

PROJECT_DIRECTORY="$BIN_DIRECTORY/.."

echo "Copying $BIN_DIRECTORY/pre-commit.sh -> $PROJECT_DIRECTORY/.git/hooks/pre-commit"

cp "$BIN_DIRECTORY/pre-commit.sh" "$PROJECT_DIRECTORY/.git/hooks/pre-commit"
chmod 0777 "$PROJECT_DIRECTORY/.git/hooks/pre-commit"
cd $CURRENT_DIRECTORY;