import os
import logging
from dotenv import load_dotenv
from typing import List
from fastapi import APIRouter, HTTPException, Request
from pydantic import BaseModel
from typing import Optional
from app.db.elastic import search_patents, get_latest_patents, get_patents_by_ids, get_patent_statistics, get_patent_count_last_10_years

router = APIRouter()

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

load_dotenv(dotenv_path="/root/website-patent/fastapi/.env", override=True)

API_KEY = os.getenv("API_KEY")
logger.info(f"Loaded API_KEY: {API_KEY}")

class PatentSearchRequest(BaseModel):
    query: Optional[str] = None
    page: int = 1
    size: int = 10
    sort_by: Optional[str] = "relevance"

class PatentByIdsRequest(BaseModel):
    patent_ids: List[str]
 
@router.post("/patents/search")
async def search_patents_endpoint(request: Request, body: PatentSearchRequest):
    api_key = request.headers.get("Authorization")
    if api_key != f"Bearer {API_KEY}":
        raise HTTPException(status_code=403, detail="Unauthorized")

    try:
        from_ = (body.page - 1) * body.size
        sort_by = body.sort_by or "relevance"

        if body.query:
            if sort_by == "newest":
                sort = [{"patent_date": {"order": "desc"}}]
            elif sort_by == "oldest":
                sort = [{"patent_date": {"order": "asc"}}]
            else:
                sort = None  # default relevance

            response = search_patents(body.query, from_, body.size, sort)
        else:
            # Tidak ada query → tetap pakai sort by date (newest/oldest)
            if sort_by == "oldest":
                sort_order = "asc"
            else:
                sort_order = "desc"
            response = get_latest_patents(from_, body.size, sort_order)

        if "hits" not in response or not isinstance(response["hits"], list):
            raise HTTPException(status_code=500, detail="Response does not contain valid 'hits' key")

        return {
            "total": response.get("total", {}).get("value", 0),
            "page": body.page,
            "size": body.size,
            "results": response["hits"]
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    
@router.post("/patents/by_ids")
async def get_patents_by_ids_endpoint(request: Request, body: PatentByIdsRequest):
    api_key = request.headers.get("Authorization")
    if api_key != f"Bearer {API_KEY}":
        raise HTTPException(status_code=403, detail="Unauthorized")
    
    try:
        response = get_patents_by_ids(body.patent_ids)
        return {
            "total": response.get("total", {}).get("value", len(response.get("hits", []))),
            "results": response.get("hits", [])
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    
@router.get("/patents/statistics")
async def get_patent_statistics_endpoint(request: Request):
    api_key = request.headers.get("Authorization")
    if api_key != f"Bearer {API_KEY}":
        raise HTTPException(status_code=403, detail="Unauthorized")
    
    try:
        stats = get_patent_statistics()
        return stats
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    
@router.get("/patents/statistics/yearly")
async def get_patent_yearly_statistics_endpoint(request: Request):
    api_key = request.headers.get("Authorization")
    if api_key != f"Bearer {API_KEY}":
        raise HTTPException(status_code=403, detail="Unauthorized")

    try:
        stats = get_patent_count_last_10_years()
        return stats
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
