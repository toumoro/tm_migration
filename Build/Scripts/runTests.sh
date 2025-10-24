#!/usr/bin/env bash

#
# TYPO3 core test runner based on docker or podman
#

trap 'cleanUp;exit 2' SIGINT

waitFor() {
    local HOST=${1}
    local PORT=${2}
    local TESTCOMMAND="
        COUNT=0;
        while ! nc -z ${HOST} ${PORT}; do
            if [ \"\${COUNT}\" -gt 20 ]; then
              echo \"Can not connect to ${HOST} port ${PORT}. Aborting.\";
              exit 1;
            fi;
            sleep 1;
            COUNT=\$((COUNT + 1));
        done;
    "
    ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name wait-for-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${IMAGE_ALPINE} /bin/sh -c "${TESTCOMMAND}"
    if [[ $? -gt 0 ]]; then
        kill -SIGINT -$$
    fi
}

cleanUp() {
    ATTACHED_CONTAINERS=$(${CONTAINER_BIN} ps --filter network=${NETWORK} --format='{{.Names}}')
    for ATTACHED_CONTAINER in ${ATTACHED_CONTAINERS}; do
        ${CONTAINER_BIN} kill ${ATTACHED_CONTAINER} >/dev/null
    done
    if [ ${CONTAINER_BIN} = "docker" ]; then
        ${CONTAINER_BIN} network rm ${NETWORK} >/dev/null
    else
        ${CONTAINER_BIN} network rm -f ${NETWORK} >/dev/null
    fi
}

handleDbmsOptions() {
    # -a, -d, -i depend on each other. Validate input combinations and set defaults.
    case ${DBMS} in
        mariadb)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "${HELP_MESSAGE}" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="10.4"
            if ! [[ ${DBMS_VERSION} =~ ^(10.4|10.5|10.6|10.7|10.8|10.9|10.10|10.11|11.0|11.1)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "${HELP_MESSAGE}" >&2
                exit 1
            fi
            ;;
        mysql)
            [ -z "${DATABASE_DRIVER}" ] && DATABASE_DRIVER="mysqli"
            if [ "${DATABASE_DRIVER}" != "mysqli" ] && [ "${DATABASE_DRIVER}" != "pdo_mysql" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "${HELP_MESSAGE}" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="8.0"
            if ! [[ ${DBMS_VERSION} =~ ^(8.0|8.1|8.2|8.3)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "${HELP_MESSAGE}" >&2
                exit 1
            fi
            ;;
        postgres)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "${HELP_MESSAGE}" >&2
                exit 1
            fi
            [ -z "${DBMS_VERSION}" ] && DBMS_VERSION="10"
            if ! [[ ${DBMS_VERSION} =~ ^(10|11|12|13|14|15|16)$ ]]; then
                echo "Invalid combination -d ${DBMS} -i ${DBMS_VERSION}" >&2
                echo >&2
                echo "${HELP_MESSAGE}" >&2
                exit 1
            fi
            ;;
        sqlite)
            if [ -n "${DATABASE_DRIVER}" ]; then
                echo "Invalid combination -d ${DBMS} -a ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "${HELP_MESSAGE}" >&2
                exit 1
            fi
            if [ -n "${DBMS_VERSION}" ]; then
                echo "Invalid combination -d ${DBMS} -i ${DATABASE_DRIVER}" >&2
                echo >&2
                echo "${HELP_MESSAGE}" >&2
                exit 1
            fi
            ;;
        *)
            echo "Invalid option -d ${DBMS}" >&2
            echo >&2
            echo "${HELP_MESSAGE}" >&2
            exit 1
            ;;
    esac
}

getPhpImageVersion() {
    case ${1} in
        8.2)
            echo -n "1.12"
            ;;
        8.3)
            echo -n "1.13"
            ;;
    esac
}

loadHelp() {
    # Load help text into $HELP
    read -r -d '' HELP <<EOF
TYPO3 core test runner. Execute functional tests in a container based test environment.

Usage: $0 [options] [file]

Options:
    -b <docker|podman>
        Container environment:
            - podman (default)
            - docker

    -a <mysqli|pdo_mysql>
        Specifies to use another driver, following combinations are available:
            - mysql
                - mysqli (default)
                - pdo_mysql
            - mariadb
                - mysqli (default)
                - pdo_mysql

    -d <sqlite|mariadb|mysql|postgres>
        Specifies on which DBMS tests are performed
            - sqlite: (default): use sqlite
            - mariadb: use mariadb
            - mysql: use MySQL
            - postgres: use postgres

    -i version
        Specify a specific database version
        With "-d mariadb":
            - 10.4   short-term, maintained until 2024-06-18 (default)
            - 10.5   short-term, maintained until 2025-06-24
            - 10.6   long-term, maintained until 2026-06
            - 10.7   short-term, no longer maintained
            - 10.8   short-term, maintained until 2023-05
            - 10.9   short-term, maintained until 2023-08
            - 10.10  short-term, maintained until 2023-11
            - 10.11  long-term, maintained until 2028-02
            - 11.0   development series
            - 11.1   short-term development series
        With "-d mysql":
            - 8.0   maintained until 2026-04 (default) LTS
            - 8.1   unmaintained since 2023-10
            - 8.2   unmaintained since 2024-01
            - 8.3   maintained until 2024-04
        With "-d postgres":
            - 10    unmaintained since 2022-11-10 (default)
            - 11    unmaintained since 2023-11-09
            - 12    maintained until 2024-11-14
            - 13    maintained until 2025-11-13
            - 14    maintained until 2026-11-12
            - 15    maintained until 2027-11-11
            - 16    maintained until 2028-11-09

    -c <chunk/numberOfChunks>
        Hack functional tests into #numberOfChunks pieces and run tests of #chunk.
        Example -c 3/13

    -p <8.2|8.3>
        Specifies the PHP minor version to be used
            - 8.2 (default): use PHP 8.2
            - 8.3: use PHP 8.3

    -e "<phpunit options>" (DEPRECATED).
        Additional options to send to phpunit.
        DEPRECATED - pass arguments after the "--" separator directly.

    -x
        Send information to host instance for test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -h
        Show this help.

Examples:
    # Run functional tests on postgres with xdebug, php 8.3 and execute a restricted set of tests
    $THIS_SCRIPT_NAME -x -p 8.3 -d postgres -i 12 -- --filter PageActionsTest

    # Run functional tests on postgres 11
    $THIS_SCRIPT_NAME -d postgres -i 11

EOF
}

# Test if docker exists, else exit out with error
if ! type "docker" >/dev/null 2>&1 && ! type "podman" >/dev/null 2>&1; then
    echo "This script relies on docker or podman. Please install" >&2
    exit 1
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called. Later, go to the
# project root.
THIS_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
cd "$THIS_SCRIPT_DIR" || exit 1

RUNTESTS_ENV="${RUNTESTS_ENV:=../../runTests.env}"
if [ -f "${RUNTESTS_ENV}" ] ; then
  echo "Using runTests config from: ${RUNTESTS_ENV}"
  source "${RUNTESTS_ENV}"
  while IFS='=' read -r name value ; do
    if [[ $name == RUNTESTS_DIR_* ]]; then
      if [[ $value != */ ]]; then
        export "$name=$value/"
      fi
    fi
  done < <(env)
else
  # Default files when runTests.env is missing, using TYPO3 core configuration:
  RUNTESTS_DIR_ROOT="${RUNTESTS_DIR_ROOT:=../../}"
  RUNTESTS_DIR_BUILDER="${RUNTESTS_DIR_BUILDER:=Build/Scripts/}"
  RUNTESTS_DIR_BIN="${RUNTESTS_DIR_BIN:=vendor/bin/}"
  RUNTESTS_DIR_TESTTEMP="${RUNTESTS_DIR_TESTTEMP:=typo3temp/var/tests/}"
  RUNTESTS_DIR_CACHE="${RUNTESTS_DIR_TESTTEMP:=.cache/}"
  RUNTESTS_PHPUNIT_FILE_FUNCTIONALTEST="${RUNTESTS_PHPUNIT_FILE_FUNCTIONALTEST:=Build/phpunit/FunctionalTests.xml}"
  RUNTESTS_DIR_PHPUNIT_FUNCTIONAL="${RUNTESTS_DIR_PHPUNIT_FUNCTIONAL:=Build/phpunit/}"
fi

# Now go into the actual "base" directory.
cd "$RUNTESTS_DIR_ROOT" || exit 1
CORE_ROOT="${PWD}"

# Default variables
TEST_SUITE="functional"
DBMS="sqlite"
DBMS_VERSION=""
PHP_VERSION="8.2"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
EXTRA_TEST_OPTIONS=""
DATABASE_DRIVER=""
CHUNKS=0
THISCHUNK=0
CONTAINER_BIN=""
COMPOSER_ROOT_VERSION="13.2.x-dev"
CONTAINER_SHELL="/bin/sh"
CONTAINER_INTERACTIVE="-it --init"
HOST_UID=$(id -u)
HOST_PID=$(id -g)
USERSET=""
SUFFIX=$(echo $RANDOM)
NETWORK="typo3-core-${SUFFIX}"
CI_PARAMS="${CI_PARAMS:-}"
CONTAINER_HOST="host.docker.internal"
THIS_SCRIPT_NAME="${RUNTESTS_DIR_BUILDER}runTests.sh"
HELP_MESSAGE="Use \"${THIS_SCRIPT_NAME} -h\" to display help and valid options"

# Option parsing
OPTIND=1
INVALID_OPTIONS=()
while getopts ":a:b:c:d:i:p:e:xy:h" OPT; do
    case ${OPT} in
        b)
            if ! [[ ${OPTARG} =~ ^(docker|podman)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            CONTAINER_BIN=${OPTARG}
            ;;
        a)
            DATABASE_DRIVER=${OPTARG}
            ;;
        c)
            if ! [[ ${OPTARG} =~ ^([0-9]+\/[0-9]+)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            else
                THISCHUNK=$(echo "${OPTARG}" | cut -d '/' -f1)
                CHUNKS=$(echo "${OPTARG}" | cut -d '/' -f2)
            fi
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        i)
            DBMS_VERSION=${OPTARG}
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8.2|8.3)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        e)
            EXTRA_TEST_OPTIONS=${OPTARG}
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        h)
            loadHelp
            echo "${HELP}"
            exit 0
            ;;
        \")
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-${I}" >&2
    done
    echo >&2
    echo "${HELP_MESSAGE}" >&2
    exit 1
fi

handleDbmsOptions

if [ "${CI}" == "true" ]; then
    CONTAINER_INTERACTIVE=""
fi

if [[ -z "${CONTAINER_BIN}" ]]; then
    if type "podman" >/dev/null 2>&1; then
        CONTAINER_BIN="podman"
    elif type "docker" >/dev/null 2>&1; then
        CONTAINER_BIN="docker"
    fi
fi

if [ $(uname) != "Darwin" ] && [ ${CONTAINER_BIN} = "docker" ]; then
    USERSET="--user $HOST_UID"
fi

if ! type ${CONTAINER_BIN} >/dev/null 2>&1; then
    echo "Selected container environment \"${CONTAINER_BIN}\" not found. Please install or use -b option to select one." >&2
    exit 1
fi

IMAGE_PHP="ghcr.io/typo3/core-testing-$(echo "php${PHP_VERSION}" | sed -e 's/\.//'):$(getPhpImageVersion $PHP_VERSION)"
IMAGE_ALPINE="docker.io/alpine:3.8"
IMAGE_REDIS="docker.io/redis:4-alpine"
IMAGE_MEMCACHED="docker.io/memcached:1.5-alpine"
IMAGE_MARIADB="docker.io/mariadb:${DBMS_VERSION}"
IMAGE_MYSQL="docker.io/mysql:${DBMS_VERSION}"
IMAGE_POSTGRES="docker.io/postgres:${DBMS_VERSION}-alpine"

shift $((OPTIND - 1))

mkdir -p .cache

${CONTAINER_BIN} network create ${NETWORK} >/dev/null

if [ ${CONTAINER_BIN} = "docker" ]; then
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm --network ${NETWORK} --add-host "${CONTAINER_HOST}:host-gateway" ${USERSET} -v ${CORE_ROOT}:${CORE_ROOT} -w ${CORE_ROOT}"
else
    CONTAINER_HOST="host.containers.internal"
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} ${CI_PARAMS} --rm --network ${NETWORK} -v ${CORE_ROOT}:${CORE_ROOT} -w ${CORE_ROOT}"
fi

if [ ${PHP_XDEBUG_ON} -eq 0 ]; then
    XDEBUG_MODE="-e XDEBUG_MODE=off"
    XDEBUG_CONFIG=" "
else
    XDEBUG_MODE="-e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=foo"
    XDEBUG_CONFIG="client_port=${PHP_XDEBUG_PORT} client_host=${CONTAINER_HOST}"
fi

# Suite execution: functional
if [ "${CHUNKS}" -gt 0 ]; then
    ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name func-splitter-${SUFFIX} -e CORE_ROOT="${CORE_ROOT}" -e RUNTESTS_DIRS_RPOJECT="${RUNTESTS_DIRS_RPOJECT}" ${IMAGE_PHP} php -dxdebug.mode=off ${RUNTESTS_DIR_BUILDER}splitFunctionalTests.php -v ${CHUNKS}
    COMMAND=(${RUNTESTS_DIR_BIN}phpunit -c ${RUNTESTS_DIR_PHPUNIT_FUNCTIONAL}FunctionalTests-Job-${THISCHUNK}.xml --exclude-group not-${DBMS} ${EXTRA_TEST_OPTIONS} "$@")
else
    COMMAND=(${RUNTESTS_DIR_BIN}phpunit -c ${RUNTESTS_PHPUNIT_FILE_FUNCTIONALTEST} --exclude-group not-${DBMS} ${EXTRA_TEST_OPTIONS} "$@")
fi
${CONTAINER_BIN} run --rm ${CI_PARAMS} --name redis-func-${SUFFIX} --network ${NETWORK} -d ${IMAGE_REDIS} >/dev/null
${CONTAINER_BIN} run --rm ${CI_PARAMS} --name memcached-func-${SUFFIX} --network ${NETWORK} -d ${IMAGE_MEMCACHED} >/dev/null
waitFor redis-func-${SUFFIX} 6379
waitFor memcached-func-${SUFFIX} 11211
CONTAINER_COMMON_PARAMS="${CONTAINER_COMMON_PARAMS} -e typo3TestingRedisHost=redis-func-${SUFFIX} -e typo3TestingMemcachedHost=memcached-func-${SUFFIX}"
case ${DBMS} in
    mariadb)
        echo "Using driver: ${DATABASE_DRIVER}"
        ${CONTAINER_BIN} run --rm ${CI_PARAMS} --name mariadb-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid ${IMAGE_MARIADB} >/dev/null
        waitFor mariadb-func-${SUFFIX} 3306
        CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mariadb-func-${SUFFIX} -e typo3DatabasePassword=funcp"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    mysql)
        echo "Using driver: ${DATABASE_DRIVER}"
        ${CONTAINER_BIN} run --rm ${CI_PARAMS} --name mysql-func-${SUFFIX} --network ${NETWORK} -d -e MYSQL_ROOT_PASSWORD=funcp --tmpfs /var/lib/mysql/:rw,noexec,nosuid ${IMAGE_MYSQL} >/dev/null
        waitFor mysql-func-${SUFFIX} 3306
        CONTAINERPARAMS="-e typo3DatabaseDriver=${DATABASE_DRIVER} -e typo3DatabaseName=func_test -e typo3DatabaseUsername=root -e typo3DatabaseHost=mysql-func-${SUFFIX} -e typo3DatabasePassword=funcp"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    postgres)
        ${CONTAINER_BIN} run --rm ${CI_PARAMS} --name postgres-func-${SUFFIX} --network ${NETWORK} -d -e POSTGRES_PASSWORD=funcp -e POSTGRES_USER=funcu --tmpfs /var/lib/postgresql/data:rw,noexec,nosuid ${IMAGE_POSTGRES} >/dev/null
        waitFor postgres-func-${SUFFIX} 5432
        CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_pgsql -e typo3DatabaseName=bamboo -e typo3DatabaseUsername=funcu -e typo3DatabaseHost=postgres-func-${SUFFIX} -e typo3DatabasePassword=funcp"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
    sqlite)
        # create sqlite tmpfs mount (temp)functional-sqlite-dbs/ to avoid permission issues
        mkdir -p "${CORE_ROOT}/${RUNTESTS_DIR_TESTTEMP}functional-sqlite-dbs/"
        CONTAINERPARAMS="-e typo3DatabaseDriver=pdo_sqlite --tmpfs ${CORE_ROOT}/${RUNTESTS_DIR_TESTTEMP}functional-sqlite-dbs/:rw,noexec,nosuid"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name functional-${SUFFIX} ${XDEBUG_MODE} -e XDEBUG_CONFIG="${XDEBUG_CONFIG}" ${CONTAINERPARAMS} ${IMAGE_PHP} "${COMMAND[@]}"
        SUITE_EXIT_CODE=$?
        ;;
esac

cleanUp

# Print summary
echo "" >&2
echo "###########################################################################" >&2
echo "Result of functional tests" >&2
echo "Container runtime: ${CONTAINER_BIN}" >&2
echo "PHP: ${PHP_VERSION}" >&2
case "${DBMS}" in
    mariadb|mysql|postgres)
        echo "DBMS: ${DBMS}  version ${DBMS_VERSION}  driver ${DATABASE_DRIVER}" >&2
        ;;
    sqlite)
        echo "DBMS: ${DBMS}" >&2
        ;;
esac
if [[ -n ${EXTRA_TEST_OPTIONS} ]]; then
    echo " Note: Using -e is deprecated. Simply add the options at the end of the command."
    echo " Instead of: ${THIS_SCRIPT_NAME} -e '${EXTRA_TEST_OPTIONS}' $@"
    echo " use:        ${THIS_SCRIPT_NAME} -- ${EXTRA_TEST_OPTIONS} $@"
fi
if [[ ${SUITE_EXIT_CODE} -eq 0 ]]; then
    echo "SUCCESS" >&2
else
    echo "FAILURE" >&2
fi
echo "###########################################################################" >&2
echo "" >&2

exit $SUITE_EXIT_CODE