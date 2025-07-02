rturo.ramirez@Arturos-MacBook-Pro Palafito-b2b % ./docker-dev.sh up
[23:36:44] 🚀 Iniciando servicios de Palafito B2B...
[+] Building 4.8s (23/42)                                                       
 => [internal] load local bake definitions                                 0.0s
 => => reading from stdin 745B                                             0.0s
 => [wp-cli internal] load build definition from Dockerfile                0.0s
 => => transferring dockerfile: 1.32kB                                     0.0s
 => [wordpress internal] load build definition from Dockerfile             0.0s
 => => transferring dockerfile: 1.99kB                                     0.0s
 => [wordpress internal] load metadata for docker.io/library/composer:lat  1.7s
 => [wordpress internal] load metadata for docker.io/library/wordpress:fp  2.6s
 => [wp-cli internal] load metadata for docker.io/library/wordpress:cli    1.7s
 => [auth] library/wordpress:pull token for registry-1.docker.io           0.0s
 => [auth] library/composer:pull token for registry-1.docker.io            0.0s
 => [wordpress internal] load .dockerignore                                0.0s
 => => transferring context: 2B                                            0.0s
 => [wp-cli  1/15] FROM docker.io/library/wordpress:cli@sha256:55e9043e77  2.4s
 => => resolve docker.io/library/wordpress:cli@sha256:55e9043e776bce94e13  0.0s
 => => sha256:43273f456cbb73c6ee2e2705df5af4e4d9d7eff1fc6051a 406B / 406B  0.2s
 => => sha256:e8ea155fe187a8a89ba67218470591ec55fea3cc8c4 1.53MB / 1.53MB  0.3s
 => => sha256:2bc72c60826433de5fe31b1ae3100e07900cbe6b71127de 387B / 387B  0.3s
 => => sha256:23a1289d548a0b15b94b12ff7cce645517492262d 14.20MB / 14.20MB  0.6s
 => => sha256:65899e43e1a99053957e39d70924a61f0c6f59587 11.07MB / 11.07MB  0.5s
 => => sha256:f70a5e5b4f10f3df79db68fd3d90450909a8bf049 19.99kB / 19.99kB  0.4s
 => => sha256:4770e17c402513ccbc52e6e702d7a20fe6abd6896 19.99kB / 19.99kB  0.4s
 => => sha256:4d3a6ee4c2754b60eb390654b5e16cb4dd128f75d9d 2.45kB / 2.45kB  0.2s
 => => sha256:b756cf31aa28dd017e7d1f67ec032371ec6f28f67 16.99MB / 16.99MB  1.0s
 => => sha256:07e1886d8eb2e018b831c7480452eed09500d22186a95e9 495B / 495B  0.3s
 => => sha256:787ca327d61f936376c60cdebf4afeed133939247 12.17MB / 12.17MB  0.6s
 => => sha256:a8b695911010ae65b6cf4c4b5ac350b27932e3b4b29077f 214B / 214B  0.3s
 => => sha256:d91ea4522a211c5e50ab5b22083b52449e7ad38398a1f6e 934B / 934B  0.2s
 => => sha256:5d74afa414d93be74315fb2fbbededff31189950343 3.47MB / 3.47MB  0.3s
 => => extracting sha256:5d74afa414d93be74315fb2fbbededff31189950343736eb  0.1s
 => => extracting sha256:d91ea4522a211c5e50ab5b22083b52449e7ad38398a1f6e5  0.0s
 => => extracting sha256:a8b695911010ae65b6cf4c4b5ac350b27932e3b4b29077f6  0.0s
 => => extracting sha256:787ca327d61f936376c60cdebf4afeed133939247c75fb1d  0.0s
 => => extracting sha256:07e1886d8eb2e018b831c7480452eed09500d22186a95e93  0.0s
 => => extracting sha256:b756cf31aa28dd017e7d1f67ec032371ec6f28f67bd16689  0.3s
 => => extracting sha256:4d3a6ee4c2754b60eb390654b5e16cb4dd128f75d9d3c7f3  0.0s
 => => extracting sha256:4770e17c402513ccbc52e6e702d7a20fe6abd68969478368  0.0s
 => => extracting sha256:f70a5e5b4f10f3df79db68fd3d90450909a8bf04997b947e  0.0s
 => => extracting sha256:65899e43e1a99053957e39d70924a61f0c6f59587ffb12c5  0.2s
 => => extracting sha256:4f4fb700ef54461cfa02571ae0db9a0dc1e0cdb5577484a6  0.0s
 => => extracting sha256:23a1289d548a0b15b94b12ff7cce645517492262ddd24e31  0.2s
 => => extracting sha256:2bc72c60826433de5fe31b1ae3100e07900cbe6b71127dec  0.0s
 => => extracting sha256:e8ea155fe187a8a89ba67218470591ec55fea3cc8c40a080  0.0s
 => => extracting sha256:43273f456cbb73c6ee2e2705df5af4e4d9d7eff1fc6051a2  0.0s
 => [wp-cli internal] load build context                                   0.0s
 => => transferring context: 6.36kB                                        0.0s
 => [wordpress stage-0  1/15] FROM docker.io/library/wordpress:fpm-alpine  1.9s
 => => resolve docker.io/library/wordpress:fpm-alpine@sha256:f7b5b9baabd8  0.0s
 => => sha256:73e8227ed42ab9d41379a84b4724590ebab9daff3505a0c02 0B / 198B  0.6s
 => => sha256:e3a1cb1d65daa5894fdaf712cd70163189d688a4220e6a8 0B / 1.76kB  0.4s
 => [wordpress internal] load build context                                0.0s
 => => transferring context: 4.83kB                                        0.0s
 => [wordpress] FROM docker.io/library/composer:latest@sha256:9f2a31e610b  1.9s
 => => resolve docker.io/library/composer:latest@sha256:9f2a31e610b009bbf  0.0s
 => => sha256:821b9dea24a435946ecb336cb8278f7297bed4a94795980b9 92B / 92B  0.2s
 => => sha256:2b24a8ffa2909a504d9bbfc6df46cc2b56d610d921e33e9 418B / 418B  0.2s
 => => sha256:7ddf66bb9ba56337081b692d4129b361f983e7f 976.04kB / 976.04kB  0.3s
 => => sha256:afb4fdb28ebd43bf90c0134d109bd7f9e79aa657ab4e9e0 256B / 256B  0.3s
 => => sha256:db90ec20dde1245e26657e9eeab7a68a4f381ea64 34.01MB / 34.01MB  0.8s
 => => sha256:8ed11a0655a2eb0f0eae0b13f815d1385ec0d4bc0 19.99kB / 19.99kB  0.2s
 => => sha256:b5659da52250b6def060ce2459caf69d028600439 20.00kB / 20.00kB  0.2s
 => => sha256:7813f4e30ce21280209e560b03ffd244261e042d03b 2.45kB / 2.45kB  0.2s
 => => sha256:e4e2192f9a8f7dc985e1b1534af7a423269d9525c 18.87MB / 20.52MB  0.9s
 => => sha256:2214385dee2a6502125f514cfafec9ffbab10a83686ed1e 491B / 491B  0.2s
 => => sha256:00e2341d4b1828e7c29626f05458e9c7ac77b849f 11.53MB / 13.64MB  0.8s
 => ERROR [wp-cli  2/15] RUN apk add --no-cache     bash     curl     git  0.3s
 => CANCELED [wordpress stage-0  2/15] RUN apk add --no-cache     bash     0.0s
 => CACHED [wordpress stage-0  3/15] RUN docker-php-ext-configure gd --wi  0.0s
 => CACHED [wordpress stage-0  4/15] RUN pecl install imagick     && dock  0.0s
 => CACHED [wordpress stage-0  5/15] RUN pecl install redis     && docker  0.0s
 => CACHED [wordpress stage-0  6/15] RUN pecl install xdebug     && docke  0.0s
 => CACHED [wordpress stage-0  7/15] COPY docker/wordpress/php.ini /usr/l  0.0s
 => CACHED [wordpress stage-0  8/15] COPY docker/wordpress/xdebug.ini /us  0.0s
 => CANCELED [wordpress stage-0  9/15] COPY --from=composer:latest /usr/b  0.0s
------
 > [wp-cli  2/15] RUN apk add --no-cache     bash     curl     git     mysql-client     redis     imagemagick     less     nano     jq:
0.132 ERROR: Unable to lock database: Permission denied
0.132 ERROR: Failed to open apk database: Permission denied
------
Dockerfile:5
--------------------
   4 |     # Instalar dependencias adicionales
   5 | >>> RUN apk add --no-cache \
   6 | >>>     bash \
   7 | >>>     curl \
   8 | >>>     git \
   9 | >>>     mysql-client \
  10 | >>>     redis \
  11 | >>>     imagemagick \
  12 | >>>     less \
  13 | >>>     nano \
  14 | >>>     jq
  15 |     
--------------------
target wp-cli: failed to solve: process "/bin/sh -c apk add --no-cache     bash     curl     git     mysql-client     redis     imagemagick     less     nano     jq" did not complete successfully: exit code: 99
