/*
 | Chart.js, bundled.
 |
 | It used to be a hand-vendored public/js/chart.min.js — 208 KB with no content hash in the
 | name, so it could never be cached hard and every release risked serving a stale copy. It was
 | also already a package.json dependency that nothing imported, which meant the version in the
 | lockfile and the version actually being served had no relationship to each other.
 |
 | Exposed on window because the dashboard's chart code is still inline and calls `new Chart()`
 | as a global. That inline code moves into a bundle in a later slice; this entry is what lets
 | the two steps happen independently.
 |
 | Imported from 'chart.js/auto' so the controllers, scales and elements register themselves,
 | which is what the hand-vendored UMD build did.
 */
import Chart from 'chart.js/auto';

window.Chart = Chart;
