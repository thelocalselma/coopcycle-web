# Tilt is a tool for quickly iterating on services deployed on kubernetes
# Install it from tilt.dev and run `tilt up`
# Pre-requisites: docker & kubectl configured for a cluster (either local in Kind or cloud)

k8s_yaml([
  ('deploy/k8s/' + f) for f in [
    'locationserver-deployment.yaml',
    'locationserver-service.yaml',
    'php-and-nginx-pod-deployment.yaml',
    'php-and-nginx-service.yaml',
    'server-nginx-config.yaml',
    'tile38-pod.yaml',
    'tile38-service.yaml',
  ]
])

docker_build(
  'jasonprado/coopcycle-web',
  '.',
  dockerfile='docker/php/Dockerfile',
  live_update=[
    sync('./app', '/server/app'),
    sync('./src', '/server/src'),
    sync('./templates', '/server/templates'),
    sync('./web', '/server/web'),
  ]
)
docker_build('jasonprado/coopcycle-locationserver', '.', dockerfile='docker/locationserver/Dockerfile')
