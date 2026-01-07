import sys
import json
import pandas as pd
import numpy as np
import math

def get_sample_tweets():
    """Data tweet asli sesuai notebook (Jokowi, Ahok, Natuna)"""
    data_tweets = [
        {'id': 'T1', 'time_slot': 1, 'text': 'Ahok modus manipulasi ktp'},
        {'id': 'T2', 'time_slot': 1, 'text': 'Ahok modus pembuatan sertifikat'},
        {'id': 'T3', 'time_slot': 1, 'text': 'Jokowi natuna ratas imam bonjol'},
        {'id': 'T4', 'time_slot': 2, 'text': 'Jokowi rapat natuna'},
        {'id': 'T5', 'time_slot': 2, 'text': 'TNI jamin keamanan Jokowi Natuna'},
    ]
    return pd.DataFrame(data_tweets)

def preprocess_text(text):
    """Preprocessing sederhana"""
    text = str(text).lower()
    tokens = text.split()
    return tokens

def calculate_df_idf(term, current_slot_df, total_slots, previous_dfs, is_entity=False):
    """Menghitung DF-IDF Temporal"""
    df_current = current_slot_df
    sum_previous = sum(previous_dfs)
    boost = 1.5 if is_entity else 1.0
    
    # Hindari log(0)
    if sum_previous == 0:
        denominator = 1
    else:
        denominator = math.log((sum_previous / total_slots) + 1) + 1
    
    score = (df_current / denominator) * boost
    return score

def calculate_distance(ngram1_col, ngram2_col):
    """Menghitung jarak antar n-gram berdasarkan rumus di PDF"""
    # A: Intersection (keduanya bernilai 1)
    A = np.sum((ngram1_col == 1) & (ngram2_col == 1))
    
    # B: Sum of ngram1
    B = np.sum(ngram1_col)
    
    # C: Sum of ngram2
    C = np.sum(ngram2_col)
    
    # Hindari pembagian dengan nol
    min_bc = min(B, C)
    if min_bc == 0:
        return 1.0
    
    distance = 1 - (A / min_bc)
    return distance

def create_distance_matrix(df_matrix):
    """Membuat matriks jarak awal"""
    ngrams_list = df_matrix.columns.tolist()
    n = len(ngrams_list)
    dist_matrix = pd.DataFrame(index=ngrams_list, columns=ngrams_list, dtype=float)
    
    for i in range(n):
        for j in range(n):
            if i == j:
                dist_matrix.iloc[i, j] = 0.0
            else:
                col_i = df_matrix.iloc[:, i]
                col_j = df_matrix.iloc[:, j]
                dist = calculate_distance(col_i, col_j)
                dist_matrix.iloc[i, j] = float(dist)
    
    return dist_matrix

def matrix_to_dict(matrix):
    """Konversi DataFrame matrix ke dictionary untuk JSON"""
    result = {
        'index': list(matrix.index),
        'columns': list(matrix.columns),
        'data': []
    }
    
    for i, row_name in enumerate(matrix.index):
        row_data = {}
        for j, col_name in enumerate(matrix.columns):
            value = matrix.iloc[i, j]
            if np.isinf(value):
                row_data[col_name] = 'inf'
            else:
                row_data[col_name] = float(value)
        result['data'].append({
            'row_name': row_name,
            'values': row_data
        })
    
    return result

def perform_clustering(df_matrix, max_iterations=15):
    """Melakukan clustering hierarchical dengan Average Linkage"""
    current_matrix = df_matrix.copy()
    np.fill_diagonal(current_matrix.values, np.inf)
    
    iterations_log = []
    final_cluster = None
    
    for iteration in range(1, max_iterations + 1):
        # Cari nilai minimum
        min_val = current_matrix.min().min()
        
        if min_val == np.inf or current_matrix.empty:
            if not current_matrix.empty and len(current_matrix) == 1:
                final_cluster = current_matrix.index[0]
            break
        
        # Dapatkan pasangan dengan jarak minimum
        min_idx = current_matrix.stack().idxmin()
        item1, item2 = min_idx
        
        new_cluster_name = f"({item1},{item2})"
        
        # Simpan matriks SEBELUM update
        matrix_before = current_matrix.copy()
        
        # Log iterasi dengan matriks
        iterations_log.append({
            'iteration': iteration,
            'merged_items': [str(item1), str(item2)],
            'distance': float(min_val),
            'new_cluster': new_cluster_name,
            'matrix_size': current_matrix.shape[0],
            'matrix_before': matrix_to_dict(matrix_before)
        })
        
        # Hitung jarak baru dengan Average Linkage
        dist_item1 = current_matrix[item1]
        dist_item2 = current_matrix[item2]
        new_distances = (dist_item1 + dist_item2) / 2
        
        # Hapus item lama
        current_matrix = current_matrix.drop([item1, item2], axis=0)
        current_matrix = current_matrix.drop([item1, item2], axis=1)
        
        # Jika matriks kosong, selesai
        if current_matrix.empty:
            final_cluster = new_cluster_name
            break
        
        # Tambah cluster baru
        new_distances = new_distances.drop([item1, item2], errors='ignore')
        current_matrix.loc[new_cluster_name] = new_distances
        current_matrix[new_cluster_name] = new_distances
        current_matrix.loc[new_cluster_name, new_cluster_name] = np.inf
        
        final_cluster = new_cluster_name
    
    # Tambahkan matriks akhir jika ada
    if not current_matrix.empty:
        iterations_log.append({
            'iteration': 'final',
            'merged_items': [],
            'distance': 0,
            'new_cluster': final_cluster,
            'matrix_size': current_matrix.shape[0],
            'matrix_before': matrix_to_dict(current_matrix)
        })
    
    return iterations_log, final_cluster

def main():
    """Fungsi utama"""
    # Parse arguments
    time_slots = int(sys.argv[1]) if len(sys.argv) > 1 else 2
    max_iterations = int(sys.argv[2]) if len(sys.argv) > 2 else 15
    use_entity_boost = bool(int(sys.argv[3])) if len(sys.argv) > 3 else True
    
    # Gunakan data asli dari notebook
    df_tweets = get_sample_tweets()
    
    # Preprocessing
    df_tweets['tokens'] = df_tweets['text'].apply(preprocess_text)
    
    # Target n-grams sesuai PDF
    target_ngrams = [
        'ahok', 'modus', 'manipulasi', 'ktp', 'pembuatan',
        'sertifikat', 'jokowi', 'natuna', 'ratas', 'imam',
        'bonjol', 'rapat', 'tni', 'jamin', 'keamanan'
    ]
    
    # Buat binary matrix
    matrix_data = []
    for index, row in df_tweets.iterrows():
        row_data = []
        tokens = row['tokens']
        for ngram in target_ngrams:
            if ngram in tokens:
                row_data.append(1)
            else:
                row_data.append(0)
        matrix_data.append(row_data)
    
    df_matrix = pd.DataFrame(matrix_data, columns=target_ngrams, index=df_tweets['id'])
    
    # Fokus pada T1-T5 sesuai PDF
    df_matrix_subset = df_matrix.loc[['T1', 'T2', 'T3', 'T4', 'T5']]
    
    # Hitung DF-IDF scores
    df_idf_scores = []
    
    # Entity words (nama orang, tempat, organisasi)
    entity_words = ['ahok', 'jokowi', 'natuna', 'tni', 'imam', 'bonjol']
    
    for term in target_ngrams:
        # Hitung document frequency per slot
        slot_dfs = []
        for slot in [1, 2]:
            slot_df = df_tweets[df_tweets['time_slot'] == slot]
            term_count = sum([1 for tokens in slot_df['tokens'] if term in tokens])
            slot_dfs.append(term_count)
        
        # Hitung score untuk slot terakhir (slot 2)
        current_df = slot_dfs[-1] if slot_dfs[-1] > 0 else 0
        previous_dfs = slot_dfs[:-1] if len(slot_dfs) > 1 else [0]
        
        # Cek apakah entity
        is_entity = use_entity_boost and (term in entity_words)
        
        if current_df > 0:  # Hanya hitung jika ada di slot current
            score = calculate_df_idf(term, current_df, time_slots, previous_dfs, is_entity)
        else:
            score = 0.0
        
        df_idf_scores.append({
            'term': term,
            'score': float(score),
            'is_entity': is_entity,
            'slot1_freq': slot_dfs[0],
            'slot2_freq': slot_dfs[1] if len(slot_dfs) > 1 else 0
        })
    
    # Sort by score descending
    df_idf_scores = sorted(df_idf_scores, key=lambda x: x['score'], reverse=True)
    
    # Hitung matriks jarak
    dist_matrix = create_distance_matrix(df_matrix_subset)
    
    # Konversi matriks jarak awal ke format yang bisa di-serialize
    initial_distance_matrix = matrix_to_dict(dist_matrix)
    
    # Lakukan clustering
    iterations_log, final_cluster = perform_clustering(dist_matrix, max_iterations)
    
    # Buat data tweet untuk ditampilkan
    tweets_data = []
    for index, row in df_tweets.iterrows():
        tweets_data.append({
            'id': row['id'],
            'time_slot': int(row['time_slot']),
            'text': row['text'],
            'tokens': row['tokens']
        })
    
    # Output hasil dalam format JSON
    result = {
        'status': 'success',
        'tweets_data': tweets_data,
        'total_tweets': len(df_tweets),
        'time_slots': time_slots,
        'target_ngrams': target_ngrams,
        'binary_matrix': df_matrix_subset.to_dict(),
        'initial_distance_matrix': initial_distance_matrix,
        'df_idf_scores': df_idf_scores,
        'clustering_iterations': iterations_log,
        'final_cluster': final_cluster
    }
    
    print(json.dumps(result, ensure_ascii=False, indent=2))

if __name__ == '__main__':
    main()