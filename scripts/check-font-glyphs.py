#!/usr/bin/env python3
"""
Глубокая проверка шрифта: смотрит внутрь собранных .woff2 и убеждается,
что нужные глифы там действительно есть.

Тест FontTest проверяет объявленные unicode-range — этого достаточно,
чтобы поймать удалённое подмножество. Но объявление и содержимое файла
могут разойтись, если поставщик шрифта изменит нарезку. Этот скрипт
смотрит в сам файл.

Запуск:  npm run build && python3 scripts/check-font-glyphs.py
Требует: pip install fonttools
"""

import glob
import sys

KAZAKH = {
    'ә': 0x04D9, 'ғ': 0x0493, 'қ': 0x049B, 'ң': 0x04A3, 'ө': 0x04E9,
    'ұ': 0x04B1, 'ү': 0x04AF, 'һ': 0x04BB, 'і': 0x0456,
}

RUSSIAN = {'ж': 0x0436, 'щ': 0x0449, 'ъ': 0x044A, 'ё': 0x0451, 'Я': 0x042F}

PATTERN = 'public/build/assets/inter-400-normal-*.woff2'


def main() -> int:
    try:
        from fontTools.ttLib import TTFont
    except ImportError:
        print('нужен fonttools: pip install fonttools')
        return 2

    files = sorted(glob.glob(PATTERN))

    if not files:
        print(f'файлов шрифта нет: {PATTERN}\nсначала: npm run build')
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
        print(f'{name}: глифов {len(points):5d}  казахских {len(here)}/9 {"".join(here)}')

    print()
    failed = False

    for label, letters in (('казахские', KAZAKH), ('русские', RUSSIAN)):
        missing = [f'{c} U+{cp:04X}' for c, cp in letters.items() if cp not in covered]

        if missing:
            print(f'{label}: ОТСУТСТВУЮТ {missing}')
            failed = True
        else:
            print(f'{label}: все на месте')

    return 1 if failed else 0


if __name__ == '__main__':
    sys.exit(main())
