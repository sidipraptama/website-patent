from elasticsearch import Elasticsearch
from elasticsearch.helpers import bulk, BulkIndexError
from typing import List
import re
import string
import spacy
import os

# Elasticsearch configuration
ELASTICSEARCH_HOST = os.getenv("ELASTICSEARCH_HOST", "http://localhost:9200")
ELASTICSEARCH_INDEX = os.getenv("ELASTICSEARCH_INDEX", "patents")

# Connect to Elasticsearch
es = Elasticsearch(ELASTICSEARCH_HOST, timeout=60)

nlp = spacy.load("en_core_web_sm")

# Create index if it doesn't exist
# Create index if it doesn't exist
def create_index(index_name=ELASTICSEARCH_INDEX):
    """
    Create an Elasticsearch index if it does not exist.
    Supports both text search and keyword filtering.
    """
    if not es.indices.exists(index=index_name):
        es.indices.create(
            index=index_name,
            body={
                "mappings": {
                    "properties": {
                        "patent_id": {"type": "keyword"},  # Unique ID
                        "patent_type": {
                            "type": "text",
                            "fields": {
                                "keyword": {"type": "keyword"}
                            }
                        },
                        "patent_date": {"type": "date"},
                        "patent_title": {
                            "type": "text",
                            "fields": {
                                "keyword": {"type": "keyword"}
                            }
                        },
                        "wipo_kind": {"type": "keyword"},
                        "num_claims": {"type": "integer"},
                        "patent_abstract": {
                            "type": "text",
                            "fields": {
                                "keyword": {"type": "keyword"}
                            }
                        }
                    }
                }
            }
        )
        print(f"✅ Index '{index_name}' created successfully!")
    else:
        print(f"⚠️ Index '{index_name}' already exists.")

# Insert a single patent document
def insert_patent(data, index_name=ELASTICSEARCH_INDEX):
    response = es.index(index=index_name, document=data)
    return response

# Bulk insert multiple patents
def bulk_insert_patents(es, data_list, index_name="patents"):
    """
    Bulk insert multiple patents into Elasticsearch.
    Ensures 'patent_id' is unique by using it as _id.
    """
    actions = [
        {
            "_op_type": "index",  # Overwrite if same ID exists
            "_index": index_name,
            "_id": data.get("patent_id"),  # Use patent_id as document ID
            "_source": data
        }
        for data in data_list
        if isinstance(data, dict) and "patent_id" in data  # Ensure ID exists
    ]

    try:
        success, failed = bulk(es, actions, raise_on_error=False, ignore_status=[400, 409])
        print(f"✅ {success} documents inserted successfully!")
        if failed:
            print(f"⚠️ {len(failed)} documents failed to insert.")
            for error in failed[:5]:  # Print first 5 errors
                print(error)
    except BulkIndexError as e:
        print(f"❌ Bulk index error: {e}")
        for error in e.errors[:5]:  # Print first 5 errors
            print(error)

# Search patents by title or abstract
def search_patents(query, from_=0, size=10, sort=None, index_name=ELASTICSEARCH_INDEX):
    body = {
        "from": from_,
        "size": size,
        "query": {
            "multi_match": {
                "query": query,
                "fields": ["patent_title", "patent_abstract"]
            }
        }
    }
    if sort:
        body["sort"] = sort

    response = es.search(index=index_name, body=body)
    return response['hits']


def get_latest_patents(from_=0, size=10, sort_order="desc", index_name=ELASTICSEARCH_INDEX):
    response = es.search(
        index=index_name,
        body={
            "from": from_,
            "size": size,
            "sort": [
                {"patent_date": {"order": sort_order}}
            ],
            "query": {"match_all": {}}
        }
    )
    return response['hits']

def get_patents_by_ids(patent_ids, index_name=ELASTICSEARCH_INDEX):
    response = es.search(
        index=index_name,
        body={
            "size": len(patent_ids),
            "query": {
                "terms": {"patent_id": patent_ids}
            }
        }
    )

    print("Elasticsearch Response:", response["hits"])

    return response["hits"]

def preprocess_text(text):
    """
    Melakukan preprocessing pada query sebelum pencarian di Elasticsearch.
    - Lowercasing
    - Menghapus tanda baca
    - Menghapus stopword
    - Lemmatization
    """
    text = text.lower()  # Lowercasing
    text = re.sub(f"[{string.punctuation}]", " ", text)  # Hapus tanda baca
    doc = nlp(text)  # NLP processing
    
    processed_text = " ".join(
        token.lemma_ for token in doc if not token.is_stop and not token.is_punct
    )  # Lemmatization + stopword removal
    
    return processed_text.strip()

def search_patents_by_ids(query, patent_ids: List[str], from_=0, size=10, index_name="patents"):
    """
    Melakukan pencarian di Elasticsearch dengan preprocessing query sebelum pencarian.
    """
    if not patent_ids:
        return {"hits": []}  # Jika tidak ada ID yang dicari, langsung return kosong
    
    processed_query = preprocess_text(query)  # Preprocessing query
    
    response = es.search(
        index=index_name,
        body={
            "from": from_,
            "size": size,
            "query": {
                "bool": {
                    "must": {
                        "multi_match": {
                            "query": processed_query,
                            "fields": ["patent_title", "patent_abstract"]
                        }
                    },
                    "filter": {
                        "terms": {"patent_id": patent_ids}  # Filter hanya pada patent_id yang diberikan
                    }
                }
            }
        }
    )
    
    print("Processed Query:", processed_query)
    print("Elasticsearch Response:", response)

    return response['hits']

def get_patent_statistics(index_name=ELASTICSEARCH_INDEX):
    response = es.search(
        index=index_name,
        size=0,
        track_total_hits=True,
        body={
            "aggs": {
                "by_type": {
                    "terms": {
                        "field": "patent_type.keyword",
                        "size": 10
                    }
                }
            }
        }
    )

    total = response["hits"]["total"]["value"]
    buckets = response["aggregations"]["by_type"]["buckets"]

    return {
        "total": total,
        "by_patent_type": [
            {"type": b["key"], "count": b["doc_count"]}
            for b in buckets
        ]
    }

def get_patent_count_last_10_years(index_name=ELASTICSEARCH_INDEX):
    """
    Mengambil jumlah paten per tahun selama 10 tahun terakhir dikurangi 1 dari tahun terakhir.
    Misal tahun terakhir = 2024, maka akan ambil dari 2013–2023.
    """
    # Step 1: Dapatkan tahun terakhir
    response = es.search(
        index=index_name,
        size=1,
        sort=[{"patent_date": {"order": "desc"}}],
        _source=["patent_date"]
    )

    if not response["hits"]["hits"]:
        return []

    latest_date = response["hits"]["hits"][0]["_source"]["patent_date"]
    latest_year = int(latest_date[:4])
    max_year = latest_year - 1
    min_year = max_year - 9

    # Step 2: Agregasi paten per tahun (10 tahun terakhir - 1)
    aggs_response = es.search(
        index=index_name,
        size=0,
        body={
            "query": {
                "range": {
                    "patent_date": {
                        "gte": f"{min_year}-01-01",
                        "lte": f"{max_year}-12-31"
                    }
                }
            },
            "aggs": {
                "per_year": {
                    "date_histogram": {
                        "field": "patent_date",
                        "calendar_interval": "year",
                        "format": "yyyy",
                        "min_doc_count": 0
                    }
                }
            }
        }
    )

    buckets = aggs_response["aggregations"]["per_year"]["buckets"]

    return [
        {"year": b["key_as_string"], "count": b["doc_count"]}
        for b in buckets
    ]