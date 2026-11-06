import math
import sys
from pathlib import Path

from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.boundsPen import BoundsPen
from fontTools.ttLib import TTFont

OUT = Path(sys.argv[1])
OUT.mkdir(parents=True, exist_ok=True)

INTER = '/usr/share/fonts/opentype/inter/InterDisplay-Bold.otf'
INTER_XB = '/usr/share/fonts/opentype/inter/InterDisplay-ExtraBold.otf'
MONO = '/usr/share/fonts/truetype/jetbrains-mono/JetBrainsMono-SemiBold.ttf'

INK = {
    'dark':  {'text': '#F7F3FF', 'soft': '#C4B5FD', 'muted': '#8A7CA8', 'accent': '#A855F7', 'rule': '#3B2F5C'},
    'light': {'text': '#140F22', 'soft': '#4C3D73', 'muted': '#7A6E95', 'accent': '#7C3AED', 'rule': '#D9D0EC'},
}


def text_path(font_file, text, size, tracking=0.0):
    """Outline text into a single SVG path. Returns (d, width, ascent, descent) in px."""
    font = TTFont(font_file)
    gs = font.getGlyphSet()
    cmap = font.getBestCmap()
    upm = font['head'].unitsPerEm
    s = size / upm
    hmtx = font['hmtx']
    kern = {}
    if 'kern' in font:
        for table in font['kern'].kernTables:
            kern.update(table.kernTable)
    pen = SVGPathPen(gs)
    x = 0.0
    prev = None
    for ch in text:
        g = cmap[ord(ch)]
        if prev and (prev, g) in kern:
            x += kern[(prev, g)] * s
        tp = TransformPen(pen, (s, 0, 0, -s, x, 0))
        gs[g].draw(tp)
        x += hmtx[g][0] * s + tracking * size
        prev = g
    width = x - tracking * size
    bp = BoundsPen(gs)
    for ch in text:
        gs[cmap[ord(ch)]].draw(bp)
    os2 = font['OS/2']
    return pen.getCommands(), width, os2.sCapHeight * s, os2.sxHeight * s


def fmt(v):
    return f'{v:.2f}'.rstrip('0').rstrip('.')


def crescent_c(cx, cy, R, r, dx, gap_deg):
    """Crescent shaped C: outer circle R at (cx,cy), inner circle r shifted by dx to the right."""
    t = math.radians(gap_deg)
    def outer(a):
        return cx + R * math.cos(a), cy + R * math.sin(a)
    def inner(a):
        # point where the ray from the outer centre at angle a meets the inner circle
        ux, uy = math.cos(a), math.sin(a)
        b = -2 * dx * ux
        c = dx * dx - r * r
        d = (-b + math.sqrt(b * b - 4 * c)) / 2
        return cx + d * ux, cy + d * uy
    o1, o2 = outer(-t), outer(t)
    i1, i2 = inner(-t), inner(t)
    icx = cx + dx
    return (
        f'M{fmt(o1[0])} {fmt(o1[1])}'
        f'A{fmt(R)} {fmt(R)} 0 1 0 {fmt(o2[0])} {fmt(o2[1])}'
        f'L{fmt(i2[0])} {fmt(i2[1])}'
        f'A{fmt(r)} {fmt(r)} 0 1 1 {fmt(i1[0])} {fmt(i1[1])}Z'
    ), o1, i1


def blossom(cx, cy, size, rot, petal_fill, core, core2):
    rx, ry = 0.27 * size, 0.46 * size
    petals = ''.join(
        f'<ellipse cx="0" cy="{fmt(-ry)}" rx="{fmt(rx)}" ry="{fmt(ry)}" transform="rotate({a})"/>' for a in range(0, 360, 72)
    )
    return (
        f'<g transform="translate({fmt(cx)} {fmt(cy)}) rotate({rot})">'
        f'<g fill="{petal_fill}">{petals}</g>'
        f'<circle r="{fmt(0.17 * size)}" fill="{core}"/><circle r="{fmt(0.08 * size)}" fill="{core2}"/></g>'
    )


def defs(uid, theme):
    light = theme == 'light'
    return f'''<defs>
  <linearGradient id="{uid}c" x1="0" y1="1" x2="1" y2="0">
    <stop offset="0" stop-color="{'#5B21B6' if light else '#6D28D9'}"/>
    <stop offset=".55" stop-color="{'#7C3AED' if light else '#A855F7'}"/>
    <stop offset="1" stop-color="{'#A78BFA' if light else '#E9D5FF'}"/>
  </linearGradient>
  <linearGradient id="{uid}p" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="{'#EDE4FF' if light else '#FFFFFF'}"/>
    <stop offset="1" stop-color="{'#B9A3F5' if light else '#C4B5FD'}"/>
  </linearGradient>
</defs>'''


def mark(uid, x, y, s, theme):
    """Clyve mark in a 100x100 box, placed at x,y with scale s."""
    d, o1, i1 = crescent_c(45, 57, 37, 26.5, 6, 44)
    tip_x = (o1[0] + i1[0]) / 2
    tip_y = (o1[1] + i1[1]) / 2
    accent = '#7C3AED' if theme == 'light' else '#A855F7'
    bloom = blossom(tip_x + 2, tip_y - 2, 27, 12, f'url(#{uid}p)', accent, '#F7F3FF')
    seed = f'<circle cx="{fmt(45 + 6 + 26.5 * math.cos(math.radians(44)) + 2)}" cy="{fmt(57 + 26.5 * math.sin(math.radians(44)) + 8.5)}" r="3" fill="{accent}" opacity=".9"/>'
    return f'<g transform="translate({fmt(x)} {fmt(y)}) scale({fmt(s)})"><path d="{d}" fill="url(#{uid}c)"/>{bloom}{seed}</g>'


def ed_blossom(x, y, size, theme):
    petal = '#F7F3FF' if theme == 'dark' else '#C9B6F7'
    petal2 = '#E9D5FF' if theme == 'dark' else '#B49CF0'
    rx, ry = 0.26 * size, 0.44 * size
    out = [f'<g transform="translate({fmt(x)} {fmt(y)})">']
    for i, a in enumerate(range(0, 360, 72)):
        out.append(f'<ellipse cy="{fmt(-ry)}" rx="{fmt(rx)}" ry="{fmt(ry)}" transform="rotate({a})" fill="{petal if i % 2 == 0 else petal2}"/>')
    out.append(f'<circle r="{fmt(0.16 * size)}" fill="{INK[theme]["accent"]}"/></g>')
    return ''.join(out)


def wordmark(x, baseline, size, theme):
    ink = INK[theme]
    d1, w1, cap, xh = text_path(INTER, 'clyve', size, tracking=-0.035)
    d2, w2, _, _ = text_path(MONO, '.dev', size * 0.62, tracking=-0.02)
    gap = -size * 0.11
    svg = (
        f'<path transform="translate({fmt(x)} {fmt(baseline)})" d="{d1}" fill="{ink["text"]}"/>'
        f'<path transform="translate({fmt(x + w1 + gap)} {fmt(baseline)})" d="{d2}" fill="{ink["accent"]}"/>'
    )
    return svg, w1 + gap + w2, cap


def ed_wordmark(x, baseline, size, theme):
    ink = INK[theme]
    d1, w1, cap, _ = text_path(INTER_XB, 'Einfach', size, tracking=0.0)
    d2, w2, _, _ = text_path(INTER_XB, 'Dev', size, tracking=0.0)
    svg = (
        f'<path transform="translate({fmt(x)} {fmt(baseline)})" d="{d1}" fill="{ink["text"]}"/>'
        f'<path transform="translate({fmt(x + w1)} {fmt(baseline)})" d="{d2}" fill="{ink["accent"]}"/>'
    )
    return svg, w1 + w2, cap


def svg(w, h, body, title, theme, uid):
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {fmt(w)} {fmt(h)}" width="{fmt(w)}" height="{fmt(h)}" role="img" aria-label="{title}">'
        f'<title>{title}</title>{defs(uid, theme)}{body}</svg>\n'
    )


def write(name, content):
    (OUT / name).write_text(content)
    print('wrote', name)


for theme in ('dark', 'light'):
    sfx = '' if theme == 'dark' else '-light'
    ink = INK[theme]

    # mark only
    write(f'clyve-mark{sfx}.svg', svg(100, 100, mark('m', 0, 0, 1, theme), 'Clyve.dev', theme, 'm'))

    # horizontal logo
    size = 64
    wm, ww, cap = wordmark(0, 0, size, theme)
    mh = 92
    pad = 6
    body = mark('l', pad, pad, mh / 100, theme)
    tx = pad + mh + 18
    base = pad + mh / 2 + cap / 2 + 2
    body += f'<g transform="translate({fmt(tx)} {fmt(base)})">{wm}</g>'
    W = tx + ww + pad
    H = mh + pad * 2
    write(f'clyve-logo{sfx}.svg', svg(W, H, body, 'Clyve.dev', theme, 'l'))

    # combo: Clyve.dev | by EinfachDev
    by_d, by_w, _, _ = text_path(MONO, 'BY', 13, tracking=0.18)
    ed, edw, edcap = ed_wordmark(0, 0, 30, theme)
    rule_x = W + 26
    bx = rule_x + 28
    cy = H / 2
    body2 = body
    body2 += f'<rect x="{fmt(rule_x)}" y="{fmt(cy - 34)}" width="1.5" height="68" rx=".75" fill="{ink["rule"]}"/>'
    body2 += f'<path transform="translate({fmt(bx)} {fmt(cy - 12)})" d="{by_d}" fill="{ink["muted"]}"/>'
    body2 += ed_blossom(bx + 11, cy + 14, 21, theme)
    body2 += f'<g transform="translate({fmt(bx + 34)} {fmt(cy + 14 + edcap / 2)})">{ed}</g>'
    W2 = bx + 34 + edw + pad
    write(f'clyve-x-einfachdev{sfx}.svg', svg(W2, H, body2, 'Clyve.dev by EinfachDev', theme, 'l'))

    # stacked: mark, wordmark, "engineered by EinfachDev"
    sw = 300
    m_size = 116
    body3 = mark('s', (sw - m_size) / 2, 8, m_size / 100, theme)
    wm2, ww2, cap2 = wordmark(0, 0, 54, theme)
    y = 8 + m_size + 22 + cap2
    body3 += f'<g transform="translate({fmt((sw - ww2) / 2)} {fmt(y)})">{wm2}</g>'
    eng_d, eng_w, ecap, _ = text_path(MONO, 'ENGINEERED BY', 11, tracking=0.2)
    ed2, edw2, edcap2 = ed_wordmark(0, 0, 18, theme)
    line_w = eng_w + 10 + 13 + 9 + edw2
    lx = (sw - line_w) / 2
    ly = y + 40
    body3 += f'<rect x="{fmt(sw / 2 - 60)}" y="{fmt(y + 18)}" width="120" height="1" fill="{ink["rule"]}"/>'
    body3 += f'<path transform="translate({fmt(lx)} {fmt(ly)})" d="{eng_d}" fill="{ink["muted"]}"/>'
    body3 += ed_blossom(lx + eng_w + 10 + 6.5, ly - ecap / 2, 12, theme)
    body3 += f'<g transform="translate({fmt(lx + eng_w + 10 + 13 + 9)} {fmt(ly + edcap2 / 2 - ecap / 2)})">{ed2}</g>'
    H3 = ly + 18
    write(f'clyve-by-einfachdev-stacked{sfx}.svg', svg(sw, H3, body3, 'Clyve.dev – engineered by EinfachDev', theme, 's'))

# app icon / favicon tile
tile = (
    '<rect width="128" height="128" rx="30" fill="#0D0B14"/>'
    '<rect x="1" y="1" width="126" height="126" rx="29" fill="none" stroke="#2A2145" stroke-width="2"/>'
    + mark('t', 16, 14, 0.96, 'dark')
)
write('clyve-icon.svg', svg(128, 128, tile, 'Clyve.dev', 'dark', 't'))

# collab icon: Clyve mark × EinfachDev blossom
x_d, x_w, xcap, _ = text_path(INTER, '×', 40)
bx_c = 112 + x_w + 18 + 46
collab = (mark('x', 0, 0, 1, 'dark')
          + f'<path transform="translate(112 {fmt(50 + 14)})" d="{x_d}" fill="#8A7CA8"/>'
          + ed_blossom(bx_c, 52, 50, 'dark'))
write('clyve-x-einfachdev-mark.svg', svg(bx_c + 48, 100, collab, 'Clyve.dev × EinfachDev', 'dark', 'x'))
