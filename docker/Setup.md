# Creating a Chamilo 2  test containers stack

To test Chamilo2 you can create a container based on the latest published code in GitHub as well as latest version of the base containers. **This is by no means a recommended approach for Production**.

As per the base [README](../README.md), this folder will use a base  PHP 8 image and add the various elements required before cloning the current Chamilo repository.

## Standalone Chamilo 2 container

You can use the provided [Dockerfile](Dockerfile) to build your own.
Not all possible PHP extensions have been enabled but only the required ones as well as APCu as an example.

You can easily modify it to add more extensions. Layers are not squashed to make sure you can refresh Chamilo source for example by rebuilding without eventually needing refresh the previous layers.

As for the database it expect you can point to yours or use a default MariaDB container when using the `docker compose up` version.

## Test stack (`docker compose` approach)

Please note that you will need to create a `.env` file to define the variables of MariaDB in that case.

```ini
MYSQL_ROOT_PASSWORD=securePassword
MYSQL_DATABASE=chamilo
MYSQL_USER=root
MYSQL_PASSWORD=
```

While configuring Chamilo, use **`mariadb`** as the server name and whatever values you did set in the `.env` file to create the connection to the database.

Volumes are created as named volumes to be persisted on your docker host. You can find options inside the [docker-compose.yml](docker-compose.yml) to use binded volumes or seed from an existing database export.

If you do not intend to rebuild every time you stand up the stack,please comment out the `build` instructionsint the `docker-compose.yml` section for the Chamilo container.
 
