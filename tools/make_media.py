#!/usr/bin/env python3
"""Pregătește media pentru import:

1. optimizează fotografiile extrase, inclusiv cele din descrieri (max 1200px, JPEG q82) -> data/opt/
2. generează cadre 360° DEMONSTRATIVE (rotație cilindrică a corpului borcanului) -> data/360/<id>/
3. generează clipuri video DEMONSTRATIVE din fotografiile produsului (ffmpeg) -> data/video/<id>.mp4

Cadrele 360° și clipurile sunt doar exemple de funcționalitate; se înlocuiesc
din admin cu fotografii/filmări reale ale produselor.
"""
import json
import math
import subprocess
from pathlib import Path

import numpy as np
from PIL import Image

D = Path(__file__).parent / "data"
data = json.loads((D / "natur.json").read_text())
P = {p["id"]: p for p in data["products"]}

# Corpul borcanului în procente din imagine: x0, x1, y0, y1
JARS = {
    556: (20, 76, 21, 83),   # Unt topit GHEE
    91: (26, 75, 23, 84),    # Pate prepeliță
    896: (32, 70, 40, 90),   # Ulei cocos
}
FRAMES = 36

VIDEOS = [556, 98, 23, 58, 601, 170]


def optimize():
    out = D / "opt"
    out.mkdir(exist_ok=True)
    for p in data["products"]:
        for fn in p["local_images"] + list(p.get("desc_images", {}).values()):
            dest = out / fn
            if dest.exists():
                continue
            im = Image.open(D / "img" / fn).convert("RGB")
            im.thumbnail((1200, 1200), Image.LANCZOS)
            im.save(dest, "JPEG", quality=82, optimize=True, progressive=True)


def shade(a):
    return 0.6 + 0.4 * np.sqrt(np.clip(np.cos(a), 0, 1))


def spin_frames(pid, box):
    src = np.asarray(Image.open(D / "opt" / P[pid]["local_images"][0]).convert("RGB"), dtype=np.float32)
    h, w, _ = src.shape
    x0, x1 = int(box[0] / 100 * w), int(box[1] / 100 * w)
    y0, y1 = int(box[2] / 100 * h), int(box[3] / 100 * h)
    cx, r = (x0 + x1) / 2, (x1 - x0) / 2
    region = src[y0:y1]

    # Textura etichetei: doar zona centrală sigură (|unghi| < A), fără fundal.
    A, blend, nf = 1.1, 0.45, 720
    a = np.linspace(-math.pi, math.pi, nf, endpoint=False)
    xs = np.clip(cx + r * np.sin(np.clip(a, -A, A)), 0, w - 1.001)
    xi = xs.astype(int)
    t = (xs - xi)[None, :, None]
    front = region[:, xi] * (1 - t) + region[:, xi + 1] * t
    front = front / shade(np.clip(a, -A, A))[None, :, None]
    # Spatele borcanului: culoarea medie a fiecărui rând (etichetă / conținut).
    inner = np.abs(a) < A * 0.8
    back = np.median(front[:, inner], axis=1)                       # mediana: ignoră textul
    k = max(3, (y1 - y0) // 40)
    kernel = np.ones(k) / k
    back = np.stack([np.convolve(np.pad(back[:, c], k, mode="edge"), kernel, "same")[k:-k] for c in range(3)], axis=1)
    back = back[:, None, :] * 0.95
    wgt = np.clip((A - np.abs(a)) / blend, 0, 1)[None, :, None]
    tex = front * wgt + back * (1 - wgt)
    n = nf

    cols = np.arange(x0, x1)
    phi = np.arcsin(np.clip((cols + 0.5 - cx) / r, -1, 1))
    out_dir = D / "360" / str(pid)
    out_dir.mkdir(parents=True, exist_ok=True)
    for k in range(FRAMES):
        theta = 2 * math.pi * k / FRAMES
        u = ((phi + theta + math.pi) % (2 * math.pi)) / (2 * math.pi) * n
        ui = np.floor(u).astype(int) % n
        uj = (ui + 1) % n
        f = (u - np.floor(u))[None, :, None]
        body = (tex[:, ui] * (1 - f) + tex[:, uj] * f) * shade(phi)[None, :, None]
        # marginile laterale (|φ| mare) rămân din fotografia originală
        keep = np.clip((np.abs(phi) - 1.15) / 0.25, 0, 1)[None, :, None]
        frame = src.copy()
        frame[y0:y1, x0:x1] = body * (1 - keep) + src[y0:y1, x0:x1] * keep
        Image.fromarray(np.clip(frame, 0, 255).astype(np.uint8)).save(
            out_dir / f"{pid}-360-{k + 1:02d}.jpg", "JPEG", quality=80, optimize=True
        )


def video(pid):
    imgs = P[pid]["local_images"][:6]
    out = D / "video"
    out.mkdir(exist_ok=True)
    dest = out / f"{pid}.mp4"
    if dest.exists():
        return
    dur, fade, fps, size = 3.5, 0.6, 30, 720
    args = ["ffmpeg", "-y", "-loglevel", "error"]
    for fn in imgs:
        args += ["-loop", "1", "-t", str(dur), "-i", str(D / "opt" / fn)]
    frames = int(dur * fps)
    parts = []
    for i in range(len(imgs)):
        zoom = "min(zoom+0.0012,1.12)" if i % 2 == 0 else "if(eq(on,0),1.12,max(zoom-0.0012,1))"
        parts.append(
            f"[{i}:v]scale={size * 2}:{size * 2}:force_original_aspect_ratio=increase,crop={size * 2}:{size * 2},"
            f"zoompan=z='{zoom}':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d={frames}:s={size}x{size}:fps={fps},"
            f"setsar=1,format=yuv420p[v{i}]"
        )
    last = "v0"
    for i in range(1, len(imgs)):
        off = i * (dur - fade)
        parts.append(f"[{last}][v{i}]xfade=transition=fade:duration={fade}:offset={off:.2f}[x{i}]")
        last = f"x{i}"
    args += ["-filter_complex", ";".join(parts), "-map", f"[{last}]",
             "-c:v", "libx264", "-preset", "slow", "-crf", "31", "-pix_fmt", "yuv420p",
             "-movflags", "+faststart", str(dest)]
    subprocess.run(args, check=True)


if __name__ == "__main__":
    optimize()
    print("imagini optimizate")
    for pid, box in JARS.items():
        spin_frames(pid, box)
        print("360:", pid, P[pid]["name"])
    for pid in VIDEOS:
        video(pid)
        print("video:", pid, P[pid]["name"])
