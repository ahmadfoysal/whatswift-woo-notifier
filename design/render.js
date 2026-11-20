const { chromium } = require('playwright');
const D = process.argv[2];
(async () => {
  const b = await chromium.launch();
  const shot = async (file, w, h, scale, out, transparent) => {
    const p = await (await b.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: scale })).newPage();
    await p.goto('file://' + D + '/' + file); await p.evaluate(() => document.fonts.ready);
    await p.screenshot({ path: D + '/' + out, omitBackground: !!transparent });
  };
  await shot('banner.html', 1544, 500, 1, 'banner-1544x500.png');
  await shot('banner.html', 1544, 500, 0.5, 'banner-772x250.png');
  await shot('icon.html', 256, 256, 1, 'icon-256x256.png', true);
  await shot('icon.html', 256, 256, 0.5, 'icon-128x128.png', true);
  await b.close();
})();
