git -C ../ reset --hard
git -C ../ pull
git -C ../ stash apply
rm ../dynamic/cache/*.php