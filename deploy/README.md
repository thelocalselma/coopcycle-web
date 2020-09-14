# Deploy production-ready CoopCycle in 30 minutes.

By following these steps you will create an instance of CoopCycle hosted on a cloud platform. The instance will be ready to serve traffic, but will require more customization before you should consider launching.

Please read the next page of background information before getting started. A series of commands is provided at the end for kicking off the kubernetes deployment.


## Hosting Prerequisites
* An S3-like object storage system
* A PostgresSQL instance
* A Redis instance
* A Kubernetes (k8s) cluster

All of these are available from popular cloud providers: AWS, Google, Azure, DigitalOcean, and others.


## Understanding the services in CoopCycle
* `coopcycle-web` is a PHP webserver that serves a REST API and HTML pages to clients. It is where most of the business logic of the system lives.
* The location server, built out of `coopcycle-web/js/api`, is a node.js application that serves location tracking APIs over WebSockets for tracking and dispatch.
* Tile38 is an in-memory database for implementing geofencing features. You will need to host your own Tile38 server. A configuration is included in the `k8s/` directory.
* OSRM is a routing system for road navigation. You will need to host your own OSRM server. A configuration is included in the `k8s/` directory.
* Redis is an in-memory cache database. While you may host your own instance of it, it is recommended you utilize a cloud provider's hosting.
* PostgresSQL is a persistent relational database. While you may host your own instance of it, it is recommended you utilize a cloud provider's hosting.


## Understanding deployment options: docker-compose vs deploying containers vs Kubernetes
Each component of the CoopCycle deployment lives in its own container. There are several ways to use these containers.

* For local development and prototyping, a docker-compose file is provided in the project root. Bringing up this set of services will quickly provide you with a running instance of CoopCycle, but it is not suitable for production deployment.
* You can use the Kubernetes configuration in this repository to quickly deploy a production-ready instance of CoopCycle on a cloud host. Kubernetes adds technical complexity to the implementation but you can skip a lot of work by using the k8s configurations as written.
* Since each component of CoopCycle is containerized, you can run each docker image individually and configure them to connect manually. The docker-compose and k8s files serve as good documentation on how the services expect to communicate. This requires manual work but it is flexible.


## Configuring `.env` for deployment
Instance-specific configuration lives by convention in the `.env` file. Begin by copying `.env.dist` to `.env`. You should visit all of these settings, but here is a quick tour of settings you will need to modify right away:

* App configuration
```
APP_ENV=production
APP_DEBUG=0
APP_SECRET=<Generate a random string here>
COOPCYCLE_COUNTRY=fr  # Or us, etc.
COOPCYCLE_LOCALE=fr   # Or en, etc.
COOPCYCLE_REGION=fr   # Of us, etc.
```

* S3 credentials. Note you can use any S3-compatible service, including some open-source self-hosted providers.
```
S3_ENDPOINT=https://s3.amazonaws.com
S3_REGION=us-west-1
S3_BUCKET=<Your bucket name>
S3_CREDENTIALS_KEY=<Your S3 IAM credentials key>
S3_CREDENTIALS_SECRET=<Your S3 IAM credentials secret>
```

* PostgresSQL configuration
```
COOPCYCLE_DB_HOST=<Your database host>
COOPCYCLE_DB_PORT=5432
COOPCYCLE_DB_NAME=<Your database name>
COOPCYCLE_DB_USER=<Your database user>
COOPCYCLE_DB_PASSWORD=<Your database password>
```

* Redis configuration
```
COOPCYCLE_REDIS_DSN=redis://<Your Redis auth key>@<Your Redis host>:15231
```

* Tile38 & OSRM

If you are using the Kubernetes deployment, or if you are setting up containers for Tile38 and OSRM such that their hostnames are `tile38` and `osrm`, you can keep these defaults:
```
COOPCYCLE_TILE38_DSN=redis://tile38:9851
COOPCYCLE_OSRM_HOST=osrm:5000
```


_Tip: If you want to have one `.env` for local development and another for deployment, consider renaming your production `.env` to `.env.production`. When you copy the file to the production host, rename it to `.env` again._


## Creating the demo database
The PostgresSQL database must be initialized with a schema and some initial data. After configuring the `.env` file, execute `deploy/scripts/create-schema.sh`. This will require network access to the database specified in `.env`.


## Kubernetes deployment
* After configuring your `.env` (or `.env.production`) file, upload it as a ConfigMap to your cluster:
```
> kubectl create configmap php-env-config --from-file=.env
```

** When you update `.env`, you will need to replace the ConfigMap:
```
kubectl create configmap php-env-config --from-file .env -o yaml --dry-run=client | kubectl replace -f -
```

* Apply the k8s configuration YAML files:
```
> kubectl apply -f deploy/k8s/
```

* Get the IP of the `php-and-nginx-service` service and open it in your browser:
```
> kubectl get svc
...
php-and-nginx-service   LoadBalancer   10.3.250.231   *34.5.29.91   80:31653/TCP        1h
```

You should see a default installation of CoopCycle.


## Next steps
Besides starting your cooperative, you will need to configure several other services:
* Google API key for maps and other services
* Facebook API key
* Matomo analytics configuration
* Sentry error-logging configuration
* Mail service


## Todo (development)
* Bring up and connect OSRM pod
* Map `var/jwt` using secrets
