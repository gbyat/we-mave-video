# we Mave Video

Self-hosted [mave.io](https://www.mave.io/docs/player/) video player for WordPress.

**Stable tag:** 1.0.1

## Features

- Self-hosted `@maveio/components` player bundle
- Shortcode, Gutenberg block, and raw markup support
- Admin settings for player defaults and component updates
- Optional GitHub-based plugin self-updates

## Development

```bash
composer install
npm install
npm run wp-cli:install
composer run lint
npm run i18n
```

`npm run i18n` erzeugt `.pot`, `de_DE.po`, `.mo` und JSON. Nur Deutsch nachziehen: `npm run po:de && npm run mo && npm run json`.

## Releases

```bash
npm run release:patch
# or: release:minor / release:major
```

Pushing the annotated tag triggers GitHub Actions to build `we-mave-video.zip` and publish a release.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
