import { chromium } from 'playwright';
const b = await chromium.launch();
// Tall viewport puts the strip (top ~2400px) on screen without scrolling,
// which sidesteps the hero's scroll hijack entirely.
const p = await (await b.newContext({viewport:{width:1440,height:3200}})).newPage();
await p.goto('http://127.0.0.1:8001/',{waitUntil:'domcontentloaded'});
await p.waitForTimeout(4000);
const box = await p.evaluate(()=>{const e=document.querySelector('.hp-strip-wrap');const r=e.getBoundingClientRect();
  return {x:Math.max(0,r.left-20), y:Math.max(0,r.top-20), width:Math.min(1440,r.width+40), height:r.height+40};});
console.log('box', JSON.stringify(box));
await p.screenshot({path:'strip.png', clip: box, fullPage: true});
await b.close();
