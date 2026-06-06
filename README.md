Sprawozdanie - PAwChO Laboratorium 13 / 13D / 14
Docker Compose - stack LEMP z phpMyAdmin

Autor: Piotr Preciuk


1. Cel

Zrobilem trzy zadania zbudowane na tym samym stacku LEMP (Linux, Nginx, MySQL, PHP) razem
z phpMyAdmin. Stack to 4 kontenery. Kolejne zadania dokladaja po jednej rzeczy:

  lemp13  - zadanie obowiazkowe z lab 13 - caly stack w jednym pliku docker-compose.yml
  lemp13d - zadanie nieobowiazkowe z lab 13D - dane wrazliwe przeniesione do secrets
  lemp14  - zadanie dodatkowe z lab 14 - podzial compose na plik bazowy i override (merge)

Katalogi compose13 i compose13d to przyklady z wykladu (monitoring oraz przyklad secrets),
nie sa czescia mojego rozwiazania.

Srodowisko: Docker 28.3.0, Docker Compose v2.38.1.

2. Architektura

Cztery uslugi:
  nginx      - obraz nginx:1.27-alpine, port 4001, sieci frontend i backend
  php        - budowany z php:8.3-fpm (dodalem rozszerzenie mysqli), siec backend
  mysql      - obraz mysql:8.4, siec backend, dane w wolumenie db_data
  phpmyadmin - obraz phpmyadmin:5.2, port 6001, siec backend

Dwie sieci: frontend i backend.

Nginx jest jako jedyny w sieci frontend, bo to on przyjmuje ruch z zewnatrz (port 4001)
i przekazuje zapytania php do php-fpm (php:9000) po sieci backend. Php i mysql siedza tylko
w backend, czyli nie sa widoczne z zewnatrz.

phpMyAdmin podlaczylem do sieci backend, phpMyAdmin musi sie polaczyc z serwerem mysql, a mysql jest tylko w backend, wiec phpMyAdmin
tez musi byc w backendzie. Do frontendu nie musi nalezec, bo do panelu wchodze z przegladarki
przez wystawiony port 6001 - a wystawienie portu (ports:) dziala niezaleznie od sieci compose.
Dodatkowo trzymanie phpMyAdmin poza frontend zostawia w warstwie publicznej tylko nginx.

Strona startowa to index.php. Laczy sie z baza przez mysqli i wypisuje czy polaczenie sie
udalo oraz wersje serwera MySQL - dzieki temu widac ze caly lancuch nginx -> php -> mysql dziala.
Ten sam index.php dziala w lab13 (dane z env) i w lab13D/14 (dane z secrets), bo ma funkcje
ktora czyta wartosc albo ze zmiennej NAZWA_FILE (sciezka do sekretu) albo ze zwyklej NAZWA.

3. Lab 13 - zadanie obowiazkowe (katalog lemp13)

Caly stack opisany w jednym pliku docker-compose.yml. Dane do bazy podane wprost jako
zmienne srodowiskowe. Obrazy maja jawnie podany tag (nie latest), php budowany z php:8.3-fpm.

Uruchomienie:

  docker compose up -d --build

docker compose ps:

  NAME              IMAGE               SERVICE      STATUS          PORTS
  lemp_mysql        mysql:8.4           mysql        Up 13 seconds   3306/tcp, 33060/tcp
  lemp_nginx        nginx:1.27-alpine   nginx        Up 13 seconds   0.0.0.0:4001->80/tcp
  lemp_php          lemp-php:8.3-fpm    php          Up 13 seconds   9000/tcp
  lemp_phpmyadmin   phpmyadmin:5.2      phpmyadmin   Up 13 seconds   0.0.0.0:6001->80/tcp

Strona startowa pod http://localhost:4001 - index.php pokazuje POLACZONO oraz wersje
MySQL 8.4.9, czyli php laczy sie z baza.

phpMyAdmin pod http://localhost:6001:

  curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost:6001
  HTTP 200

Loguje sie na root / rootpass (albo lempuser / lemppass).

Zalozenie testowej bazy (odpowiednik "Nowa baza" w phpMyAdmin, jako root):

  docker exec lemp_mysql mysql -uroot -prootpass -e "CREATE DATABASE IF NOT EXISTS testdb; SHOW DATABASES;"

  Database
  information_schema
  lempdb
  mysql
  performance_schema
  sys
  testdb

Sprawdzenie segmentacji sieci - php i mysql tylko w backend, nginx w obu:

  docker network inspect lemp13_backend --format '{{range .Containers}}{{.Name}} {{end}}'
  -> lemp_mysql lemp_nginx lemp_php lemp_phpmyadmin
  docker network inspect lemp13_frontend --format '{{range .Containers}}{{.Name}} {{end}}'
  -> lemp_nginx

Zatrzymanie:

  docker compose down       (kontenery i sieci)
  docker compose down -v    (dodatkowo wolumen z danymi mysql)


4. Lab 13D - secrets (katalog lemp13d)

To samo co lab 13, tylko dane wrazliwe przeniesione ze zmiennych srodowiskowych do
secrets dockera. Za wrazliwe uznalem: haslo root do mysql, haslo uzytkownika aplikacji
oraz nazwe tego uzytkownika.

Dzialanie jest dwuetapowe:
  1. na koncu pliku compose jest top-level secrets, ktory wiaze nazwe sekretu z plikiem
     z katalogu secrets/ (np. mysql_root_password -> ./secrets/mysql_root_password.txt)
  2. w usludze (mysql, php) dodaje atrybut secrets - wtedy sekret montuje sie w kontenerze
     pod /run/secrets/<nazwa>, a obraz czyta go przez zmienne z koncowka _FILE
     (np. MYSQL_ROOT_PASSWORD_FILE: /run/secrets/mysql_root_password)

phpMyAdmin nie dostaje sekretow, bo haslo podaje sie przy logowaniu w przegladarce.

Pliki sekretow:
  secrets/mysql_root_password.txt
  secrets/mysql_password.txt
  secrets/mysql_user.txt

Uruchomienie:

  docker compose up -d --build

Sekrety widoczne w kontenerze:

  docker exec lemp_mysql ls -l /run/secrets/
  -rw-r--r-- 1 root root 8 mysql_password
  -rw-r--r-- 1 root root 8 mysql_root_password
  -rw-r--r-- 1 root root 8 mysql_user

Potwierdzenie ze sekret jest podpiety jako bind mount:

  docker container inspect lemp_mysql -f '{{json .Mounts}}' | python3 -m json.tool
  ...
  "Type": "bind",
  "Source": ".../lemp13d/secrets/mysql_root_password.txt",
  "Destination": "/run/secrets/mysql_root_password",
  ...

Strona pod http://localhost:4001 pokazuje POLACZONO, wersje MySQL 8.4.9 oraz uzytkownika
lempuser - czyli nazwa uzytkownika zostala wczytana z sekretu i polaczenie sie udalo.

phpMyAdmin pod http://localhost:6001 zwraca HTTP 200.

Testowa baza (haslo root biore z sekretu):

  docker exec lemp_mysql sh -c 'mysql -uroot -p"$(cat /run/secrets/mysql_root_password)" -e "CREATE DATABASE IF NOT EXISTS testdb; SHOW DATABASES;"'
  -> w wyniku pojawia sie testdb

Zatrzymanie:

  docker compose down -v

Uwaga: w prawdziwym projekcie plikow z secrets/ nie wrzucalbym do repo (dodalbym do
.gitignore), tutaj sa tylko zeby bylo widac jak to dziala.


5. Lab 14 - podzial na base i override / merge (katalog lemp14)

Wzialem rozwiazanie z lab 13D i podzielilem jeden plik compose na dwa:

  docker-compose.base.yml - to co wspolne dla kazdego srodowiska: obrazy/build, sieci,
    wolumeny, sekrety, zaleznosci, wspolne zmienne. Plik bazowy nie wystawia zadnych portow.
  docker-compose.override.yml - rzeczy specyficzne dla srodowiska, czyli tutaj wystawione
    porty 4001 i 6001 (dla srodowiska lokalnego).

Chodzi o to, ze zeby odpalic to samo na innym srodowisku (np. CI), podmieniam tylko maly
plik override, a base zostaje bez zmian. Przy laczeniu plikow compose nadpisuje wartosci
pojedyncze, a listy (np. ports) skleja. Wszystkie sciezki musza byc wzgledem pliku bazowego.

Uruchomienie (laczenie dwoch plikow przez -f):

  docker compose -f docker-compose.base.yml -f docker-compose.override.yml up -d --build

Sprawdzenie ze merge dziala - w scalonej konfiguracji widac porty z override:

  docker compose -f docker-compose.base.yml -f docker-compose.override.yml config | grep -nE "nginx:|phpmyadmin:|ports|published"
  26:  nginx:
  36:    ports:
  39:        published: "4001"
  84:  phpmyadmin:
  97:    ports:
  100:        published: "6001"

Compose widzi oba pliki konfiguracyjne:

  docker compose ls
  NAME       STATUS         CONFIG FILES
  lemp14     running(4)     .../docker-compose.base.yml,.../docker-compose.override.yml

Reszta dziala tak jak wczesniej - strona pod http://localhost:4001 pokazuje POLACZONO i
MySQL 8.4.9, phpMyAdmin pod http://localhost:6001 zwraca HTTP 200, testowa baza zaklada sie
poprawnie.

Zatrzymanie:

  docker compose -f docker-compose.base.yml -f docker-compose.override.yml down -v

Gdyby plik bazowy nazwac docker-compose.yml a override zostawic jako docker-compose.override.yml
to compose laczy je sam i mozna pominac -f (samo docker compose up -d). Zostawilem nazwe
base.yml zeby bylo widac laczenie przez -f tak jak na wykladzie.


6. Podsumowanie

We wszystkich trzech wariantach stack LEMP dziala poprawnie: nginx serwuje strone startowa
php, php laczy sie z mysql, a phpMyAdmin pozwala sie zalogowac i zalozyc testowa baze.
Lab 13D pokazuje przeniesienie hasel do secrets, a lab 14 podzial konfiguracji na plik
bazowy i override.
