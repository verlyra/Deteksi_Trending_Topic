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

def calculate_distance(ngram1_col, ngram2_col):
    """Menghitung jarak antar n-gram"""
    A = np.sum((ngram1_col == 1) & (ngram2_col == 1))
    B = np.sum(ngram1_col)
    C = np.sum(ngram2_col)
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

def perform_clustering(df_matrix, max_iterations=15):
    """Melakukan clustering hierarchical dengan Average Linkage"""
    current_matrix = df_matrix.copy()
    np.fill_diagonal(current_matrix.values, np.inf)
    
    iterations_log = []
    final_cluster = None
    
    for iteration in range(1, max_iterations + 1):
        min_val = current_matrix.min().min()
        
        if min_val == np.inf or current_matrix.empty:
            if not current_matrix.empty and len(current_matrix) == 1:
                final_cluster = current_matrix.index[0]
            break
        
        min_idx = current_matrix.stack().idxmin()
        item1, item2 = min_idx
        new_cluster_name = f"({item1},{item2})"
        
        iterations_log.append({
            'iteration': iteration,
            'merged_items': [str(item1), str(item2)],
            'distance': float(min_val),
            'new_cluster': new_cluster_name
        })
        
        dist_item1 = current_matrix[item1]
        dist_item2 = current_matrix[item2]
        new_distances = (dist_item1 + dist_item2) / 2
        
        current_matrix = current_matrix.drop([item1, item2], axis=0)
        current_matrix = current_matrix.drop([item1, item2], axis=1)
        
        if current_matrix.empty:
            final_cluster = new_cluster_name
            break
        
        new_distances = new_distances.drop([item1, item2], errors='ignore')
        current_matrix.loc[new_cluster_name] = new_distances
        current_matrix[new_cluster_name] = new_distances
        current_matrix.loc[new_cluster_name, new_cluster_name] = np.inf
        
        final_cluster = new_cluster_name
    
    return iterations_log, final_cluster

def build_dendrogram_data(iterations_log, target_ngrams):
    """Membangun data untuk visualisasi dendrogram"""
    # Buat mapping posisi X untuk setiap n-gram
    ngram_positions = {}
    for i, ngram in enumerate(target_ngrams):
        ngram_positions[ngram] = i
    
    dendrogram_nodes = []
    cluster_info = {}
    
    # Inisialisasi leaf nodes
    for ngram in target_ngrams:
        cluster_info[ngram] = {
            'height': 0,
            'left_pos': ngram_positions[ngram],
            'right_pos': ngram_positions[ngram],
            'center_pos': ngram_positions[ngram]
        }
    
    # Process setiap merge
    for merge_data in iterations_log:
        item1 = merge_data['merged_items'][0]
        item2 = merge_data['merged_items'][1]
        distance = merge_data['distance']
        new_cluster = merge_data['new_cluster']
        
        if item1 not in cluster_info or item2 not in cluster_info:
            continue
        
        info1 = cluster_info[item1]
        info2 = cluster_info[item2]
        
        # Hitung posisi untuk cluster baru
        left_pos = min(info1['left_pos'], info2['left_pos'])
        right_pos = max(info1['right_pos'], info2['right_pos'])
        center_pos = (info1['center_pos'] + info2['center_pos']) / 2
        
        # Simpan info cluster baru
        cluster_info[new_cluster] = {
            'height': distance,
            'left_pos': left_pos,
            'right_pos': right_pos,
            'center_pos': center_pos
        }
        
        # Tambahkan ke dendrogram nodes
        dendrogram_nodes.append({
            'iteration': merge_data['iteration'],
            'left_child': item1,
            'right_child': item2,
            'left_height': info1['height'],
            'right_height': info2['height'],
            'merge_height': distance,
            'left_pos': info1['center_pos'],
            'right_pos': info2['center_pos'],
            'merge_pos': center_pos,
            'cluster_name': new_cluster
        })
    
    return dendrogram_nodes

def main():
    """Fungsi utama"""
    # Parse arguments
    max_iterations = int(sys.argv[1]) if len(sys.argv) > 1 else 15
    
    # Gunakan data asli dari notebook
    df_tweets = get_sample_tweets()
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
            row_data.append(1 if ngram in tokens else 0)
        matrix_data.append(row_data)
    
    df_matrix = pd.DataFrame(matrix_data, columns=target_ngrams, index=df_tweets['id'])
    df_matrix_subset = df_matrix.loc[['T1', 'T2', 'T3', 'T4', 'T5']]
    
    # Hitung matriks jarak dan lakukan clustering
    dist_matrix = create_distance_matrix(df_matrix_subset)
    iterations_log, final_cluster = perform_clustering(dist_matrix, max_iterations)
    
    # Buat data dendrogram
    dendrogram_data = build_dendrogram_data(iterations_log, target_ngrams)
    
    # Hitung max height untuk scaling
    max_height = max([node['merge_height'] for node in dendrogram_data]) if dendrogram_data else 1
    
    # Output hasil dalam format JSON
    result = {
        'status': 'success',
        'target_ngrams': target_ngrams,
        'total_ngrams': len(target_ngrams),
        'total_iterations': len(iterations_log),
        'max_height': float(max_height),
        'dendrogram_data': dendrogram_data,
        'final_cluster': final_cluster
    }
    
    print(json.dumps(result, ensure_ascii=False, indent=2))

if __name__ == '__main__':
    main()