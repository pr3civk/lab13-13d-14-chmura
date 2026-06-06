Lab 13D - LEMP z secrets (zadanie nieobowiazkowe)

To samo co w lab 13, tylko dane wrazliwe przeniosłem ze zmiennych srodowiskowych do
secrets dockera. Wrazliwe sa: haslo root do mysql, haslo uzytkownika aplikacji i nazwa
tego uzytkownika.

Jak to dziala:
1. na koncu pliku compose jest top-level secrets, ktory wiaze nazwe sekretu z plikiem
   z katalogu secrets/
2. w usludze (mysql, php) dodaje atrybut secrets, przez co sekret montuje sie w
   /run/secrets/<nazwa>, a obraz czyta go przez zmienne z koncowka _FILE

mysql i moj index.php obsluguja wariant _FILE, wiec ten sam kod chodzi i z env (lab13)
i z secrets (lab13d).

phpMyAdmin nie dostaje sekretow, bo haslo podaje sie przy logowaniu w przegladarce,
nie trzeba go nigdzie wpisywac w configu.

Pliki sekretow:
  secrets/mysql_root_password.txt
  secrets/mysql_password.txt
  secrets/mysql_user.txt

Uruchomienie:

  docker compose up -d --build

Sprawdzenie ze sekrety sa w kontenerze:

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

Strona dziala pod http://localhost:4001, index.php pokazuje POLACZONO, wersje MySQL 8.4.9
oraz uzytkownika lempuser (ktory zostal wczytany z sekretu).

phpMyAdmin pod http://localhost:6001 zwraca HTTP 200.

Testowa baza (haslo root pobieram z sekretu):

  docker exec lemp_mysql sh -c 'mysql -uroot -p"$(cat /run/secrets/mysql_root_password)" -e "CREATE DATABASE IF NOT EXISTS testdb; SHOW DATABASES;"'

  Database
  information_schema
  lempdb
  mysql
  performance_schema
  sys
  testdb

Zatrzymanie:

  docker compose down -v

Na koniec uwaga - w prawdziwym projekcie pliki z secrets/ nie powinny trafic do repo
(dodalbym je do .gitignore), tutaj sa tylko po to zeby bylo widac jak to dziala.
