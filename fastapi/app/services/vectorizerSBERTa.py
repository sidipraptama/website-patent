import string
import re
import torch
import numpy as np
from sklearn.preprocessing import normalize
from sentence_transformers import SentenceTransformer
from nltk.corpus import stopwords
import nltk
from nltk.tokenize import word_tokenize 

nltk.download('punkt')
nltk.download('stopwords')

stop_words = set(stopwords.words('english'))
common_words = set([
    'apparatus', 'means', 'unit',
    'process', 'data', 'information', 'signal', 'control',
    'based', 'provide', 'includes', 'configured', 'plurality',
    'comprising', 'including', 'comprises', 'arranged',
    'perform', 'step', 'component', 'embodiment', 'example',
    'embodiments', 'execution', 'module', 'operation',
    'provides', 'enables', 'generating', 'operations',
    
    'mechanism', 'structure', 'system', 'method',
    'function', 'device', 'application', 'environment',
    'interface', 'interaction', 'elements', 'element',
    'portion', 'part', 'unit', 'object', 'entities',
    'arrangement', 'configuration', 'integration', 'platform',
    'architecture', 'layer', 'level', 'type', 'technique',
    'logic', 'manager', 'controller', 'framework',
    'resources', 'resource', 'processes', 'threads',
    'parameters', 'parameter', 'conditions',
    'states', 'state', 'event', 'events',
    'response', 'request', 'communication', 'exchange',
    'execution', 'initiation', 'trigger', 'triggered',
    'implementation', 'utilization', 'usage',
    'handling', 'managing', 'management',
    'processing', 'monitoring', 'tracking',
    'detecting', 'detection', 'determining', 'determination',
    'calculating', 'calculation', 'identifying', 'identification',
    'storing', 'storage', 'retrieving', 'retrieval',
    'access', 'accessing', 'display', 'displaying',
    'output', 'input', 'transmitting', 'transmission',
    'receiving', 'reception', 'recording',
    'updating', 'modifying', 'changing',
    'synchronization', 'synchronizing', 'initializing',
    'initialization', 'registration',
    'activation', 'deactivation',
    'secure', 'security', 'authorization', 'authentication',
    'sharing', 'collaboration', 'compatibility',
    'optimization', 'performance', 'efficiency',
    'scalability', 'redundancy', 'availability',
    'portability', 'flexibility', 'reliability'
])

all_stopwords = stop_words.union(common_words)

# Load Sentence-BERT model
device = 'cuda' if torch.cuda.is_available() else 'cpu'
# model_sberta = SentenceTransformer('AI-Growth-Lab/PatentSBERTa').to(device)
model_sberta = SentenceTransformer('AAUBS/PatentSBERTa_V2').to(device)

print(f"Model 'PatentSBERTa' berhasil di-load di {device}.")

MAX_SEQ_LENGTH = 512  # Tidak dipakai langsung di SBERT, tapi bisa digunakan untuk preprocessing jika mau

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
    
    # Tokenize and remove stopwords + common_words
    tokens = word_tokenize(text)
    filtered_tokens = [word for word in tokens if word not in all_stopwords]
    
    return ' '.join(filtered_tokens)

# Get embedding (mirip fungsi `get_text_embedding`)
def get_text_embedding_sberta(text):
    text = preprocess_text_sberta(text)
    embedding = model_sberta.encode(text, convert_to_tensor=True).cpu().numpy()
    normalized_embedding = normalize([embedding], norm='l2')
    return normalized_embedding[0]