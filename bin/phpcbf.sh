#!/bin/bash
_tabtospace() {
    echo "# tab-to-space: $1";
    sed -i -e 's/\t/    /g' -e '1s/^\xEF\xBB\xBF//' $1;
}

find ./src/ -type f -name "*.php" |while read f; do
    _tabtospace "$f";
done
./vendor/bin/phpcbf --tab-width=4 ./src/
exit 0;

