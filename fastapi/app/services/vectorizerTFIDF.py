import os
import string
import re
import numpy as np
import itertools
import nltk
nltk.download('punkt_tab')
import gensim
from gensim.models import Word2Vec, TfidfModel
from gensim.corpora import Dictionary
from gensim.models.phrases import Phraser
from nltk.tokenize import sent_tokenize
from gensim.utils import simple_preprocess
from nltk.corpus import stopwords

# Load Models
BASE_PATH = "./patent-tfidf-w2v"
w2v_model = Word2Vec.load(f"{BASE_PATH}/w2v.m")
dictionary = Dictionary.load(f"{BASE_PATH}/docs_dict_11_2.d")
tfidf_model = TfidfModel.load(f"{BASE_PATH}/model_tfidf_11_2.m")
bigram_model = Phraser.load(f"{BASE_PATH}/bigram.m")
stop_words = set(stopwords.words('english'))

def preprocess_text_tfidf(text):
    text = text.lower()
    text = text.translate(str.maketrans('', '', string.punctuation))  # Remove punctuation
    sentences = sent_tokenize(text)
    clean = []
    for sent in sentences:
        tokens = simple_preprocess(sent)
        tokens = [word for word in tokens if word not in stop_words]
        clean.append(tokens)
    return clean

def apply_bigram(tokens):
    return [bigram_model[t] for t in tokens]

def get_tfidf(tokens):
    bow = dictionary.doc2bow(itertools.chain(*tokens))
    return dict(tfidf_model[bow])

def compute_vector(tokens):
    tfidf = get_tfidf(tokens)
    vectors = []
    weights = []

    for word_id, weight in tfidf.items():
        word = dictionary[word_id]
        if word in w2v_model.wv:
            vectors.append(w2v_model.wv[word] * weight)
            weights.append(weight)

    if vectors:
        return np.sum(vectors, axis=0) / np.sum(weights)
    else:
        return np.zeros(w2v_model.vector_size)

def get_text_embedding_tfidf(text):
    tokens = preprocess_text_tfidf(text)
    bigrams = apply_bigram(tokens)
    vector = compute_vector(bigrams)
    return vector