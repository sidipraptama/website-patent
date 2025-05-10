from fastapi import APIRouter, HTTPException, Request
from pydantic import BaseModel
from app.db.milvus import connect_milvus, search_vectors, search_vectors_sberta, search_vectors_tfidf
from app.services.vectorizer import get_text_embedding, preprocess_text
from app.services.vectorizerSBERTa import get_text_embedding_sberta, preprocess_text_sberta
from app.services.vectorizerTFIDF import get_text_embedding_tfidf
# from app.services.vectorizer64 import get_text_embedding_64
# from app.services.vectorizer64 import get_text_embedding_64
from app.db.elastic import search_patents, search_patents_by_ids, get_patents_by_ids
from typing import List, Dict
from sklearn.metrics import pairwise
from typing import Dict, List, Set
import numpy as np
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
async def get_similar_patents(request: Request, body: SimilarityRequest):
    # Mengecek header Authorization untuk API key
    api_key = request.headers.get("Authorization")
    if api_key != f"Bearer {API_KEY}":
        raise HTTPException(status_code=403, detail="Unauthorized")

    try:
        abstract = preprocess_text(body.abstract)
        embedding = get_text_embedding(abstract)
        results = search_vectors(embedding, body.limit)

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

        # Gabungkan score dengan data dari Elasticsearch
        combined_results = []
        for idx, item in enumerate(id_score_pairs, start=1):
            patent_id = item["id"]
            source = id_to_source.get(patent_id)
            if source:
                combined_results.append({
                    "no": idx,
                    **source,
                    "score": item["score"]
                })

        return {
            "success": True,
            "data": combined_results
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    
# @router.post("/similarity_sberta")
# async def get_similar_patents_sberta(request: Request, body: SimilarityRequest):
#     # Mengecek header Authorization untuk API key
#     api_key = request.headers.get("Authorization")
#     if api_key != f"Bearer {API_KEY}":
#         raise HTTPException(status_code=403, detail="Unauthorized")

#     try:
#         # Preprocessing untuk SBERT
#         abstract = preprocess_text_sberta(body.abstract)  # Pakai fungsi preprocess dari SBERT code
#         embedding_tensor = get_text_embedding_sberta(abstract)  # Dapatkan tensor
#         results = search_vectors_sberta(embedding_tensor, body.limit)

#         # Ambil ID dan skor dari hasil vektor similarity
#         id_score_pairs = [
#             {"id": hit.entity.get("id"), "score": hit.distance}
#             for hit in results[0]
#         ]
#         ids = [entry["id"] for entry in id_score_pairs]

#         # Ambil data paten dari Elasticsearch
#         es_hits = get_patents_by_ids(ids).get("hits", [])

#         # Buat dict mapping dari ID ke hasil Elasticsearch (_source)
#         id_to_source = {
#             hit["_source"]["patent_id"]: hit["_source"]
#             for hit in es_hits
#         }

#         # Gabungkan score dengan data dari Elasticsearch, tambahkan nomor urut
#         combined_results = []
#         for idx, item in enumerate(id_score_pairs, start=1):
#             patent_id = item["id"]
#             source = id_to_source.get(patent_id)
#             if source:
#                 combined_results.append({
#                     "no": idx,  # Tambahkan nomor urut
#                     **source,
#                     "score": item["score"]
#                 })

#         return {
#             "success": True,
#             "data": combined_results
#         }

#     except Exception as e:
#         raise HTTPException(status_code=500, detail=str(e))
    
# @router.post("/similarity_tfidf")
# async def get_similar_patents_tfidf(request: Request, body: SimilarityRequest):
#     # Mengecek header Authorization untuk API key
#     api_key = request.headers.get("Authorization")
#     if api_key != f"Bearer {API_KEY}":
#         raise HTTPException(status_code=403, detail="Unauthorized")

#     try:
#         embedding_tensor = get_text_embedding_tfidf(body.abstract)
#         results = search_vectors_tfidf(embedding_tensor, body.limit)

#         # Ambil ID dan skor dari hasil vektor similarity
#         id_score_pairs = [
#             {"id": hit.entity.get("id"), "score": hit.distance}
#             for hit in results[0]
#         ]
#         ids = [entry["id"] for entry in id_score_pairs]

#         # Ambil data paten dari Elasticsearch
#         es_hits = get_patents_by_ids(ids).get("hits", [])

#         # Buat dict mapping dari ID ke hasil Elasticsearch (_source)
#         id_to_source = {
#             hit["_source"]["patent_id"]: hit["_source"]
#             for hit in es_hits
#         }

#         # Gabungkan score dengan data dari Elasticsearch
#         combined_results = []
#         for item in id_score_pairs:
#             patent_id = item["id"]
#             source = id_to_source.get(patent_id)
#             if source:
#                 combined_results.append({
#                     **source,
#                     "score": item["score"]
#                 })

#         return {
#             "success": True,
#             "data": combined_results
#         }

#     except Exception as e:
#         raise HTTPException(status_code=500, detail=str(e))

# @router.post("/similarity")
# async def get_similar_patents(request: Request, body: SimilarityRequest):
#     # Mengecek header Authorization untuk API key
#     api_key = request.headers.get("Authorization")
#     if api_key != f"Bearer {API_KEY}":
#         raise HTTPException(status_code=403, detail="Unauthorized")

#     try:
#         abstract = preprocess_text(body.abstract)
#         print("preprocess_text")
#         embedding = get_text_embedding(abstract)
#         print("get_text_embedding")
#         results = search_vectors(embedding, body.limit)
#         print("search_vectors")
#         return [
#             {"id": hit.entity.get("id"), "score": hit.distance}
#             for hit in results[0]
#         ]
#     except Exception as e:
#         raise HTTPException(status_code=500, detail=str(e))

# @router.post("/similarity64")
# def get_similar_patents_64(request: SimilarityRequest):
#     try:
#         abstract = preprocess_text(request.abstract)
#         embedding = get_text_embedding_64(abstract)
#         results = search_vectors_64(embedding, request.limit)
#         return [
#             {"id": hit.entity.get("id"), "score": hit.distance}
#             for hit in results[0]
#         ]
#     except Exception as e:
#         raise HTTPException(status_code=500, detail=str(e))

# @router.post("/hybrid_similarity")
# def hybrid_similarity(request: SimilarityRequest):
#     try:
#         abstract = preprocess_text(request.abstract)

#         # 1️⃣ Pencarian Vector Search di Milvus terlebih dahulu
#         embedding = get_text_embedding(abstract)
#         milvus_results = search_vectors(embedding, 5000)  # Batasi hasil Milvus
        
#         if not milvus_results or not isinstance(milvus_results[0], list):
#             raise HTTPException(status_code=500, detail="Invalid Milvus response format")

#         bert_results: Dict[str, float] = {}
#         patent_ids_from_milvus: Set[str] = set()  # Menyimpan patent_id yang ditemukan
        
#         for hit in milvus_results[0]:
#             patent_id = hit.entity.get("id")  # Ambil patent_id langsung dari metadata
#             if patent_id:
#                 bert_results[patent_id] = hit.distance  # Sudah dalam bentuk similarity
#                 patent_ids_from_milvus.add(patent_id)

#         # 2️⃣ Pencarian BM25 di Elasticsearch hanya untuk patent_id yang ditemukan di Milvus
#         if not patent_ids_from_milvus:
#             return {"total": 0, "results": []}  # Jika Milvus tidak menemukan hasil, langsung return
        
#         es_results = search_patents_by_ids(abstract, patent_ids=list(patent_ids_from_milvus), from_=0, size=5000)
        
#         if "hits" not in es_results or not isinstance(es_results["hits"], list):
#             raise HTTPException(status_code=500, detail="Invalid Elasticsearch response format")
        
#         bm25_results: Dict[str, float] = {}

#         for hit in es_results["hits"]:
#             patent_id = hit["_source"]["patent_id"]
#             bm25_results[patent_id] = hit["_score"]

#         # 3️⃣ Normalisasi BM25 ke skala 0 - 1
#         if bm25_results:
#             min_bm25, max_bm25 = min(bm25_results.values()), max(bm25_results.values())
#             if max_bm25 > min_bm25:
#                 bm25_results = {k: (v - min_bm25) / (max_bm25 - min_bm25) for k, v in bm25_results.items()}
#             else:
#                 bm25_results = {k: 1.0 for k in bm25_results}

#         # 4️⃣ Normalisasi Milvus ke skala 0 - 1
#         if bert_results:
#             min_bert, max_bert = min(bert_results.values()), max(bert_results.values())
#             if max_bert > min_bert:
#                 bert_results = {k: (v - min_bert) / (max_bert - min_bert) for k, v in bert_results.items()}
#             else:
#                 bert_results = {k: 1.0 for k in bert_results}

#         # 5️⃣ Hitung bobot hybrid `c`
#         import numpy as np
#         bm25_std = np.std(list(bm25_results.values())) if bm25_results else 0
#         milvus_std = np.std(list(bert_results.values())) if bert_results else 0

#         # c = milvus_std / (bm25_std + milvus_std) if (bm25_std + milvus_std) > 0 else 0.5
#         c = 0.7
#         if not bm25_results:
#             c = 1.0  # Hanya gunakan Milvus
#         elif not bert_results:
#             c = 0.0  # Hanya gunakan BM25

#         # 6️⃣ Gabungkan skor Hybrid dan terapkan threshold
#         threshold = 0.2  # Hasil di bawah threshold akan diabaikan
#         hybrid_scores: Dict[str, float] = {}

#         for patent_id in set(bm25_results.keys()).union(set(bert_results.keys())):
#             bm25_score = bm25_results.get(patent_id, 0)
#             milvus_score = bert_results.get(patent_id, 0)
#             final_score = (1 - c) * bm25_score + c * milvus_score
#             if final_score >= threshold:
#                 hybrid_scores[patent_id] = final_score

#         # 7️⃣ Urutkan hasil berdasarkan skor Hybrid tertinggi
#         sorted_results = sorted(hybrid_scores.items(), key=lambda x: x[1], reverse=True)

#         # 8️⃣ Batasi hasil sesuai request.limit
#         limit = request.limit if request.limit else len(sorted_results)
#         final_results: List[Dict[str, float]] = [
#             {
#                 "id": patent_id,
#                 "hybrid_score": score,
#                 "bm25_score": bm25_results.get(patent_id, 0),
#                 "milvus_score": bert_results.get(patent_id, 0)
#             }
#             for patent_id, score in sorted_results[:limit]
#         ]
        
#         return {"total": len(final_results), "results": final_results}
    
#     except Exception as e:
#         raise HTTPException(status_code=500, detail=str(e))
    
# @router.post("/hybrid_similarity_64")
# def hybrid_similarity_64(request: SimilarityRequest):
#     try:
#         abstract = preprocess_text(request.abstract)

#         # 1️⃣ Pencarian BM25 di Elasticsearch
#         es_results = search_patents(abstract, from_=0, size=10000)
        
#         if "hits" not in es_results or not isinstance(es_results["hits"], list):
#             raise HTTPException(status_code=500, detail="Invalid Elasticsearch response format")
        
#         bm25_results: Dict[str, float] = {}
#         id_to_patent_id: Dict[str, str] = {}  # Mapping ID Milvus ke patent_id
        
#         for hit in es_results["hits"]:
#             es_id = hit["_source"]["patent_id"]
#             patent_id = hit["_source"]["patent_id"]
#             bm25_results[patent_id] = hit["_score"]
#             id_to_patent_id[es_id] = patent_id
        
#         # 2️⃣ Pencarian Vector Search di Milvus
#         embedding = get_text_embedding_64(abstract)
#         milvus_results = search_vectors_64(embedding, 10000)
        
#         if not milvus_results or not isinstance(milvus_results[0], list):
#             raise HTTPException(status_code=500, detail="Invalid Milvus response format")
        
#         bert_results: Dict[str, float] = {}
#         for hit in milvus_results[0]:
#             entity_id = str(hit.entity.get("id"))
#             patent_id = id_to_patent_id.get(entity_id)
#             if patent_id:
#                 bert_results[patent_id] = 1 - hit.distance  # Konversi dari jarak ke similarity

#         # 3️⃣ Normalisasi BM25 ke skala 0 - 1
#         if bm25_results:
#             min_bm25, max_bm25 = min(bm25_results.values()), max(bm25_results.values())
#             if max_bm25 > min_bm25:
#                 bm25_results = {k: (v - min_bm25) / (max_bm25 - min_bm25) for k, v in bm25_results.items()}
        
#         # 4️⃣ Normalisasi Milvus ke skala 0 - 1
#         if bert_results:
#             min_bert, max_bert = min(bert_results.values()), max(bert_results.values())
#             if max_bert > min_bert:
#                 bert_results = {k: (v - min_bert) / (max_bert - min_bert) for k, v in bert_results.items()}

#         # 5️⃣ Hitung bobot hybrid `c` secara dinamis
#         import numpy as np
#         bm25_std = np.std(list(bm25_results.values())) if bm25_results else 0
#         milvus_std = np.std(list(bert_results.values())) if bert_results else 0
#         c = milvus_std / (bm25_std + milvus_std) if (bm25_std + milvus_std) > 0 else 0.5

#         c = 0.7

#         # 6️⃣ Gabungkan skor Hybrid (0 - 1)
#         hybrid_scores: Dict[str, float] = {}
#         for patent_id in set(bm25_results.keys()).union(set(bert_results.keys())):
#             bm25_score = bm25_results.get(patent_id, 0)
#             milvus_score = bert_results.get(patent_id, 0)
#             final_score = (1 - c) * bm25_score + c * milvus_score
#             hybrid_scores[patent_id] = final_score

#         # 7️⃣ Urutkan hasil berdasarkan skor Hybrid tertinggi
#         sorted_results = sorted(hybrid_scores.items(), key=lambda x: x[1], reverse=True)

#         # 8️⃣ Batasi hasil sesuai request.limit
#         limit = request.limit if request.limit else len(sorted_results)
#         final_results: List[Dict[str, float]] = [
#             {
#                 "id": patent_id,
#                 "hybrid_score": score,
#                 "bm25_score": bm25_results.get(patent_id, 0),
#                 "milvus_score": bert_results.get(patent_id, 0)
#             }
#             for patent_id, score in sorted_results[:limit]
#         ]
        
#         return {"total": len(final_results), "results": final_results}
    
#     except Exception as e:
#         raise HTTPException(status_code=500, detail=str(e))