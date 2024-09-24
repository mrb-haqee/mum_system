reload:
	browser-sync start --proxy "localhost/mum_system" --files "**/*.css, **/*.js, **/*.php"

php:
	php test/test.php

BRANCH = fitur_purchasing

# Pull perubahan terbaru dari branch utama
pull:
	git pull origin $(BRANCH)

# Add semua perubahan, commit, dan push ke repository
commit:
	git add .
	git commit -m "$(m)"
	git push origin $(BRANCH)

# Check status dari repository Git
status:
	git status

# Buat branch baru dan pindah ke branch tersebut
new-branch:
	git checkout -b $(b)

# Ganti ke branch yang diinginkan
switch-branch:
	git checkout $(b)

# Fetch dan update branch lokal dengan branch remote
update:
	git fetch origin
	git merge origin/$(BRANCH)

# Rebase branch dengan branch utama
rebase:
	git fetch origin
	git rebase origin/$(BRANCH)

# Hapus branch lokal
delete-branch:
	git branch -d $(b)

# Hapus branch remote
delete-remote-branch:
	git push origin --delete $(b)

# Reset perubahan yang belum di-commit
reset:
	git reset --hard

# Tampilkan log commit terakhir
log:
	git log --oneline -n 10

# Buat tag versi baru
tag:
	git tag $(v)
	git push origin $(v)

# Cherry-pick commit tertentu ke branch saat ini
cherry-pick:
	git cherry-pick $(c)

# Lakukan stash perubahan saat ini
stash:
	git stash

# Ambil stash terbaru dan terapkan
stash-pop:
	git stash pop

# Menghapus semua branch lokal kecuali branch aktif
clean-branches:
	git branch | grep -v '$(BRANCH)' | xargs git branch -d

.PHONY: pull commit status new-branch switch-branch update rebase delete-branch delete-remote-branch reset log tag cherry-pick stash stash-pop clean-branches
