import os
import numpy as np
from sklearn.preprocessing import normalize
from app.db.milvus import (
    connect_milvus,
    create_index,
    create_collection,
    reset_collection_all,
    check_index,
    get_collection
)

connect_milvus()
def count_data_in_collection():
    # Menghubungkan ke Milvus
    collection = get_collection()  # Pastikan get_collection sudah benar

    if not collection:
        print("❌ Koleksi 'patent_vectors' tidak ditemukan.")
        return

    # Mengecek jumlah entitas dalam koleksi
    count = collection.num_entities
    print(f"Jumlah data dalam koleksi 'patent_vectors': {count}")

count_data_in_collection()

# from app.scripts.create_index import create_index, update_index

# connect_milvus()
# reset_collection_all("patent_vectors")

# connect_milvus()
# create_collection()
# create_index()
# check_index()

# connect_milvus()
# check_index() 