import { lstatSync } from 'node:fs';

/**
 * Prettier refuses symlinks passed explicitly, so they are filtered out before it runs.
 */
const notSymlink = (file) => !lstatSync(file).isSymbolicLink();

export default {
    '**/.env*': 'scripts/forbid-env-files.sh',
    '**/*.php': ['vendor/bin/duster fix'],
    '**/*': (files) => {
        const formattable = files.filter(notSymlink);

        return [
            ...(formattable.length
                ? [`prettier --write --ignore-unknown ${formattable.map((file) => JSON.stringify(file)).join(' ')}`]
                : []),
            'scripts/gitleaks-staged.sh',
        ];
    },
};
