# from transformers import BertTokenizer
# from sentence_transformers import SentenceTransformer
# import numpy as np
# import os
# import string

# # Konfigurasi
# MODEL_NAME = 'prithivida/bert-for-patents-64d'  # Model dimensi 64 dari Hugging Face
# MAX_SEQ_LENGTH = 512

# # Load model Sentence Transformer
# print("📝 Load model Sentence Transformer...")
# try:
#     model = SentenceTransformer(MODEL_NAME)
#     print("✅ Model berhasil di-load!")
# except Exception as e:
#     print(f"❌ Gagal load model: {e}")
#     raise

# # Fungsi untuk mendapatkan embedding

# def get_text_embedding_64(texts):
#     try:
#         if not texts:
#             print("⚠️ Tidak ada teks yang diberikan.")
#             return None

#         print(f"📝 Menghasilkan embedding untuk {len(texts)} teks...")
#         embeddings = model.encode(texts, batch_size=100, convert_to_numpy=True, show_progress_bar=True)

#         print(f"✅ Embedding berhasil dihasilkan dengan shape: {embeddings.shape}")
#         return embeddings
#     except Exception as e:
#         print(f"❌ Terjadi kesalahan saat menghasilkan embedding: {e}")
#         return None