FROM osrm/osrm-backend:v5.16.0

# Locations for the OSM data to be loaded into the container
ARG OSM_DATA_URL=https://coopcycle-osrm.s3.amazonaws.com/selma-alabama-us.osm.pbf
ARG OSM_PROFILE=bicycle

# Fix "can't execute 'ssl_helper'"
# https://github.com/Yelp/dumb-init/issues/73
RUN apk --no-cache add openssl wget
	
COPY ./start.sh /usr/local/bin/osrm-start

RUN chmod +x /usr/local/bin/osrm-start

RUN mkdir /data && \
    wget --no-check-certificate $OSM_DATA_URL -O /data/data.osm.pbf && \
    osrm-extract -p /opt/$OSM_PROFILE.lua /data/data.osm.pbf && \
    osrm-partition /data/data.osrm && \
    osrm-customize /data/data.osrm

CMD ["osrm-start"]
