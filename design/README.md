# Listing artwork

Source for the WordPress.org banner and icon in `.wordpress-org/`.

Render with Playwright (Inter font installed):

```bash
node design/render.js "$PWD/design" && cp design/*.png .wordpress-org/
```

`render.js` writes `banner-1544x500.png`, `banner-772x250.png`, `icon-256x256.png` and `icon-128x128.png`.
