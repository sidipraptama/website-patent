# Website Patent

Proyek ini merupakan website untuk melakukan **Prior Art Search** dan **Similarity Check** pada paten global menggunakan **Sentence-BERT** (model PatentSBERTa) dan **Cosine Similarity**.  
Project terdiri dari:
- **Laravel**: Frontend + Backend website
- **FastAPI**: Layanan API untuk pengolahan teks dan similarity
- **Milvus**: Vector database untuk penyimpanan embedding
- **Elasticsearch**: Penyimpanan meta data paten
- **Docker**: Manajemen layanan (Laravel, FastAPI, Nginx, Milvus, MinIO, dll)

## Model
Model yang digunakan untuk embedding:
- [PatentSBERTa_V2](https://huggingface.co/AAUBS/PatentSBERTa_V2)  
Simpan model ini ke folder:  
```
fastapi/PatentSBERTa_V2
```

## Dataset
- **Meta Data (untuk Elasticsearch)**: [Cleaned Patent 2019–2024](https://www.kaggle.com/datasets/sidipraptama/cleaned-patent-2019-2024)
- **Vector Data (untuk Milvus)**: [SBERTa 2019–2024](https://www.kaggle.com/datasets/sidipraptama/sberta-2019-2024)

Download dataset tersebut dan simpan di folder:
```
fastapi/data
```

## Struktur Project
```
website-patent/
├── certbot/                     # Sertifikat SSL
├── fastapi/                     # API service (Python)
│   ├── PatentSBERTa_V2/         # Model SBERT
│   ├── app/                     # Source code FastAPI
│   │   ├── scripts/             # Script data processing
│   │   │   └── server/          # Script Milvus & Elasticsearch
│   │   │       ├── create_collection.py
│   │   │       ├── insert_data.py
│   │   │       ├── insert_meta_data.py
│   │   │       └── playground_SBERTa.py   # Script eksperimen Milvus
│   └── requirements.txt         # Dependencies FastAPI
├── laravel-website/             # Website Laravel
├── nginx/                       # Konfigurasi Nginx
├── volumes/                     # Volume untuk Docker
├── .env                         # Environment variables
├── docker-compose.yml           # Docker services
└── sh/                          # Script tambahan
```

## Setup & Development

### 1. Clone repository
```
git clone https://github.com/<username>/<repo>.git
cd website-patent
```

### 2. Setup Laravel
Masuk ke container Laravel:
```
docker exec -it laravel_app sh
```
Jalankan development server:
```
php artisan serve --host=0.0.0.0 --port=8000
npm run dev -- --host
```

### 3. Setup FastAPI
Masuk ke folder FastAPI:
```
cd fastapi
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
```

### 4. Siapkan Dataset
Download dataset dari Kaggle:
- [Cleaned Patent 2019–2024](https://www.kaggle.com/datasets/sidipraptama/cleaned-patent-2019-2024) (untuk Elasticsearch)
- [SBERTa 2019–2024](https://www.kaggle.com/datasets/sidipraptama/sberta-2019-2024) (untuk Milvus)

Ekstrak dan simpan ke:
```
fastapi/data
```

### 5. Buat Collection & Insert Data ke Milvus
Jalankan script:
```
python -m app.scripts.server.create_collection
python -m app.scripts.server.insert_data
```

### 6. Insert Meta Data ke Elasticsearch
```
python -m app.scripts.server.insert_meta_data
```

### 7. Restart Milvus (Jika diperlukan reset)
```
docker rm -f milvus-etcd milvus-minio milvus-standalone

docker volume rm \
  website-patent_minio_data1 \
  website-patent_minio_data2 \
  website-patent_minio_data3 \
  website-patent_minio_data4

docker-compose up -d --build etcd minio standalone
```

### 8. Jalankan FastAPI
```
uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload
```

### 9. Script Eksperimen (Opsional)
Untuk menguji query atau eksplorasi Milvus:
```
python -m app.scripts.server.playground_SBERTa
```

### 10. Development Laravel
```
npm run dev -- --host
```

## Pindah Komputer
Jika memindahkan project ke komputer baru:
```
docker-compose build
cd fastapi
rm -rf venv
python3.11 -m venv venv
pip install -r requirements.txt
# Jalankan script sesuai kebutuhan (create_collection, insert_data, insert_meta_data)
```

## License
MIT License
