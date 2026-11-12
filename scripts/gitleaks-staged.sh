#!/usr/bin/env sh
# lint-staged runs this with the staged paths as arguments; gitleaks scans the
# whole index itself, so the arguments are ignored. Fail the commit if any
# staged change contains something that looks like a credential.

if ! command -v gitleaks >/dev/null 2>&1; then
    printf 'gitleaks is not installed, so staged changes cannot be scanned for secrets.\n' >&2
    printf 'Install it with: brew install gitleaks\n' >&2
    exit 1
fi

exec gitleaks git --pre-commit --staged --no-banner --verbose --redact --config .gitleaks.toml
