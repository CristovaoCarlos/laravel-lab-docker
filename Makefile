.PHONY: up down logs shell test fresh tinker queue-logs tools

up:            ## sobe tudo (constrói a imagem se preciso)
	docker compose up -d --build

down:          ## para os containers (mantém banco e código)
	docker compose down

logs:          ## acompanha os logs do app
	docker compose logs -f app

queue-logs:    ## acompanha o worker da fila
	docker compose logs -f queue

shell:         ## abre um terminal dentro do container do app
	docker compose exec app bash

test:          ## roda a suíte PHPUnit (usa SQLite em memória, não toca no MySQL)
	docker compose exec app php artisan test

fresh:         ## recria o banco e roda os seeders
	docker compose exec app php artisan migrate:fresh --seed

tinker:        ## console interativo do Laravel
	docker compose exec app php artisan tinker

tools:         ## sobe também o Adminer (interface web do banco)
	docker compose --profile tools up -d
