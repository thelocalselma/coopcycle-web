#!/bin/sh

if [ -f "/data/data.osrm" ]; then
    osrm-routed --algorithm mld /data/data.osrm
fi
