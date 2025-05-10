from transformers import BertTokenizer
from sklearn.preprocessing import normalize
import tensorflow as tf
import os
# curl -O https://raw.githubusercontent.com/google-research/bert/master/tokenization.py
import tokenization
from sklearn.decomposition import PCA
import string

# Inisialisasi PCA di luar biar nggak re-train tiap kali dipanggil
# REDUCED_DIM = 256
# pca = PCA(n_components=REDUCED_DIM)

# Patch tokenization to fix TensorFlow 2.x compatibility
if hasattr(tf, 'io'):
    tokenization.tf = tf
    tokenization.gfile = tf.io.gfile

# Ensure tokenization.py uses tf.io.gfile
if hasattr(tokenization, 'tf'):
    tokenization.gfile = tokenization.tf.io.gfile

# Fix the tf.gfile.GFile reference inside tokenization.py
if hasattr(tokenization, 'load_vocab'):
    def patched_load_vocab(vocab_file):
        import collections
        vocab = collections.OrderedDict()
        index = 0
        with tf.io.gfile.GFile(vocab_file, "r") as reader:
            while True:
                token = reader.readline()
                if not token:
                    break
                token = token.strip()
                vocab[token] = index
                index += 1
        return vocab
    tokenization.load_vocab = patched_load_vocab

MODEL_DIR = './bert_for_patents_model'  # Path model BERT kamu
VOCAB_FILE = './bert_for_patents_model/bert_for_patents_vocab_39k.txt'  # Pakai vocab.txt
MAX_SEQ_LENGTH = 512

print("Load tokenizer...")
tokenizer = tokenization.FullTokenizer(vocab_file=VOCAB_FILE, do_lower_case=True)
print("Tokenizer berhasil di-load!")

print("Load model BERT...")
loaded_model = tf.compat.v2.saved_model.load(MODEL_DIR, tags=['serve'])
print("Model berhasil di-load!")

infer = loaded_model.signatures['serving_default']

# Mean pooling layer for combining
pooling = tf.keras.layers.GlobalAveragePooling1D()

# Tokenisasi dan preprocessing
def get_bert_token_input(texts):
    input_ids = []
    input_mask = []
    segment_ids = []

    for text in texts:
        tokens = tokenizer.tokenize(text)
        if len(tokens) > MAX_SEQ_LENGTH - 2:
            tokens = tokens[0:(MAX_SEQ_LENGTH - 2)]
        tokens = ['[CLS]'] + tokens + ['[SEP]']

        ids = tokenizer.convert_tokens_to_ids(tokens)
        token_pad = MAX_SEQ_LENGTH - len(ids)
        input_mask.append([1] * len(ids) + [0] * token_pad)
        input_ids.append(ids + [0] * token_pad)
        segment_ids.append([0] * MAX_SEQ_LENGTH)

    return {
        'segment_ids': tf.convert_to_tensor(segment_ids, dtype=tf.int64),
        'input_mask': tf.convert_to_tensor(input_mask, dtype=tf.int64),
        'input_ids': tf.convert_to_tensor(input_ids, dtype=tf.int64),
        'mlm_positions': tf.convert_to_tensor([], dtype=tf.int64)
    }

# Dapatkan embedding
def get_text_embedding(text):
    inputs = get_bert_token_input([text])
    response = infer(**inputs)
    embeddings = pooling(tf.reshape(response['encoder_layer'], shape=[1, -1, 1024]))
    embeddings_np = embeddings.numpy()

    if len(embeddings_np.shape) == 3:
        embeddings_np = embeddings_np.reshape(embeddings_np.shape[0], -1)

    normalized_embedding = normalize(embeddings_np, norm='l2')

    return normalized_embedding[0]

def preprocess_text(text):
    text = text.lower()  # Ubah ke huruf kecil
    text = text.translate(str.maketrans('', '', string.punctuation))  # Hapus tanda baca
    return text

# from transformers import BertTokenizer
# from sklearn.decomposition import PCA
# import tensorflow as tf
# import os
# import numpy as np
# import joblib  # Pakai joblib buat load PCA yang udah disimpan
# import tokenization

# # Konfigurasi
# REDUCED_DIM = 128
# PCA_FILE = './pca_model.joblib'
# MODEL_DIR = './bert_for_patents_model'  # Path model BERT kamu
# VOCAB_FILE = './bert_for_patents_model/bert_for_patents_vocab_39k.txt'  # Pakai vocab.txt
# MAX_SEQ_LENGTH = 512

# # Load PCA yang sudah dilatih sebelumnya
# if os.path.exists(PCA_FILE):
#     pca = joblib.load(PCA_FILE)
#     print("🎉 Menggunakan PCA yang sudah dilatih sebelumnya.")
# else:
#     raise FileNotFoundError(f"❌ PCA model tidak ditemukan di {PCA_FILE}")

# # Patch tokenization buat kompatibilitas TensorFlow 2.x
# if hasattr(tf, 'io'):
#     tokenization.tf = tf
#     tokenization.gfile = tf.io.gfile

# # Fix load_vocab di tokenization.py
# if hasattr(tokenization, 'load_vocab'):
#     def patched_load_vocab(vocab_file):
#         import collections
#         vocab = collections.OrderedDict()
#         index = 0
#         with tf.io.gfile.GFile(vocab_file, "r") as reader:
#             while True:
#                 token = reader.readline()
#                 if not token:
#                     break
#                 token = token.strip()
#                 vocab[token] = index
#                 index += 1
#         return vocab
#     tokenization.load_vocab = patched_load_vocab

# # Load tokenizer
# print("📝 Load tokenizer...")
# tokenizer = tokenization.FullTokenizer(vocab_file=VOCAB_FILE, do_lower_case=True)
# print("✅ Tokenizer berhasil di-load!")

# # Load model BERT
# print("📝 Load model BERT...")
# loaded_model = tf.compat.v2.saved_model.load(MODEL_DIR, tags=['serve'])
# print("✅ Model BERT berhasil di-load!")

# # Inferensi BERT
# infer = loaded_model.signatures['serving_default']

# # Mean pooling buat ambil representasi embedding
# pooling = tf.keras.layers.GlobalAveragePooling1D()

# # Tokenisasi dan preprocessing
# def get_bert_token_input(texts):
#     input_ids = []
#     input_mask = []
#     segment_ids = []

#     for text in texts:
#         tokens = tokenizer.tokenize(text)
#         if len(tokens) > MAX_SEQ_LENGTH - 2:
#             tokens = tokens[:MAX_SEQ_LENGTH - 2]
#         tokens = ['[CLS]'] + tokens + ['[SEP]']

#         ids = tokenizer.convert_tokens_to_ids(tokens)
#         token_pad = MAX_SEQ_LENGTH - len(ids)
#         input_mask.append([1] * len(ids) + [0] * token_pad)
#         input_ids.append(ids + [0] * token_pad)
#         segment_ids.append([0] * MAX_SEQ_LENGTH)

#     return {
#         'segment_ids': tf.convert_to_tensor(segment_ids, dtype=tf.int64),
#         'input_mask': tf.convert_to_tensor(input_mask, dtype=tf.int64),
#         'input_ids': tf.convert_to_tensor(input_ids, dtype=tf.int64),
#         'mlm_positions': tf.convert_to_tensor([], dtype=tf.int64)
#     }

# # Dapatkan embedding dari teks dan reduksi dengan PCA
# def get_text_embedding(text):
#     # Tokenisasi input
#     inputs = get_bert_token_input([text])
#     response = infer(**inputs)
    
#     # Pooling buat dapetin representasi akhir (shape: [1, seq_len, 1024])
#     embeddings = pooling(tf.reshape(response['encoder_layer'], shape=[1, -1, 1024]))

#     # Konversi ke numpy dan cek shape
#     embeddings = embeddings.numpy()

#     # Pastikan PCA sudah terlatih
#     if hasattr(pca, 'transform'):
#         # Transformasi PCA ke dimensi yang lebih kecil
#         reduced_embedding = pca.transform(embeddings)
#         print(f"✅ Embedding berhasil direduksi ke shape: {reduced_embedding.shape}")
#     else:
#         raise RuntimeError("❌ PCA belum terlatih dengan benar.")

#     return reduced_embedding[0]  # Output jadi shape (REDUCED_DIM,)