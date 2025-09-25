#!/bin/bash

set -euo pipefail

TM_MIGRATION_DIR="vendor/toumoro/tm-migration"
DEST_DIR="."

if [ ! -d "${DEST_DIR}/migration" ]; then
    mkdir -p "${DEST_DIR}/migration"
    cp -rp "${TM_MIGRATION_DIR}/migration/" "${DEST_DIR}/migration/"
    echo "Migration folder copied to ${DEST_DIR}/migration/"
fi