from fastapi import APIRouter, HTTPException, Request
from pydantic import BaseModel
from app.db.milvus import connect_milvus, search_vectors_sberta, milvus_available
from app.services.vectorizerSBERTa import get_text_embedding_sberta, preprocess_text_sberta
from app.db.elastic import get_patents_by_ids
import os

router = APIRouter()

API_KEY = os.getenv("API_KEY")

class SimilarityRequest(BaseModel):
    abstract: str
    limit: int = 10

@router.on_event("startup")
def startup():
    connect_milvus()

@router.post("/similarity")
async def get_similar_patents_sberta(request: Request, body: SimilarityRequest):
    # Mengecek header Authorization untuk API key
    api_key = request.headers.get("Authorization")
    if api_key != f"Bearer {API_KEY}":
        raise HTTPException(status_code=403, detail="Unauthorized")

    try:
        # Preprocessing untuk SBERT
        abstract = preprocess_text_sberta(body.abstract)  # Pakai fungsi preprocess dari SBERT code
        embedding_tensor = get_text_embedding_sberta(abstract)  # Dapatkan tensor
        results = search_vectors_sberta(embedding_tensor, body.limit)

        # Ambil ID dan skor dari hasil vektor similarity
        id_score_pairs = [
            {"id": hit.entity.get("id"), "score": hit.distance}
            for hit in results[0]
        ]
        ids = [entry["id"] for entry in id_score_pairs]

        # Ambil data paten dari Elasticsearch
        es_hits = get_patents_by_ids(ids).get("hits", [])

        # Buat dict mapping dari ID ke hasil Elasticsearch (_source)
        id_to_source = {
            hit["_source"]["patent_id"]: hit["_source"]
            for hit in es_hits
        }

        # Gabungkan score dengan data dari Elasticsearch, tambahkan nomor urut
        combined_results = []
        for idx, item in enumerate(id_score_pairs, start=1):
            patent_id = item["id"]
            source = id_to_source.get(patent_id)
            if source:
                combined_results.append({
                    "no": idx,  # Tambahkan nomor urut
                    **source,
                    "score": item["score"]
                })

        return {
            "success": True,
            "data": combined_results
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))