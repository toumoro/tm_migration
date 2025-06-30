#!/bin/bash

if [[ ! -d "migration" && -d "vendor/toumoro/tm-migration/migration/" ]]; then
    cp -r vendor/toumoro/tm-migration/migration/ migration/
fi