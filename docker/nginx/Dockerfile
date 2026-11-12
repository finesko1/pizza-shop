ARG NGINX_VERSION=1.30
ARG RELEASE_IMAGE=app-prod:latest

FROM ${RELEASE_IMAGE} AS release

FROM nginx:${NGINX_VERSION}-alpine

COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template

COPY --from=release /var/www/html/public /var/www/html/public

RUN mkdir -p /etc/nginx/upstreams

EXPOSE 80