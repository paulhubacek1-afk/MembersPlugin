#!/usr/bin/env python3
"""
Members Plugin – CSS-Variablen-Konverter.

Ersetzt hartkodierte Farben (und einige Radien) in den Ultimate-Member-
Stylesheets durch CSS Custom Properties mit dem Originalwert als Fallback:

    color: #3ba1da;   ->   color: var(--um-primary, #3ba1da);

Dadurch sieht das Plugin ohne Einstellungen exakt aus wie vorher, kann aber
über die Variablen (automatisch aus dem Theme oder manuell) umgefärbt werden.

Aufruf:  python3 tools/css-vars.py [--check] DATEI.css [...]
Das Skript ist idempotent – bereits umgewandelte Werte werden übersprungen.
"""
import re
import sys
from collections import Counter

# --- Farb-Normalisierung -------------------------------------------------------

HEX_RE = re.compile(r'#[0-9a-fA-F]{3,8}\b')
RGBA_RE = re.compile(r'rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(?:,\s*[\d.]+\s*)?\)')


def norm_hex(h):
    h = h.lower()[1:]
    if len(h) in (3, 4):
        h = ''.join(c * 2 for c in h)
    if len(h) == 8 and h.endswith('ff'):
        h = h[:6]
    return '#' + h


# Kategorien von Eigenschaften
TEXT_PROPS = {'color', '-webkit-text-fill-color', 'fill', 'caret-color'}
BG_PROPS = {'background', 'background-color'}
SKIP_PROPS = {'filter', '-ms-filter', 'box-shadow', '-webkit-box-shadow', '-moz-box-shadow', 'text-shadow'}

ON_PRIMARY_SEL = re.compile(
    r'um-button|button|btn|um-modal-header|\.current|\.active|um-field-group-head|\.upload\b|picker__|'
    r'um-request-button|select2-highlighted|select2-results__option--highlighted|um-members-pagi span\.current|'
    r'ui-slider|um-confirm|um-notice'
)
ALT_SEL = re.compile(r'um-alt|\.um-button-alt|secondary')

PRIMARY = {'#3ba1da', '#0089ec', '#5897fb', '#3875d7', '#007fff', '#003eff'}
PRIMARY_HOVER = {'#44b0ec', '#b1dcfb', '#e1f4ff'}
ERROR = {'#c74a4a', '#db2929', '#aa0000', '#c65151', '#cc3000', '#e74c3c', '#ff0000', '#dd3333', '#d63638'}
SUCCESS = {'#7acf58', '#6fc24a', '#00a32a', '#46b450'}
INFO = {'#497bc7'}
WARN_BG = {'#f9f9d1', '#feffde'}
WARN_BORDER = {'#efe4a2'}
STRONG = {'#000000', '#111111', '#222222', '#333333', '#444444'}
BODY = {'#555555', '#666666'}
MUTED = {'#777777', '#888888', '#999999'}
LIGHT = {'#aaaaaa', '#bbbbbb', '#cccccc', '#c6c6c6'}
BORDERS = {'#dddddd', '#e5e5e5', '#eeeeee', '#e0e0e0', '#e4e4e4', '#f1f1f1', '#e9e9e9', '#dcdcdc', '#d9d9d9', '#ebebeb'}
BORDERS_STRONG = {'#cccccc', '#bbbbbb', '#aaaaaa', '#c6c6c6', '#999999', '#c5c5c5', '#888888'}
BG_ALT = {'#f4f4f4', '#f9f9f9', '#f1f1f1', '#fafafa', '#f5f5f5', '#f7f7f7', '#f2f2f2', '#f8f8f8', '#eeeeee', '#e5e5e5', '#efefef', '#e9e9e9', '#f6f6f6', '#ededed', '#e4e4e4'}
WHITE = '#ffffff'


def classify(prop, sel, color):
    """Gibt den Variablennamen für eine Farbe in einem Kontext zurück (oder None)."""
    p = prop.lower()
    if p in SKIP_PROPS:
        return None
    is_text = p in TEXT_PROPS
    is_bg = p in BG_PROPS or p.startswith('background')
    is_border = p.startswith('border') or p.startswith('outline') or p == 'column-rule-color'
    c = color

    if c in PRIMARY:
        return 'primary'
    if c in PRIMARY_HOVER:
        return 'primary-hover' if c == '#44b0ec' else 'primary-soft'
    if c in ERROR:
        return 'error'
    if c in SUCCESS:
        return 'success'
    if c in INFO:
        return 'info'
    if c in WARN_BG:
        return 'warning-bg'
    if c in WARN_BORDER:
        return 'warning-border'

    if is_text:
        if c == WHITE:
            return 'on-primary' if ON_PRIMARY_SEL.search(sel) else None
        if ALT_SEL.search(sel) and c in BODY:
            return 'on-secondary'
        if c in STRONG:
            return 'text-strong'
        if c in BODY:
            return 'text'
        if c in MUTED:
            return 'text-muted'
        if c in LIGHT:
            return 'text-light'
        return None

    if is_bg:
        if c == WHITE:
            return 'bg'
        if ALT_SEL.search(sel) and c == '#eeeeee':
            return 'secondary'
        if ALT_SEL.search(sel) and c == '#e5e5e5':
            return 'secondary-hover'
        if c in ('#333333', '#222222', '#000000') and 'tipsy' in sel:
            return 'tooltip-bg'
        if c in BG_ALT:
            return 'bg-alt'
        if c in ('#dddddd', '#cccccc', '#e0e0e0'):
            return 'border'
        return None

    if is_border:
        if c == WHITE:
            return 'bg'
        if c in BORDERS:
            return 'border'
        if c in BORDERS_STRONG:
            return 'border-strong'
        if c in ('#333333', '#222222') and 'tipsy' in sel:
            return 'tooltip-bg'
        return None
    return None


# --- Parser ----------------------------------------------------------------------

COMMENT_RE = re.compile(r'/\*.*?\*/', re.S)
BLOCK_RE = re.compile(r'([^{}]*)\{([^{}]*)\}')


def split_decls(body):
    """Teilt einen Deklarationsblock an ';' (Klammern/Strings beachtend)."""
    parts, depth, quote, cur = [], 0, None, ''
    for ch in body:
        if quote:
            cur += ch
            if ch == quote:
                quote = None
            continue
        if ch in '"\'':
            quote = ch
        elif ch == '(':
            depth += 1
        elif ch == ')':
            depth -= 1
        elif ch == ';' and depth == 0:
            parts.append(cur + ';')
            cur = ''
            continue
        cur += ch
    parts.append(cur)
    return parts


stats = Counter()
unmapped = Counter()


def transform_decl(decl, sel):
    m = re.match(r'(\s*)([-\w]+)(\s*:\s*)(.*)$', decl, re.S)
    if not m or 'var(--um-' in decl:
        return decl
    pre, prop, colon, val = m.groups()

    def rep_hex(mm):
        raw = mm.group(0)
        start = mm.start()
        # Nicht innerhalb von url(...) ersetzen
        if 'url(' in val[:start] and ')' not in val[val.rfind('url(', 0, start):start]:
            return raw
        var = classify(prop, sel, norm_hex(raw))
        if var:
            stats[var] += 1
            return 'var(--um-%s, %s)' % (var, raw)
        if prop.lower() not in SKIP_PROPS:
            unmapped[(prop.lower(), norm_hex(raw))] += 1
        return raw

    new_val = HEX_RE.sub(rep_hex, val)

    # Eckenradien: kleine feste Radien werden über --um-radius steuerbar.
    if prop.lower() in ('border-radius', '-webkit-border-radius', '-moz-border-radius'):
        rr = re.fullmatch(r'\s*(\d+)px(\s*!important)?\s*;?\s*', new_val)
        if rr and 1 <= int(rr.group(1)) <= 8:
            var = 'button-radius' if re.search(r'button|btn', sel) else 'radius'
            stats[var] += 1
            new_val = re.sub(r'(\d+)px', lambda x: 'var(--um-%s, %spx)' % (var, x.group(1)), new_val, count=1)
    return pre + prop + colon + new_val


def transform(css):
    def rep_block(m):
        sel_raw, body = m.group(1), m.group(2)
        sel = COMMENT_RE.sub('', sel_raw).strip()
        if sel.startswith('@font-face'):
            return m.group(0)
        decls = split_decls(body)
        return sel_raw + '{' + ''.join(transform_decl(d, sel) for d in decls) + '}'

    # Kommentare temporär maskieren, damit '{' in Kommentaren nichts zerstört
    comments = []

    def mask(m):
        comments.append(m.group(0))
        return '/*__MPC%d__*/' % (len(comments) - 1)

    masked = COMMENT_RE.sub(mask, css)
    out = BLOCK_RE.sub(rep_block, masked)
    return re.sub(r'/\*__MPC(\d+)__\*/', lambda m: comments[int(m.group(1))], out)


if __name__ == '__main__':
    check = '--check' in sys.argv
    files = [a for a in sys.argv[1:] if not a.startswith('--')]
    for f in files:
        with open(f, encoding='utf-8') as fh:
            src = fh.read()
        before = sum(stats.values())
        out = transform(src)
        print('%-45s %4d Ersetzungen' % (f, sum(stats.values()) - before))
        if not check and out != src:
            with open(f, 'w', encoding='utf-8') as fh:
                fh.write(out)
    print('\nVariablen:', dict(stats.most_common()))
    print('\nNicht zugeordnet (bleiben fest):')
    for (p, c), n in unmapped.most_common(40):
        print('  %-28s %-10s %d' % (p, c, n))
