#!/usr/bin/env python3
"""
Generate photorealistic patient portraits using fal.ai Flux 2 Realism LoRA.

Usage:
    python3 demo/generate-patient-photos.py                    # all 8
    python3 demo/generate-patient-photos.py 01 04 08           # specific visits

Requires:
    FAL_KEY env var (or pass via --key)
    pip install requests  (or uses urllib as fallback)

Output:
    demo/visits/visit-XX-name/patient-photo.png
"""

import json
import os
import ssl
import sys
import time
import urllib.request

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
VISITS_DIR = os.path.join(BASE_DIR, "visits")

API_URL = "https://queue.fal.run/fal-ai/flux-2-lora-gallery/realism"

# SSL context (macOS Python sometimes needs this)
CTX = ssl.create_default_context()
CTX.check_hostname = False
CTX.verify_mode = ssl.CERT_NONE

PROMPT_PREFIX = (
    "Professional medical patient profile photograph, head and shoulders portrait, "
    "neutral light gray background, natural soft studio lighting. Photorealistic, "
    "shot on Canon EOS R5, 85mm f1.8 lens, shallow depth of field. "
)

PATIENTS = {
    "00": {
        "dir": "visit-00-pvcs-palpitations",
        "prompt": "Alex, a 40-year-old Black African American man with short cropped dark hair, "
                  "clean-shaven, athletic medium build, calm but slightly concerned expression, "
                  "wearing a casual light gray henley shirt.",
    },
    "01": {
        "dir": "visit-01-coronarography-stenosis",
        "prompt": "Marie, a 72-year-old European woman with silver-gray chin-length hair, "
                  "gentle but tired expression, light wrinkles, wearing a simple light blue hospital gown.",
    },
    "02": {
        "dir": "visit-02-gastric-bypass-preop",
        "prompt": "Sofia, a 48-year-old European woman with short brown hair, medium to heavy build, "
                  "friendly warm smile, light makeup, wearing a cream colored top.",
    },
    "03": {
        "dir": "visit-03-hypertension-followup",
        "prompt": "Henri, a 68-year-old French man with gray hair and well-trimmed gray beard, "
                  "wire-frame glasses, dignified calm expression, wearing a light blue button-up shirt.",
    },
    "04": {
        "dir": "visit-04-chest-pain-carotid",
        "prompt": "Krystyna, a 62-year-old Polish woman with short wavy brown and gray hair, "
                  "heavier build with round face, concerned worried expression, wearing a dark green sweater.",
    },
    "05": {
        "dir": "visit-05-arm-pain-fibromyalgia",
        "prompt": "Fatima, a 55-year-old North African woman wearing an olive green hijab headscarf, "
                  "warm but tired dark brown eyes, slight wrinkles around eyes, gentle expression, "
                  "wearing a beige blouse.",
    },
    "06": {
        "dir": "visit-06-aortic-aneurysm-smoking",
        "prompt": "Robert, a 60-year-old European man with weathered thin face, short gray receding hair, "
                  "deep-set blue eyes, gaunt cheekbones from smoking, wearing a gray-green casual shirt.",
    },
    "07": {
        "dir": "visit-07-preop-stent-statin",
        "prompt": "Tomasz, a 58-year-old Polish Slavic man with very large heavy overweight build, "
                  "round fleshy face, double chin, short dark hair with gray at temples, wearing a white t-shirt.",
    },
    "08": {
        "dir": "visit-08-hypertension-bp-monitoring",
        "prompt": "Marek, a 52-year-old Polish man with medium build, brown hair with some gray, "
                  "slightly anxious worried expression, clean-shaven, wearing a dark blue polo shirt.",
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
    print(f"  OK — {size_kb}K saved to {os.path.basename(output_path)}")
    return True


def main():
    api_key = get_api_key()

    # Which visits to generate
    if len(sys.argv) > 1:
        visit_ids = sys.argv[1:]
    else:
        visit_ids = sorted(PATIENTS.keys())

    print(f"Generating {len(visit_ids)} patient portraits via Flux 2 Realism (fal.ai)\n")

    # Submit all jobs first (batch)
    jobs = []
    for vid in visit_ids:
        if vid not in PATIENTS:
            print(f"Unknown visit: {vid}, skipping")
            continue
        p = PATIENTS[vid]
        output_path = os.path.join(VISITS_DIR, p["dir"], "patient-photo.png")
        print(f"Visit {vid}: submitting...")
        request_id = submit_job(p["prompt"], api_key)
        jobs.append((vid, request_id, output_path))
        time.sleep(0.5)  # small delay between submissions

    print(f"\n{len(jobs)} jobs submitted. Polling for results...\n")

    # Poll and download
    for vid, request_id, output_path in jobs:
        print(f"Visit {vid}: polling {request_id[:12]}...")
        poll_and_download(request_id, output_path, api_key)

    print("\nDone!")


if __name__ == "__main__":
    main()
