#!/usr/bin/env python3
"""
A deep font check: opens the built .woff2 files and confirms the glyphs
we need are really inside them.

FontTest checks the declared unicode-range, which is enough to catch a
subset that was removed. The declaration and the file can drift apart if
the font vendor changes how it slices them, and this looks in the file.

Run:      npm run build && python3 scripts/check-font-glyphs.py
Requires: pip install fonttools
"""

import glob
import sys

# Both cases. The list was lower case only, so a capitalised Kazakh
# heading could have fallen back to a system font with the check still
# green — and the letters live in a different part of the range, so their
# presence does not follow from the lower-case ones being there.
KAZAKH = {
    'ә': 0x04D9, 'ғ': 0x0493, 'қ': 0x049B, 'ң': 0x04A3, 'ө': 0x04E9,
    'ұ': 0x04B1, 'ү': 0x04AF, 'һ': 0x04BB, 'і': 0x0456,
    'Ә': 0x04D8, 'Ғ': 0x0492, 'Қ': 0x049A, 'Ң': 0x04A2, 'Ө': 0x04E8,
    'Ұ': 0x04B0, 'Ү': 0x04AE, 'Һ': 0x04BA, 'І': 0x0406,
}

RUSSIAN = {'ж': 0x0436, 'щ': 0x0449, 'ъ': 0x044A, 'ё': 0x0451, 'Я': 0x042F}

# Every weight the build produces, not only 400. A subset that lost a
# glyph in one weight would show it in body text and not in a heading.
PATTERN = 'public/build/assets/inter-*-normal-*.woff2'


def main() -> int:
    try:
        from fontTools.ttLib import TTFont
    except ImportError:
        print('needs fonttools: pip install fonttools')
        return 2

    files = sorted(glob.glob(PATTERN))

    if not files:
        print(f'no font files at {PATTERN}\nrun npm run build first')
        return 2

    covered: set[int] = set()

    for path in files:
        font = TTFont(path)
        points: set[int] = set()

        for table in font['cmap'].tables:
            points |= set(table.cmap.keys())

        covered |= points
        here = [c for c, cp in KAZAKH.items() if cp in points]
        name = path.rsplit('/', 1)[-1]
        print(f'{name}: glyphs {len(points):5d}  kazakh {len(here)}/{len(KAZAKH)} {"".join(here)}')

    print()
    failed = False

    for label, letters in (('kazakh', KAZAKH), ('russian', RUSSIAN)):
        missing = [f'{c} U+{cp:04X}' for c, cp in letters.items() if cp not in covered]

        if missing:
            print(f'{label}: MISSING {missing}')
            failed = True
        else:
            print(f'{label}: all present')

    return 1 if failed else 0


if __name__ == '__main__':
    sys.exit(main())
