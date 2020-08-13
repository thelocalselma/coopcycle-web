#!/bin/sh

help()
{
  echo "Performs a full production build of docker images for:"
  echo "* coopcyle PHP web server"
  echo "* tracking/dispatch location server"
  echo
  echo "Along the way it also builds the web application frontend."
  echo "The build process is containerized. The only host system requirements"
  echo "are /bin/sh and docker."
  echo
  echo "Syntax: $0 [-p containerTagPrefix]"
  echo "  -h  show this help text"
  echo "  -p [prefix]  Tag the built containers with the specified prefix,"
  echo "               e.g. -p coopcycle => coopcycle/coopcycle-web"
}

cd $(dirname "$0")
SCRIPT_DIR=$(pwd)
APP_DIR="$SCRIPT_DIR"/../..

CONTAINER_TAG_PREFIX=jasonprado

while getopts "hp:" arg; do
  case $arg in
    h)
      help
      exit
      ;;
    p)
      CONTAINER_TAG_PREFIX=$OPTARG
      ;;
  esac
done

set -x

cd "$APP_DIR"
docker build . -f deploy/docker/php/Dockerfile -t "$CONTAINER_TAG_PREFIX/coopcycle-web"
docker build . -f deploy/docker/nodejs/Dockerfile -t "$CONTAINER_TAG_PREFIX/coopcycle-locationserver"
