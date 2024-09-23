reload:
	browser-sync start --proxy "localhost/skansapura" --files "**/*.css, **/*.js, **/*.php"

php:
	php test/test.php

dummy:
	php test/buat_dummy_data.php

data:
	curl -o output.txt http://localhost/skansapura/buat_dummy_data.php



mrb:
	git checkout mrb
main:
	git checkout main

commit:
	git add . && git commit -m "$(p)"