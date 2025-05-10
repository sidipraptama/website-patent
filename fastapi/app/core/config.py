import os
from dotenv import load_dotenv

# Load .env
load_dotenv(override=True)

print("Loading environment variables...")

class Settings:
    APP_NAME: str = os.getenv("APP_NAME", "PatentSimilarityAPI")
    APP_ENV: str = os.getenv("APP_ENV", "development")
    APP_HOST: str = os.getenv("APP_HOST", "0.0.0.0")
    APP_PORT: int = int(os.getenv("APP_PORT", 8000))

    # Database (MySQL)
    DB_HOST: str = os.getenv("DB_HOST", "localhost")
    DB_PORT: int = int(os.getenv("DB_PORT", 3306))
    DB_USER: str = os.getenv("DB_USER", "root")
    DB_PASS: str = os.getenv("DB_PASS", "yourpassword")
    DB_NAME: str = os.getenv("DB_NAME", "patent_similarity")

    # Elasticsearch
    ELASTICSEARCH_HOST: str = os.getenv("ELASTICSEARCH_HOST", "http://localhost:9200")
    ELASTICSEARCH_INDEX: str = os.getenv("ELASTICSEARCH_INDEX", "patents")

    # Milvus
    MILVUS_HOST: str = os.getenv("MILVUS_HOST", "localhost")
    MILVUS_PORT: int = int(os.getenv("MILVUS_PORT", 19530))
    MILVUS_COLLECTION: str = os.getenv("MILVUS_COLLECTION", "patent_vectors")

settings = Settings()
