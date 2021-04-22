# Local Development

## Docker-compose
There is a `docker-compose` configuration that setups everything to be able to develop easily on a dev machine.

## Caddy & TLS
Caddy installs TLS certificates on the docker container and not on the host machine, meaning that the host does not have access to the same
trust-store, returning various SSL errors when trying to connect to the website through `localhost`.
Although it is possible in chrome to ignore the untrusted certificate, it becomes a bit more complex
for other tools launched from the host trying to connect to the Orkestra Server's API (such as curl, postman etc.).

In order to hae a fully working TLS in local, one must create a trusted store on the host.

**Install `mkcert`**
Install [mkcert](https://github.com/FiloSottile/mkcert), using the pre-built binary
and placing it in a location in your path and ensuring it is executable.

```shell
# libnss3-tools is required for installing certutil which is a dependency of mkcert
sudo apt install libnss3-tools 
mkdir tls; cd tls
mkcert localhost
mkcert -install
```

This will create two files `localhost.pem`, `localhost-key.pem` that are referenced in the Caddy config.