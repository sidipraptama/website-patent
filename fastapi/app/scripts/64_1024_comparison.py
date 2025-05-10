import requests
import numpy as np
import scipy.stats as stats
from sklearn.metrics import mean_squared_error

# Fungsi untuk mendapatkan hasil dari endpoint
def get_results(endpoint, abstract, limit):
    response = requests.post(endpoint, json={"abstract": abstract, "limit": limit})
    return {item["id"]: item["score"] for item in response.json()}

# Endpoint untuk diuji
endpoint_1024 = "http://127.0.0.1:8000/api/similarity"
endpoint_64 = "http://127.0.0.1:8000/api/similarity64"
abstract_text = "an drum practice pad devided to two part right and left, each side has two piezoelectric sensors to detect the hit position."
limit = 10

# Ambil hasil dari kedua endpoint
results_1024 = get_results(endpoint_1024, abstract_text, limit)
results_64 = get_results(endpoint_64, abstract_text, limit)

# Cari ID yang muncul di kedua hasil
common_ids = set(results_1024.keys()).intersection(set(results_64.keys()))
scores_1024 = np.array([results_1024[id] for id in common_ids])
scores_64 = np.array([results_64[id] for id in common_ids])

# 1. Spearman Rank Correlation
spearman_corr, _ = stats.spearmanr(scores_1024, scores_64)

# 2. Jaccard Similarity untuk Top-N hasil
top_n_1024 = set(results_1024.keys())
top_n_64 = set(results_64.keys())
jaccard_similarity = len(top_n_1024.intersection(top_n_64)) / len(top_n_1024.union(top_n_64))

# 3. Pearson Correlation & MSE
pearson_corr, _ = stats.pearsonr(scores_1024, scores_64)
mse = mean_squared_error(scores_1024, scores_64)

# 4. Kolmogorov-Smirnov (KS) Test
ks_stat, ks_p_value = stats.ks_2samp(scores_1024, scores_64)

# Print hasil evaluasi
print(f"Spearman Rank Correlation: {spearman_corr:.4f}")
print(f"Jaccard Similarity (Top-N Overlap): {jaccard_similarity:.4f}")
print(f"Pearson Correlation: {pearson_corr:.4f}")
print(f"Mean Squared Error (MSE): {mse:.4f}")
print(f"Kolmogorov-Smirnov Test: Statistic={ks_stat:.4f}, p-value={ks_p_value:.4f}")