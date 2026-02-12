#!/usr/bin/env python3
"""
Generate photorealistic doctor portraits using fal.ai Flux 2 Realism LoRA.

Usage:
    python3 demo/generate-doctor-photos.py                        # all doctors
    python3 demo/generate-doctor-photos.py default endo gastro    # specific ones

Requires:
    FAL_KEY env var (or in .env)

Output:
    demo/doctors/{key}/doctor-photo.png
"""

import json
import os
import ssl
import sys
import time
import urllib.request

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DOCTORS_DIR = os.path.join(BASE_DIR, "doctors")

API_URL = "https://queue.fal.run/fal-ai/flux-2-lora-gallery/realism"

CTX = ssl.create_default_context()
CTX.check_hostname = False
CTX.verify_mode = ssl.CERT_NONE

PROMPT_PREFIX = (
    "Professional medical doctor portrait photograph, head and shoulders, "
    "wearing a white lab coat with stethoscope around neck, "
    "neutral light gray clinical background, natural soft studio lighting. "
    "Photorealistic, shot on Canon EOS R5, 85mm f1.8 lens, shallow depth of field. "
)

DOCTORS = {
    "default": {
        "dir": "default",
        "prompt": "Michael, a 45-year-old European man with short brown hair with some gray at temples, "
                  "neatly trimmed beard, confident warm smile, blue-gray eyes, athletic build, "
                  "wearing white lab coat over light blue dress shirt, stethoscope around neck. "
                  "Cardiology specialist, professional and approachable.",
    },
    "endocrinologist": {
        "dir": "endocrinologist",
        "prompt": "Anita, a 42-year-old Indian woman with long dark brown hair pulled back in a neat bun, "
                  "warm brown skin, kind intelligent dark eyes, gentle professional smile, "
                  "small gold earrings, wearing white lab coat over a burgundy silk blouse, "
                  "stethoscope around neck. Endocrinology specialist.",
    },
    "gastroenterologist": {
        "dir": "gastroenterologist",
        "prompt": "Lisa, a 38-year-old East Asian Chinese-American woman with straight black shoulder-length hair, "
                  "light skin, sharp confident expression with a warm smile, minimal makeup, "
                  "wearing white lab coat over a navy blue blouse, stethoscope around neck. "
                  "Gastroenterology specialist.",
    },
    "pulmonologist": {
        "dir": "pulmonologist",
        "prompt": "Chukwuemeka, a 50-year-old Nigerian man with dark brown skin, short graying black hair, "
                  "well-groomed goatee with gray, warm deep brown eyes, distinguished authoritative presence, "
                  "wearing white lab coat over a white dress shirt with dark tie, stethoscope around neck. "
                  "Pulmonology specialist, experienced senior physician.",
    },
}


def get_api_key():
    key = os.environ.get("FAL_KEY")
    if not key:
        env_file = os.path.join(BASE_DIR, "..", ".env")
        if os.path.exists(env_file):
            for line in open(env_file):
                if line.startswith("FAL_KEY="):
                    key = line.strip().split("=", 1)[1]
                    break
    if not key:
        print("ERROR: FAL_KEY not found. Set env var or add to .env")
        sys.exit(1)
    return key


def submit_job(prompt, api_key):
    """Submit image generation job to fal.ai queue."""
    headers = {
        "Authorization": f"Key {api_key}",
        "Content-Type": "application/json",
    }
    data = json.dumps({
        "prompt": PROMPT_PREFIX + prompt,
        "image_size": "square",
        "num_images": 1,
        "output_format": "png",
        "num_inference_steps": 40,
        "guidance_scale": 2.5,
    }).encode()
    req = urllib.request.Request(API_URL, data=data, headers=headers)
    resp = urllib.request.urlopen(req, timeout=120, context=CTX)
    result = json.loads(resp.read())
    return result["request_id"]


def poll_and_download(request_id, output_path, api_key):
    """Poll for completion and download the image."""
    headers = {"Authorization": f"Key {api_key}"}
    status_url = f"https://queue.fal.run/fal-ai/flux-2-lora-gallery/requests/{request_id}/status"
    result_url = f"https://queue.fal.run/fal-ai/flux-2-lora-gallery/requests/{request_id}"

    for attempt in range(60):
        req = urllib.request.Request(status_url, headers=headers)
        resp = urllib.request.urlopen(req, timeout=30, context=CTX)
        status = json.loads(resp.read()).get("status")
        if status == "COMPLETED":
            break
        time.sleep(2)
    else:
        print(f"  TIMEOUT after 120s")
        return False

    req = urllib.request.Request(result_url, headers=headers)
    resp = urllib.request.urlopen(req, timeout=30, context=CTX)
    data = json.loads(resp.read())
    img_url = data["images"][0]["url"]

    req = urllib.request.Request(img_url)
    img_data = urllib.request.urlopen(req, timeout=60, context=CTX).read()
    with open(output_path, "wb") as f:
        f.write(img_data)

    size_kb = len(img_data) // 1024
    print(f"  OK -- {size_kb}K saved to {output_path}")
    return True


def main():
    api_key = get_api_key()

    if len(sys.argv) > 1:
        keys = sys.argv[1:]
    else:
        keys = sorted(DOCTORS.keys())

    print(f"Generating {len(keys)} doctor portraits via Flux 2 Realism (fal.ai)\n")

    jobs = []
    for key in keys:
        if key not in DOCTORS:
            print(f"Unknown doctor: {key}, skipping")
            continue
        d = DOCTORS[key]
        out_dir = os.path.join(DOCTORS_DIR, d["dir"])
        os.makedirs(out_dir, exist_ok=True)
        output_path = os.path.join(out_dir, "doctor-photo.png")
        print(f"Doctor [{key}]: submitting...")
        request_id = submit_job(d["prompt"], api_key)
        jobs.append((key, request_id, output_path))
        time.sleep(0.5)

    print(f"\n{len(jobs)} jobs submitted. Polling for results...\n")

    for key, request_id, output_path in jobs:
        print(f"Doctor [{key}]: polling {request_id[:12]}...")
        poll_and_download(request_id, output_path, api_key)

    print("\nDone!")


if __name__ == "__main__":
    main()
