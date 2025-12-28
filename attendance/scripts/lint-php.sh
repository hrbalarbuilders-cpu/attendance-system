#!/usr/bin/env bash
PHP_CMD=$(command -v php || true)
if [ -z "$PHP_CMD" ]; then
  echo "php not found in PATH; install PHP or run this script with PHP available."
  exit 2
fi

FAIL=0
while IFS= read -r -d '' file; do
  echo "Linting $file"
  "$PHP_CMD" -l "$file" || FAIL=1
done < <(find "$(dirname "$0")/.." -name '*.php' -print0)

if [ "$FAIL" -ne 0 ]; then
  echo "Some PHP files failed lint." >&2
  exit 1
fi

echo "All PHP files lint OK"
exit 0
