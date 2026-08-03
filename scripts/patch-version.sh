#!/usr/bin/env bash
# Patches Stable tag and "== Changelog ==" in the WP.org readme from the
# Version: header in woocommerce-emailchef.php, and optionally creates a git tag.
#
# Normally invoked by commit-and-tag-version (postbump lifecycle script in
# .versionrc.js) during `pnpm run release`, after the version has been bumped.
# Can also be run standalone.
#
# Usage:
#   ./scripts/patch-version.sh [OPTIONS]
#
# Options:
#   --tested-up <version>   Update "Tested up to" in README
#   --changelog <message>   Override auto-generated changelog with a fixed message
#   --tag                   Create and push a git tag after patching
#
# Conventional commit types recognised:
#   feat      → Features
#   fix       → Bug Fixes
#   security  → Security
#   perf      → Performance
#   refactor  → Refactoring
#   docs      → Documentation
#   chore / build / ci / style / test → Other Changes
#
# If no conventional commits are found since the last tag, all commit subjects
# are listed as generic bullet points.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
PLUGIN_FILE="$ROOT_DIR/woocommerce-emailchef.php"
README_FILE="$ROOT_DIR/.wordpress-org/readme/README.md"

# ── Parse arguments ───────────────────────────────────────────────────────────
TESTED_UP=""
CHANGELOG_OVERRIDE=""
CREATE_TAG=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        --tested-up)
            TESTED_UP="$2"; shift 2 ;;
        --changelog)
            CHANGELOG_OVERRIDE="$2"; shift 2 ;;
        --tag)
            CREATE_TAG=true; shift ;;
        *)
            echo "Unknown argument: $1" >&2
            echo "Usage: $0 [--tested-up <wp_version>] [--changelog <message>] [--tag]" >&2
            exit 1 ;;
    esac
done

# ── Read plugin version ───────────────────────────────────────────────────────
PLUGIN_VERSION=$(grep -E '^\s*\*\s*Version:' "$PLUGIN_FILE" \
    | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')

if [[ -z "$PLUGIN_VERSION" ]]; then
    echo "ERROR: could not read Version from $PLUGIN_FILE" >&2; exit 1
fi
echo "Plugin version : $PLUGIN_VERSION"

# ── Patch Stable tag ─────────────────────────────────────────────────────────
CURRENT_STABLE=$(grep -E '^Stable tag:' "$README_FILE" \
    | sed 's/Stable tag:[[:space:]]*//' | tr -d '[:space:]')

if [[ "$CURRENT_STABLE" == "$PLUGIN_VERSION" ]]; then
    echo "Stable tag     : already $PLUGIN_VERSION (no change)"
else
    sed -i "s/^Stable tag:.*/Stable tag: $PLUGIN_VERSION/" "$README_FILE"
    echo "Stable tag     : $CURRENT_STABLE → $PLUGIN_VERSION"
fi

# ── Patch Tested up to ────────────────────────────────────────────────────────
if [[ -n "$TESTED_UP" ]]; then
    CURRENT_TESTED=$(grep -E '^Tested up to:' "$README_FILE" \
        | sed 's/Tested up to:[[:space:]]*//' | tr -d '[:space:]')
    if [[ "$CURRENT_TESTED" == "$TESTED_UP" ]]; then
        echo "Tested up to   : already $TESTED_UP (no change)"
    else
        sed -i "s/^Tested up to:.*/Tested up to: $TESTED_UP/" "$README_FILE"
        echo "Tested up to   : $CURRENT_TESTED → $TESTED_UP"
    fi
fi

# ── Build changelog entry ─────────────────────────────────────────────────────
build_changelog_from_commits() {
    # Find the previous tag (skip the current version tag if it already exists)
    LAST_TAG=$(git tag --sort=-version:refname \
        | grep -v "^${PLUGIN_VERSION}$" \
        | head -1 || true)
    if [[ -n "$LAST_TAG" ]]; then
        RANGE="$LAST_TAG..HEAD"
        echo "               (commits since tag $LAST_TAG)" >&2
    else
        RANGE="HEAD"
        echo "               (no previous tag found, taking all commits)" >&2
    fi

    # Collect commit subjects (merge commits are noise in a changelog)
    mapfile -t COMMITS < <(git log "$RANGE" --no-merges --pretty=format:"%s" 2>/dev/null || true)

    if [[ ${#COMMITS[@]} -eq 0 ]]; then
        echo "* Version $PLUGIN_VERSION"
        return
    fi

    # Buckets
    declare -a FEATS FIXES SECURITY PERFS REFACTORS DOCS OTHERS

    # Helper: extract description after "type(optional-scope)!: "
    extract_desc() {
        echo "$1" | sed -E 's/^[a-z]+(\([^)]*\))?!?:[[:space:]]*//'
    }

    for msg in "${COMMITS[@]}"; do
        type=$(echo "$msg" | grep -oE '^[a-z]+' || true)
        case "$type" in
            feat)                              FEATS+=("$(extract_desc "$msg")") ;;
            fix|bugfix)                        FIXES+=("$(extract_desc "$msg")") ;;
            security)                          SECURITY+=("$(extract_desc "$msg")") ;;
            perf)                              PERFS+=("$(extract_desc "$msg")") ;;
            refactor)                          REFACTORS+=("$(extract_desc "$msg")") ;;
            docs)                              DOCS+=("$(extract_desc "$msg")") ;;
            chore|build|ci|style|test|revert)  OTHERS+=("$(extract_desc "$msg")") ;;
            *)
                # Non-conventional: check if it has "type:" prefix at all
                if echo "$msg" | grep -qE '^[a-z]+(\([^)]*\))?!?:'; then
                    OTHERS+=("$(extract_desc "$msg")")
                else
                    OTHERS+=("$msg")
                fi ;;
        esac
    done

    local output=""
    for item in "${SECURITY[@]:-}";  do [[ -n "$item" ]] && output+="* Security: $item"$'\n'; done
    for item in "${FEATS[@]:-}";     do [[ -n "$item" ]] && output+="* Feature: $item"$'\n'; done
    for item in "${FIXES[@]:-}";     do [[ -n "$item" ]] && output+="* Fix: $item"$'\n'; done
    for item in "${PERFS[@]:-}";     do [[ -n "$item" ]] && output+="* Performance: $item"$'\n'; done
    for item in "${REFACTORS[@]:-}"; do [[ -n "$item" ]] && output+="* Refactor: $item"$'\n'; done
    for item in "${DOCS[@]:-}";      do [[ -n "$item" ]] && output+="* Docs: $item"$'\n'; done
    for item in "${OTHERS[@]:-}";    do [[ -n "$item" ]] && output+="* $item"$'\n'; done

    if [[ -z "$output" ]]; then
        echo "* Version $PLUGIN_VERSION"
    else
        echo -n "$output"
    fi
}

# ── Add/update changelog entry ────────────────────────────────────────────────
if [[ -n "$CHANGELOG_OVERRIDE" ]]; then
    ENTRY_BODY="$CHANGELOG_OVERRIDE"
else
    ENTRY_BODY=$(build_changelog_from_commits)
fi

PY_SCRIPT=$(cat <<'PYEOF'
import sys, re

readme_path = sys.argv[1]
version     = sys.argv[2]
body        = sys.argv[3]

with open(readme_path, 'r') as f:
    content = f.read()

marker = '== Changelog =='
if marker not in content:
    print("ERROR: '== Changelog ==' not found in README", file=sys.stderr)
    sys.exit(1)

new_block = f'\n= {version} =\n{body}'

# Remove existing entry for this version (header + its lines up to next header or EOF)
pattern = rf'\n= {re.escape(version)} =\n.*?(?=\n= |\Z)'
existed = bool(re.search(pattern, content, flags=re.DOTALL))
content = re.sub(pattern, '', content, flags=re.DOTALL)

# Insert fresh block right after "== Changelog =="
insert_at = content.find(marker) + len(marker)
content = content[:insert_at] + new_block + content[insert_at:]

with open(readme_path, 'w') as f:
    f.write(content)

print('update' if existed else 'insert')
PYEOF
)

PYEOF_RESULT=$(python3 -c "$PY_SCRIPT" "$README_FILE" "$PLUGIN_VERSION" "$ENTRY_BODY")

if [[ "$PYEOF_RESULT" == "update" ]]; then
    echo "Changelog      : updated entry for $PLUGIN_VERSION"
else
    echo "Changelog      : added entry for $PLUGIN_VERSION"
fi
echo "$ENTRY_BODY" | sed 's/^/               > /'

# ── Commit + tag ──────────────────────────────────────────────────────────────
if $CREATE_TAG; then
    if git tag | grep -qx "$PLUGIN_VERSION"; then
        echo "Git tag        : $PLUGIN_VERSION already exists (no change)"
    else
        # Stage only the files this script touches
        git add "$PLUGIN_FILE" "$README_FILE"

        # Commit if there is anything staged
        if ! git diff --cached --quiet; then
            git commit -m "chore(release): bump version to $PLUGIN_VERSION"
            echo "Git commit     : chore(release): bump version to $PLUGIN_VERSION"
        else
            echo "Git commit     : nothing to commit (working tree clean)"
        fi

        # Tag the current HEAD (the commit above, or the existing one)
        git tag "$PLUGIN_VERSION"
        echo "Git tag        : created $PLUGIN_VERSION"
        echo "               > Push with: git push origin HEAD $PLUGIN_VERSION"
    fi
fi

echo "Done."
