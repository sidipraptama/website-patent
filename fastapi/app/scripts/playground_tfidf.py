import os
import numpy as np
from sklearn.preprocessing import normalize
from app.db.milvus import (
    connect_milvus,
    create_index,
    # create_index_64,
    # reset_collection_64,
    # create_collection_64,
    create_collection,
    create_collection_tfidf,
    create_index_tfidf,
    check_index_tfidf,
    get_collection_sberta,
    get_collection,
    insert_vectors_batch,
    reset_collection,
    reset_collection_all,
    check_index
)

# connect_milvus()

# def count_data_in_collection():
#     # Menghubungkan ke Milvus
#     collection = get_collection_sberta()  # Pastikan get_collection_sberta sudah benar

#     if not collection:
#         print("❌ Koleksi 'patent_vectors_sberta' tidak ditemukan.")
#         return

#     # Mengecek jumlah entitas dalam koleksi
#     count = collection.num_entities
#     print(f"Jumlah data dalam koleksi 'patent_vectors_sberta': {count}")

# count_data_in_collection()

# from app.scripts.create_index import create_index, update_index

connect_milvus()
reset_collection_all("patent_vectors_tfidf")

# connect_milvus()
create_collection_tfidf()
create_index_tfidf()
check_index_tfidf()

# connect_milvus()
# check_index() 