#!/usr/bin/env python3
"""Fetch the self-hosted title fonts and regenerate resources/css/fonts.css.

Run from the project root:  python3 scripts/fetch-title-fonts.py

FAMILIES must match the picker in tenant/admin/settings/index.blade.php -- change the two
together, or the picker offers a font with no face behind it. SelfHostedFontsTest fails if
they drift, if a face points at a missing file, or if a layout starts asking a third party
for a font again.

Latin subset only: this is an English-language product and the other subsets are most of
the bytes. Weight ranges are tried before individual weights, because Google answers a
range with a single variable file covering every weight in it -- one request instead of
four, and it can serve weights the layouts did not think to ask for.
"""
import hashlib
import os
import re
import urllib.parse
import urllib.request

FAMILIES = [
    'Poppins', 'Montserrat', 'Raleway', 'Inter', 'Nunito', 'DM Sans', 'Urbanist', 'Outfit',
    'Lato', 'Open Sans', 'Roboto', 'Oswald', 'Playfair Display', 'Merriweather', 'Lora',
    'Cormorant Garamond', 'EB Garamond', 'Libre Baskerville', 'Cinzel', 'Bebas Neue',
    'Anton', 'Abril Fatface', 'Righteous',
]

# Ranges first, then fixed weights, then whatever the family has. Google answers a request
# for weights a family does not ship with a 400, so this walks down until one works.
VARIANTS = [':wght@100..900', ':wght@400..900', ':wght@400..700', ':wght@400;600;700;800',
            ':wght@400;700', '']

UA = ('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
      'Chrome/140.0.0.0 Safari/537.36')
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FONT_DIR = os.path.join(ROOT, 'public', 'fonts')
CSS = os.path.join(ROOT, 'resources', 'css', 'fonts.css')


def fetch(url):
    return urllib.request.urlopen(
        urllib.request.Request(url, headers={'User-Agent': UA}), timeout=30).read()


def main():
    os.makedirs(FONT_DIR, exist_ok=True)
    faces, written, failures = [], set(), []

    for family in FAMILIES:
        quoted = urllib.parse.quote_plus(family)
        css = None
        for variant in VARIANTS:
            try:
                css = fetch(f'https://fonts.googleapis.com/css2?family={quoted}{variant}'
                            '&display=swap').decode()
                break
            except Exception:
                continue
        if not css:
            failures.append(family)
            continue

        for block in re.findall(r'/\* latin \*/\s*(@font-face\s*\{[^}]*\})', css):
            found = re.search(r'url\((https://fonts\.gstatic\.com/[^)]+)\)', block)
            if not found:
                continue
            source = found.group(1)
            # Named for the source, so a regenerated file is a new URL and the year-long
            # immutable cache header stays true.
            name = (family.lower().replace(' ', '-') + '-'
                    + hashlib.sha1(source.encode()).hexdigest()[:8] + '.woff2')
            path = os.path.join(FONT_DIR, name)
            if not os.path.isfile(path):
                data = fetch(source)
                if data[:4] != b'wOF2':
                    raise SystemExit(f'{family}: {source} is not a woff2')
                with open(path, 'wb') as handle:
                    handle.write(data)
            written.add(name)
            faces.append(re.sub(r'url\(https://[^)]+\)', f"url('/fonts/{name}')", block).strip())

    if failures:
        raise SystemExit('could not fetch: ' + ', '.join(failures))

    # Anything left behind is from an older run and nothing references it any more.
    stale = [f for f in os.listdir(FONT_DIR) if f.endswith('.woff2') and f not in written]
    for name in stale:
        os.remove(os.path.join(FONT_DIR, name))

    with open(CSS, 'w') as handle:
        handle.write(
            '/* Self-hosted title fonts -- GENERATED, do not hand-edit.\n'
            '   Regenerate with: python3 scripts/fetch-title-fonts.py\n'
            '   That script holds the family list and explains the rest. */\n\n'
            + '\n\n'.join(faces) + '\n')

    variable = sum(1 for f in faces if re.search(r'font-weight:\s*\d+\s+\d+', f))
    total = sum(os.path.getsize(os.path.join(FONT_DIR, n)) for n in written)
    print(f'{len(FAMILIES)} families -> {len(faces)} faces, {len(written)} files, '
          f'{total // 1024}KB ({variable} variable)')
    if stale:
        print(f'removed {len(stale)} stale file(s)')


if __name__ == '__main__':
    main()
