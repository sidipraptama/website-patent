import string
import re
import torch
import numpy as np
from sklearn.preprocessing import normalize
from sentence_transformers import SentenceTransformer
from nltk.tokenize import word_tokenize 

# Load Sentence-BERT model
device = 'cuda' if torch.cuda.is_available() else 'cpu'
model_path = './PatentSBERTa_V2'
# model_sberta = SentenceTransformer('AI-Growth-Lab/PatentSBERTa').to(device)
model_sberta = SentenceTransformer(model_path).to(device)

print(f"Model 'PatentSBERTa' berhasil di-load di {device}.")

# Preprocessing text (mirip dengan fungsi `preprocess_text`)
def preprocess_text_sberta(text):
    text = text.lower()  # Lowercase
    text = text.translate(str.maketrans('', '', string.punctuation))  # Remove punctuation
    text = re.sub(r'\s+', ' ', text).strip()  # Remove extra spaces
    return text

def preprocess_text_sberta(text):
    # Lowercase and remove non-letter characters
    text = text.lower()
    text = re.sub(r'[^a-z\s]', '', text)
    text = re.sub(r'\s+', ' ', text).strip()
    
    # Tokenize (tanpa hapus stopwords)
    tokens = word_tokenize(text)
    
    return ' '.join(tokens)

# Get embedding (mirip fungsi `get_text_embedding`)
def get_text_embedding_sberta(text):
    text = preprocess_text_sberta(text)
    embedding = model_sberta.encode(text, convert_to_tensor=True).cpu().numpy()
    normalized_embedding = normalize([embedding], norm='l2')
    return normalized_embedding[0]