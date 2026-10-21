.PHONY: check clean test start
check:
	./scripts/check
clean:
	./scripts/clean
test:
	backend/vendor/bin/phpunit -c backend/phpunit.xml
	npm --prefix frontend test -- --run
start:
	docker compose up --build
