module.exports = {
    // existing tags are plain "5.5.2", no "v" prefix
    'tag-prefix': '',
    releaseCommitMessageFormat: 'chore(release): bump version to {{currentTag}}',
    // source of truth for the current version
    packageFiles: [
        {filename: 'woocommerce-emailchef.php', updater: 'scripts/version-updaters/php-plugin.js'},
    ],
    bumpFiles: [
        {filename: 'woocommerce-emailchef.php', updater: 'scripts/version-updaters/php-plugin.js'},
        {filename: '.wordpress-org/readme/README.md', updater: 'scripts/version-updaters/wp-readme.js'},
        {filename: 'package.json', type: 'json'},
    ],
    scripts: {
        // Regenerate the WP.org readme "== Changelog ==" entry from the
        // conventional commits since the last tag (reads the freshly bumped
        // version from woocommerce-emailchef.php), then stage it into the
        // release commit.
        postbump: './scripts/patch-version.sh',
        precommit: 'git add .wordpress-org/readme/README.md',
    },
};
