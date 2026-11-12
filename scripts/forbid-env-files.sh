#!/usr/bin/env sh
# lint-staged passes the staged paths as arguments. Fail the commit if any of
# them is a .env file other than .env.example — that is how the production
# .env reached git (#3599).

forbidden=""

for path in "$@"; do
    case "$(basename "$path")" in
        .env.example) ;;
        .env|.env.*) forbidden="$forbidden  $path\n" ;;
    esac
done

if [ -z "$forbidden" ]; then
    exit 0
fi

printf 'Refusing to commit environment files:\n%b' "$forbidden" >&2
printf 'Only .env.example may be committed. Unstage them with: git restore --staged <file>\n' >&2
exit 1
