# OSRM 

This container builds an OSRM server that has pre-cached information about a specified region, used for routing.

The location for the data is specified in the `Dockerfile`, which ought to be in the OSM PBF format.
This can be obtained by downloading an `.osm` file from the OSM website and using a tool like `osmconvert` to convert to the PBF format.

```
ARG OSM_DATA_URL=<YOUR_PBF_FILE>
```
