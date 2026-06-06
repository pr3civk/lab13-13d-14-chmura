Lab 13 - stack LEMP z phpMyAdmin (zadanie obowiazkowe)

Stack LEMP czyli Nginx, MySQL i PHP, do tego phpMyAdmin. Calosc odpalana przez docker compose,
4 kontenery.

Uslugi:
- nginx (nginx:1.27-alpine) - port 4001, sieci frontend i backend
- php (budowany z php:8.3-fpm, dodalem rozszerzenie mysqli) - siec backend
- mysql (mysql:8.4) - siec backend
- phpmyadmin (phpmyadmin:5.2) - port 6001, siec backend

Nginx jest jako jedyny w sieci frontend, bo to on przyjmuje ruch z zewnatrz i przekazuje
zapytania php do php-fpm po sieci backend.

phpMyAdmin dalem do backend, bo musi sie laczyc z mysql a mysql siedzi tylko w backend.
Do frontend nie musi byc podpiety, bo z przegladarki i tak wchodze po wystawionym porcie 6001
(wystawienie portu dziala niezaleznie od sieci).

Uruchomienie:

  docker compose up -d --build

Sprawdzenie ze dziala (docker compose ps):

  NAME              IMAGE               SERVICE      STATUS          PORTS
  lemp_mysql        mysql:8.4           mysql        Up 13 seconds   3306/tcp, 33060/tcp
  lemp_nginx        nginx:1.27-alpine   nginx        Up 13 seconds   0.0.0.0:4001->80/tcp
  lemp_php          lemp-php:8.3-fpm    php          Up 13 seconds   9000/tcp
  lemp_phpmyadmin   phpmyadmin:5.2      phpmyadmin   Up 13 seconds   0.0.0.0:6001->80/tcp

Strona startowa wchodzi pod http://localhost:4001 - index.php laczy sie z baza i pokazuje
POLACZONO oraz wersje MySQL 8.4.9.

phpMyAdmin pod http://localhost:6001 - curl zwraca HTTP 200. Loguje sie na root / rootpass
albo lempuser / lemppass.

Zalozenie testowej bazy (to samo co Nowa baza w phpMyAdmin, robie jako root):

  docker exec lemp_mysql mysql -uroot -prootpass -e "CREATE DATABASE IF NOT EXISTS testdb; SHOW DATABASES;"

  Database
  information_schema
  lempdb
  mysql
  performance_schema
  sys
  testdb

Sprawdzenie sieci - php i mysql tylko w backend, nginx w obu:

  docker network inspect lemp13_backend --format '{{range .Containers}}{{.Name}} {{end}}'
  -> lemp_mysql lemp_nginx lemp_php lemp_phpmyadmin
  docker network inspect lemp13_frontend --format '{{range .Containers}}{{.Name}} {{end}}'
  -> lemp_nginx

Zatrzymanie:

  docker compose down       (usuwa kontenery i sieci)
  docker compose down -v    (dodatkowo kasuje wolumen z danymi mysql)
