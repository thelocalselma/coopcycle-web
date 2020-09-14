FROM node:12-alpine

RUN apk update && apk add --no-cache git

RUN npm install -g pm2@^4.2

WORKDIR /locationserver/

COPY package.json /locationserver/
COPY package-lock.json /locationserver/

RUN npm install

COPY docker/locationserver/start.sh /locationserver/
RUN chmod +x /locationserver/start.sh

COPY js/api /locationserver/js/api/

COPY pm2.config.js /locationserver/

ENTRYPOINT ["/locationserver/start.sh"]
