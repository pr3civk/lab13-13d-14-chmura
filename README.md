Lab 13 / 13D / 14 - Docker Compose, stack LEMP

Trzy zadania na tym samym stacku LEMP + phpMyAdmin (4 kontenery), dokladane po kolei.

lemp13  - lab 13, obowiazkowe - LEMP + phpMyAdmin w jednym docker-compose.yml
lemp13d - lab 13D, nieobowiazkowe - dane wrazliwe jako secrets
lemp14  - lab 14, dodatkowe - podzial na plik bazowy i override (merge)

Szczegoly i polecenia w README w kazdym katalogu.

Architektura (wszedzie taka sama):
- nginx (nginx:1.27-alpine) - jedyny w sieci frontend, port 4001, przekazuje php do php-fpm
- php (php:8.3-fpm + mysqli) - tylko backend
- mysql (mysql:8.4) - tylko backend, dane w wolumenie db_data
- phpmyadmin (phpmyadmin:5.2) - tylko backend, port 6001 (musi widziec mysql)

Szybki start:

  cd lemp13  && docker compose up -d --build
  cd lemp13d && docker compose up -d --build
  cd lemp14  && docker compose -f docker-compose.base.yml -f docker-compose.override.yml up -d --build

Po odpaleniu: aplikacja php pod http://localhost:4001, phpMyAdmin pod http://localhost:6001.

Katalogi compose13 i compose13d to przyklady z wykladu, nie sa czescia rozwiazania.
