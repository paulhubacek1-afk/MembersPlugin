#!/usr/bin/env python3
"""
Members Plugin – einfacher, sicherer CSS-Minifier (ohne externe Abhängigkeiten).

Erzeugt zu jeder übergebenen Datei X.css die Datei X.min.css.
Entfernt Kommentare (außer /*! ... */), Zeilenumbrüche und überflüssige
Leerzeichen. Strings und calc()-Ausdrücke bleiben unverändert.

Aufruf:  python3 tools/minify-css.py datei.css [...]
"""
import re
import sys


def minify(css):
    out, i, n = [], 0, len(css)
    buf = []

    def flush():
        if buf:
            out.append(('code', ''.join(buf)))
            buf.clear()

    while i < n:
        ch = css[i]
        if ch in '"\'':
            j = i + 1
            while j < n and css[j] != ch:
                j += 2 if css[j] == '\\' else 1
            flush()
            out.append(('str', css[i:j + 1]))
            i = j + 1
        elif css.startswith('/*', i):
            j = css.find('*/', i + 2)
            j = n if j == -1 else j + 2
            if css.startswith('/*!', i):
                flush()
                out.append(('str', css[i:j]))
            i = j
        else:
            buf.append(ch)
            i += 1
    flush()

    res = []
    for kind, txt in out:
        if kind == 'str':
            res.append(txt)
            continue
        t = re.sub(r'\s+', ' ', txt)
        t = re.sub(r'\s*([{};,>])\s*', r'\1', t)
        # Leerzeichen nach ':' in Deklarationen entfernen (nicht in Selektoren wie "a :hover")
        t = re.sub(r'([\w-]):\s+', r'\1:', t)
        t = t.replace(';}', '}')
        res.append(t)
    result = ''.join(res).strip()
    # calc(): Operatoren brauchen Leerzeichen – durch obige Regeln nicht betroffen, nur prüfen
    return result + '\n'


if __name__ == '__main__':
    for f in sys.argv[1:]:
        if not f.endswith('.css') or f.endswith('.min.css'):
            continue
        with open(f, encoding='utf-8') as fh:
            src = fh.read()
        target = f[:-4] + '.min.css'
        with open(target, 'w', encoding='utf-8') as fh:
            fh.write(minify(src))
        print('%s -> %s (%d -> %d Bytes)' % (f, target, len(src), len(minify(src))))
