#!/bin/sh

cd /locationserver
npm install
pm2-runtime pm2.config.js
