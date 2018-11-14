FROM ubuntu

RUN apt-get update
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y tzdata
RUN apt-get install -y --no-install-recommends nginx php-fpm php-mysqli openssh-server vim supervisor git

COPY supervisor_all.conf /etc/supervisor/conf.d/all.conf
COPY nginx_default /etc/nginx/sites-available/default

CMD /bin/sh /local/build/init.sh && exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
