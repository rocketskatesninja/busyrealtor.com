import os, re, urllib.request, urllib.parse, hashlib, sys

FAMILIES = ['Poppins','Montserrat','Raleway','Inter','Nunito','DM Sans','Urbanist','Outfit',
            'Lato','Open Sans','Roboto','Oswald','Playfair Display','Merriweather','Lora',
            'Cormorant Garamond','EB Garamond','Libre Baskerville','Cinzel','Bebas Neue',
            'Anton','Abril Fatface','Righteous']
UA = ('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
      'Chrome/140.0.0.0 Safari/537.36')
OUT = '/var/www/busyrealtor.com/public/fonts/'
os.makedirs(OUT, exist_ok=True)

def get(url):
    r = urllib.request.Request(url, headers={'User-Agent': UA})
    return urllib.request.urlopen(r, timeout=30).read()

faces, failures, total = [], [], 0
for fam in FAMILIES:
    q = urllib.parse.quote_plus(fam)
    css = None
    # The layouts ask for these four weights; display faces only ship one, and asking for
    # weights they do not have is a 400, so fall back to whatever the family does offer.
    for suffix in [':wght@400;600;700;800', ':wght@400;700', '']:
        try:
            css = get(f'https://fonts.googleapis.com/css2?family={q}{suffix}&display=swap').decode()
            break
        except Exception:
            continue
    if not css:
        failures.append(fam); continue

    # Only the latin subset: this is an English-language product and the other subsets are
    # most of the bytes.
    for block in re.findall(r'/\* latin \*/\s*(@font-face\s*\{[^}]*\})', css):
        url = re.search(r'url\((https://fonts\.gstatic\.com/[^)]+)\)', block)
        if not url:
            continue
        src = url.group(1)
        name = fam.lower().replace(' ', '-') + '-' + hashlib.sha1(src.encode()).hexdigest()[:8] + '.woff2'
        path = OUT + name
        if not os.path.isfile(path):
            data = get(src)
            assert data[:4] == b'wOF2', f'{fam}: not a woff2'
            open(path, 'wb').write(data)
        total += os.path.getsize(path)
        faces.append(re.sub(r'url\(https://[^)]+\)', f"url('/fonts/{name}')", block).strip())

open('/var/www/busyrealtor.com/resources/css/fonts.css', 'w').write(
    "/* Self-hosted title fonts. Generated -- do not hand-edit; see busyrealtor-shots notes\n"
    "   and the FAMILIES list in the fetch script. Latin subset only, which is what an\n"
    "   English-language product needs and is most of the saving. Google serves several of\n"
    "   these as variable fonts, where one file covers every weight the layouts ask for. */\n\n"
    + '\n\n'.join(faces) + '\n')

variable = sum(1 for f in faces if re.search(r'font-weight:\s*\d+\s+\d+', f))
print(f'families: {len(FAMILIES) - len(failures)}/{len(FAMILIES)}   faces: {len(faces)}   '
      f'variable: {variable}   on disk: {total // 1024}KB')
if failures:
    print('FAILED:', ', '.join(failures))
