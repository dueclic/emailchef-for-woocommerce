/**
 * commit-and-tag-version updater for woocommerce-emailchef.php:
 * keeps the plugin header "Version:" and the WC_EMAILCHEF_VERSION
 * constant in sync.
 */

const HEADER_RE = /(\* Version:\s+)([0-9.]+)/;
const CONSTANT_RE = /(define\(\s*['"]WC_EMAILCHEF_VERSION['"],\s*['"])([0-9.]+)(['"]\s*\))/;

module.exports.readVersion = function (contents) {
    const match = contents.match(HEADER_RE);
    if (!match) {
        throw new Error('Version header not found in woocommerce-emailchef.php');
    }
    return match[2];
};

module.exports.writeVersion = function (contents, version) {
    if (!CONSTANT_RE.test(contents)) {
        throw new Error('WC_EMAILCHEF_VERSION constant not found in woocommerce-emailchef.php');
    }
    return contents
        .replace(HEADER_RE, `$1${version}`)
        .replace(CONSTANT_RE, `$1${version}$3`);
};
