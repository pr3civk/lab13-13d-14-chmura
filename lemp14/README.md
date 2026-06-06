Lab 14 - podzial compose na base i override (zadanie dodatkowe)

Wzialem rozwiazanie z lab 13D (to ze sekretami) i podzielilem jeden plik compose na dwa:

- docker-compose.base.yml - to co wspolne dla kazdego srodowiska: obrazy/build, sieci,
  wolumeny, sekrety, zaleznosci, wspolne zmienne. Plik bazowy nie wystawia zadnych portow.
- docker-compose.override.yml - rzeczy specyficzne dla srodowiska, czyli tutaj wystawione
  porty 4001 i 6001 (dla lokalnego/dev).

Sens jest taki, ze jak chce odpalic to samo na innym srodowisku (np. CI) to podmieniam
tylko maly plik override, a base zostaje bez zmian.

Compose przy laczeniu plikow nadpisuje wartosci pojedyncze a listy (np. ports) skleja.
Wszystkie sciezki musza byc wzgledem pliku bazowego (pierwszego z -f).

Uruchomienie (laczenie dwoch plikow):

  docker compose -f docker-compose.base.yml -f docker-compose.override.yml up -d --build

Sprawdzenie ze merge dziala - w scalonej konfiguracji sa porty z override:

  docker compose -f docker-compose.base.yml -f docker-compose.override.yml config | grep -nE "nginx:|phpmyadmin:|ports|published"
  26:  nginx:
  36:    ports:
  39:        published: "4001"
  84:  phpmyadmin:
  97:    ports:
  100:        published: "6001"

Compose widzi oba pliki (docker compose ls):

  NAME       STATUS         CONFIG FILES
  lemp14     running(4)     .../docker-compose.base.yml,.../docker-compose.override.yml

Reszta tak jak wczesniej - strona pod http://localhost:4001 pokazuje POLACZONO i MySQL 8.4.9,
phpMyAdmin pod http://localhost:6001 zwraca HTTP 200.

Testowa baza:

  docker exec lemp_mysql sh -c 'mysql -uroot -p"$(cat /run/secrets/mysql_root_password)" -e "CREATE DATABASE IF NOT EXISTS testdb; SHOW DATABASES;"'
  -> w wyniku pojawia sie testdb

Zatrzymanie:

  docker compose -f docker-compose.base.yml -f docker-compose.override.yml down -v

Dodatkowo - gdyby plik bazowy nazwac docker-compose.yml a override zostawic jako
docker-compose.override.yml to compose laczy je sam i mozna pominac -f (samo docker compose up -d).
Zostawilem nazwe base.yml zeby bylo widac laczenie przez -f jak na wykladzie.
