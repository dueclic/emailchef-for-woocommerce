/**
 * commit-and-tag-version updater for the WordPress.org readme:
 * updates the "Stable tag:" line.
 */

const STABLE_TAG_RE = /(Stable tag:\s*)([0-9.]+)/;

module.exports.readVersion = function (contents) {
    const match = contents.match(STABLE_TAG_RE);
    if (!match) {
        throw new Error('Stable tag not found in readme');
    }
    return match[2];
};

module.exports.writeVersion = function (contents, version) {
    return contents.replace(STABLE_TAG_RE, `$1${version}`);
};
