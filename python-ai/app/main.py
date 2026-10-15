from datetime import date, datetime, timedelta
from io import BytesIO
import re
from typing import Annotated

from fastapi import FastAPI, File, Form, HTTPException, UploadFile
import numpy as np
from PIL import Image, UnidentifiedImageError

app = FastAPI(title="Surplus Market AI", version="0.2.0")

# PaddleOCR downloads its official, pretrained Indonesian model on first inference.
# Keep it lazy so health checks and service startup do not load a large model.
_ocr = None


def get_ocr():
    global _ocr
    if _ocr is None:
        try:
            from paddleocr import PaddleOCR

            _ocr = PaddleOCR(
                lang="id",
                ocr_version="PP-OCRv5",
                use_doc_orientation_classify=False,
                use_doc_unwarping=False,
                use_textline_orientation=False,
                device="cpu",
                enable_mkldnn=False,
            )
        except Exception as exc:
            raise HTTPException(
                status_code=503,
                detail=f"Model OCR belum siap. Pastikan PaddlePaddle dan model pralatih tersedia: {exc}",
            ) from exc
    return _ocr


def result_data(result) -> dict:
    raw = getattr(result, "json", {})
    if callable(raw):
        raw = raw()
    if isinstance(raw, dict) and isinstance(raw.get("res"), dict):
        return raw["res"]
    return raw if isinstance(raw, dict) else {}


def normalize_text(value: str) -> str:
    return re.sub(r"\s+", " ", value).strip()


def extract_expiry_date(text: str) -> date | None:
    # Numeric formats commonly printed on Indonesian packaging.
    for match in re.finditer(r"\b(20\d{2})[./-](\d{1,2})[./-](\d{1,2})\b", text):
        try:
            return date(int(match[1]), int(match[2]), int(match[3]))
        except ValueError:
            pass
    for match in re.finditer(r"\b(\d{1,2})[./-](\d{1,2})[./-](20\d{2})\b", text):
        try:
            return date(int(match[3]), int(match[2]), int(match[1]))
        except ValueError:
            pass

    months = {
        "jan": 1, "januari": 1, "feb": 2, "februari": 2,
        "mar": 3, "maret": 3, "apr": 4, "april": 4,
        "mei": 5, "may": 5, "jun": 6, "juni": 6,
        "jul": 7, "juli": 7, "agu": 8, "agt": 8, "agustus": 8,
        "sep": 9, "september": 9, "okt": 10, "oktober": 10,
        "nov": 11, "november": 11, "des": 12, "desember": 12,
    }
    month_pattern = "|".join(sorted(months, key=len, reverse=True))
    for match in re.finditer(rf"\b(\d{{1,2}})[\s/-]+({month_pattern})[\s/-]+(20\d{{2}})\b", text.lower()):
        try:
            return date(int(match[3]), months[match[2]], int(match[1]))
        except ValueError:
            pass
    return None


async def read_image(upload: UploadFile) -> Image.Image:
    content = await upload.read()
    if not content or len(content) > 10 * 1024 * 1024:
        raise HTTPException(status_code=422, detail="Setiap foto harus berukuran maksimal 10 MB.")
    try:
        image = Image.open(BytesIO(content)).convert("RGB")
        image.load()
        return image
    except (UnidentifiedImageError, OSError) as exc:
        raise HTTPException(status_code=422, detail="Berkas yang dikirim bukan gambar yang valid.") from exc


@app.get("/health")
def health():
    return {"status": "ok", "ocr_model": "PP-OCRv5 Indonesian (lazy-loaded)"}


@app.post("/analyze")
async def analyze(
    product_id: Annotated[int, Form()],
    type: Annotated[str, Form()],
    description: Annotated[str, Form()],
    images: Annotated[list[UploadFile], File()],
    item_condition: Annotated[str | None, Form()] = None,
    expired_at: Annotated[date | None, Form()] = None,
    expiry_label: Annotated[UploadFile | None, File()] = None,
):
    if type not in {"goods", "food"}:
        raise HTTPException(status_code=422, detail="Jenis produk tidak dikenal.")
    if not 1 <= len(images) <= 6:
        raise HTTPException(status_code=422, detail="Unggah 1 sampai 6 foto produk.")
    if type == "food" and expiry_label is None:
        raise HTTPException(status_code=422, detail="Foto label kedaluwarsa wajib untuk produk makanan.")

    ocr = get_ocr()
    uploads = [expiry_label] if type == "food" and expiry_label else images
    recognized: list[str] = []
    scores: list[float] = []
    for upload in uploads:
        image = await read_image(upload)
        try:
            predictions = ocr.predict(np.asarray(image))
            for prediction in predictions:
                data = result_data(prediction)
                recognized.extend(str(text) for text in data.get("rec_texts", []) if text)
                scores.extend(float(score) for score in data.get("rec_scores", []) if score is not None)
        except HTTPException:
            raise
        except Exception as exc:
            raise HTTPException(status_code=503, detail=f"Analisis OCR gagal: {exc}") from exc

    raw_text = normalize_text(" ".join(recognized))
    avg_score = round(sum(scores) / len(scores) * 100) if scores else 0
    detected = extract_expiry_date(raw_text) if type == "food" else None
    detected = detected or expired_at

    if type == "food" and detected and detected <= date.today() + timedelta(days=30):
        return {
            "confidence_score": avg_score,
            "detected_date": detected.isoformat(),
            "recommended_price": None,
            "raw_ocr_text": raw_text,
            "rejection_reason": "expiry_within_30_days",
        }

    if type == "food" and detected is None:
        reason = "expiry_date_not_found"
    elif not scores:
        reason = "no_readable_text"
    else:
        reason = "price_estimation_not_configured"

    # OCR is pretrained for text extraction; it does not estimate resale prices or
    # assess an item's physical condition. Never invent those values here.
    return {
        "confidence_score": avg_score,
        "detected_date": detected.isoformat() if detected else None,
        "recommended_price": None,
        "raw_ocr_text": raw_text,
        "rejection_reason": reason,
        "model": "PP-OCRv5",
        "product_id": product_id,
    }
