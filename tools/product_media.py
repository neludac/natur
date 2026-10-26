#!/usr/bin/env python3
"""Pregătește fotografiile și filmările reale ale produselor pentru site -> data/media/

  python3 tools/product_media.py ~/Downloads/Poze_Video_Final_site_nou

1. fotografiile (*.jpg): max 1200px, JPEG q82, ca restul imaginilor (vezi make_media.py)
2. filmările (*.mp4): fără benzile negre laterale (cadrul util e aproape pătrat, la fel ca
   scena galeriei), fără pista audio (e mută), H.264 pentru web, cu „faststart”

Numele fișierelor se păstrează; legarea de produse o face tools/product_media.php.
Fișierele deja pregătite se sar.
"""
import re
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageOps

OUT = Path(__file__).parent / "data" / "media"


def photo(src):
    dest = OUT / src.name
    if dest.exists():
        return
    im = ImageOps.exif_transpose(Image.open(src)).convert("RGB")
    im.thumbnail((1200, 1200), Image.LANCZOS)
    im.save(dest, "JPEG", quality=82, optimize=True, progressive=True)
    print("foto:", src.name)


def video(src):
    dest = OUT / src.name
    if dest.exists():
        return
    # Zona fără benzi negre, pe tot clipul; câțiva pixeli în plus spre interior, ca să nu rămână margini închise.
    log = subprocess.run(
        ["ffmpeg", "-i", str(src), "-vf", "cropdetect=limit=24:round=2:reset=0", "-an", "-f", "null", "-"],
        capture_output=True, text=True, check=True,
    ).stderr
    w, h, x, y = map(int, re.findall(r"crop=(\d+):(\d+):(\d+):(\d+)", log)[-1])
    m = 4
    crop = f"crop={(w - 2 * m) // 2 * 2}:{h // 2 * 2}:{x + m}:{y}"
    subprocess.run(
        ["ffmpeg", "-y", "-loglevel", "error", "-i", str(src), "-vf", crop, "-an",
         "-c:v", "libx264", "-preset", "slow", "-crf", "23", "-pix_fmt", "yuv420p",
         "-movflags", "+faststart", str(dest)],
        check=True,
    )
    print("video:", src.name, crop)


if __name__ == "__main__":
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    OUT.mkdir(parents=True, exist_ok=True)
    for f in sorted(Path(sys.argv[1]).expanduser().iterdir()):
        if f.suffix.lower() in (".jpg", ".jpeg"):
            photo(f)
        elif f.suffix.lower() == ".mp4":
            video(f)
