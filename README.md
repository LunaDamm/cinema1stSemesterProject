# cinema1stSemesterProject
1st Semester Project for Web Development.

## Owner dashboard

Run the project database migration after starting Docker:

```sh
docker compose exec -T database sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" movieDB' < config/initdb/002_owner_backend.sql
```

Open `/admin/login.php`. On a fresh installation, use **Create the first owner account**. The setup page is disabled as soon as an admin account exists. Owner sessions protect the dashboard, and all dashboard forms use CSRF tokens.

The dashboard provides company settings, movies, showings and front-page premieres, news, booking overview, and invoice creation/status management.
