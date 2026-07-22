#!/usr/bin/env bash
#
# Build/distribute the DataMetric plugin after edits:
#   1. Minify assets
#   2. Sync the plugin folder into the Local (by Flywheel) site, if present  → live test site
#   3. Package the RELEASE-READY plugin (dev files excluded) and mirror the
#      zip to BOTH ~/Downloads and ~/Desktop                                → manual upload
#
# The zip contains ONLY the files that should ship (no tests, PHPUnit config,
# Composer/npm manifests, vendor, or node_modules). NOTE: we never push to SVN
# from here — releases are uploaded manually by the author.
#
# Safe to run repeatedly: it skips everything when no plugin SOURCE file has
# changed since the last delivered copy. Intended for a Claude Code Stop hook.

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT" || exit 0

SLUG="datametric-analytics-heatmaps"
ZIP="$REPO_ROOT/$SLUG.zip"
DESTS=( "$HOME/Downloads/$SLUG.zip" "$HOME/Desktop/$SLUG.zip" )

# Local (by Flywheel) plugin destination. Override with DATAMETRIC_LOCAL_DEST if your
# site path differs. Sync is skipped automatically when the parent plugins dir is absent.
LOCAL_DEST="${DATAMETRIC_LOCAL_DEST:-$HOME/Local Sites/test/app/public/wp-content/plugins/$SLUG}"

# Files that must never ship (dev-only). Directory paths are ANCHORED with a leading
# slash so they match only at the plugin root — otherwise '/vendor/' would also drop
# Admin/js/vendor/ (ApexCharts), which the dashboard needs.
EXCLUDES=(
	--exclude '/tests/'
	--exclude '/phpunit.xml'
	--exclude '/vendor/'
	--exclude '/composer.json'
	--exclude '/composer.lock'
	--exclude '/package.json'
	--exclude '/package-lock.json'
	--exclude '.phpunit.result.cache'
	--exclude '.DS_Store'
	--exclude 'node_modules/'
)

# Skip only if EVERY delivered copy already exists and is newer than every plugin
# SOURCE file (minified outputs are derived, so excluded from the freshness check).
NEED_BUILD=0
for dest in "${DESTS[@]}"; do
	[ -f "$dest" ] || NEED_BUILD=1
done
if [ "$NEED_BUILD" = 0 ]; then
	NEWER="$(find "$SLUG" -type f \
		\( -name '*.php' -o -name '*.css' -o -name '*.js' -o -name '*.txt' -o -name '*.json' \) \
		! -name '*.min.*' -newer "${DESTS[0]}" 2>/dev/null | head -n 1)"
	[ -z "$NEWER" ] && exit 0
fi

DID_LOCAL=0
DID_ZIP=0

# Rebuild minified assets (best-effort).
python3 bin/minify.py >/dev/null 2>&1

# 1. Sync into the Local site (only if the plugins directory exists on this machine).
if [ -d "$(dirname "$LOCAL_DEST")" ]; then
	mkdir -p "$LOCAL_DEST"
	if rsync -a --delete "${EXCLUDES[@]}" "$SLUG/" "$LOCAL_DEST/" 2>/dev/null; then
		DID_LOCAL=1
	fi
fi

# 2. Package the release-ready zip (dev files excluded).
rm -f "$SLUG/.phpunit.result.cache" "$ZIP"
if zip -rq "$ZIP" "$SLUG" \
	-x "$SLUG/tests/*" \
	-x "$SLUG/phpunit.xml" \
	-x "$SLUG/vendor/*" \
	-x "$SLUG/composer.json" \
	-x "$SLUG/composer.lock" \
	-x "$SLUG/package.json" \
	-x "$SLUG/package-lock.json" \
	-x "$SLUG/.phpunit.result.cache" \
	-x "*/.DS_Store" \
	-x "*/node_modules/*"; then
	xattr -c "$ZIP" 2>/dev/null

	# Mirror to every destination ATOMICALLY (temp file + mv) so Finder never shows
	# a half-written file.
	MIRRORED=0
	for dest in "${DESTS[@]}"; do
		[ -d "$(dirname "$dest")" ] || continue
		TMP="$dest.tmp.$$"
		if cp "$ZIP" "$TMP" 2>/dev/null; then
			xattr -c "$TMP" 2>/dev/null
			chmod 644 "$TMP" 2>/dev/null
			mv -f "$TMP" "$dest" 2>/dev/null && MIRRORED=$((MIRRORED + 1)) || rm -f "$TMP"
		fi
	done
	[ "$MIRRORED" -gt 0 ] && DID_ZIP=1
fi

# Report what happened.
if [ "$DID_LOCAL" = 1 ] && [ "$DID_ZIP" = 1 ]; then
	MSG="DataMetric: Local sitesine senkronlandi + zip ~/Downloads ve ~/Desktop klasorlerine kopyalandi."
elif [ "$DID_LOCAL" = 1 ]; then
	MSG="DataMetric: Local sitesine senkronlandi (zip paketlenemedi)."
elif [ "$DID_ZIP" = 1 ]; then
	MSG="DataMetric: zip ~/Downloads ve ~/Desktop klasorlerine kopyalandi (Local sitesi bulunamadi)."
else
	exit 0
fi

printf '{"systemMessage": "%s"}\n' "$MSG"
