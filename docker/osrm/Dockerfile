FROM osrm/osrm-backend:v5.16.0

# Locations for the OSM data to be loaded into the container
ARG OSM_DATA_URL=https://coopcycle.org/osm/paris-france.osm.pbf
ARG OSM_PROFILE=bicycle
ENV OSRM_FILENAME=data.osrm

# Fix "can't execute 'ssl_helper'"
# https://github.com/Yelp/dumb-init/issues/73
RUN apk --no-cache add openssl wget
	
COPY ./start.sh /usr/local/bin/osrm-start

RUN chmod +x /usr/local/bin/osrm-start

RUN mkdir /data
RUN wget --no-check-certificate $OSM_DATA_URL -O /data/data.osm.pbf
RUN osrm-extract -p /opt/$OSM_PROFILE.lua /data/data.osm.pbf
RUN osrm-partition /data/$OSRM_FILENAME
RUN osrm-customize /data/$OSRM_FILENAME

CMD ["osrm-start"]
