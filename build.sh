#!/usr/bin/env bash
# Sestavení distribučního ZIPu + SHA-256 pro GitHub Release.
# Použití: ./build.sh            → dist/bombora-infinite-scroll.zip + .sha256
# Vyžaduje: zip, sha256sum, php.
set -euo pipefail

cd "$(dirname "$0")"
SLUG="bombora-infinite-scroll"
VERSION="$(grep -m1 -E '^\s*\*\s*Version:' ${SLUG}.php | sed -E 's/.*Version:\s*//; s/\s+$//')"
CONST="$(grep -m1 "define( 'BOMBORA_IS_VERSION'" ${SLUG}.php | sed -E "s/.*'BOMBORA_IS_VERSION', '([^']+)'.*/\1/")"

if [[ "$VERSION" != "$CONST" ]]; then
	echo "Chyba: hlavička Version ($VERSION) ≠ BOMBORA_IS_VERSION ($CONST)" >&2
	exit 1
fi
if ! grep -q "^## \[$VERSION\]" CHANGELOG.md; then
	echo "Chyba: CHANGELOG.md neobsahuje sekci [$VERSION]" >&2
	exit 1
fi

# Kontrola syntaxe PHP.
find . -name '*.php' -not -path './dist/*' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l >/dev/null

rm -rf dist build
mkdir -p "build/${SLUG}" dist
# Kopie bez souborů z .distignore (bez závislosti na rsync).
tar --exclude-from=.distignore --exclude=./build --exclude=./dist -cf - . | tar -xf - -C "build/${SLUG}"
( cd build && zip -qr9X "../dist/${SLUG}.zip" "${SLUG}" )
( cd dist && sha256sum "${SLUG}.zip" > "${SLUG}.zip.sha256" )
rm -rf build

echo "Hotovo: dist/${SLUG}.zip (v${VERSION})"
cat "dist/${SLUG}.zip.sha256"
