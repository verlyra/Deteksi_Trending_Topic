<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Tarik Data Tweet - Dark Mode</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #121212; /* Latar belakang gelap */
            color: #e0e0e0; /* Warna teks terang */
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .form-container {
            background-color: #1e1e1e; /* Warna kartu sedikit lebih terang */
            padding: 40px;
            border-radius: 12px;
            border: 1px solid #333;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            box-sizing: border-box;
        }

        .form-container h1 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #a0a0a0;
            font-weight: 500;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            background-color: #2c2c2c;
            border: 1px solid #444;
            border-radius: 8px;
            font-size: 16px;
            color: #e0e0e0;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-group input[type="text"]::placeholder {
            color: #666;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #f5a941;
            box-shadow: 0 0 0 3px rgba(245, 169, 65, 0.25);
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .button-group button, .button-group input[type="submit"] {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .btn-primary {
            background-color: #f5a941;
            color: #121212;
        }
        .btn-primary:hover {
            background-color: #ffbe5b;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #333;
            color: #e0e0e0;
        }
        .btn-secondary:hover {
            background-color: #444;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <h1>Tarik Data Tweet</h1>
        <form action="tampilTarikData.php" method="POST">
            <div class="form-group">
                <label for="filename">Nama File</label>
                <input type="text" id="filename" name="filename" placeholder="Contoh: gempa.csv" required>
            </div>
            
            <div class="form-group">
                <label for="keyword">Kata Kunci</label>
                <input type="text" id="keyword" name="keyword" placeholder="Contoh: gempa lang:id" required>
            </div>
            
            <div class="form-group">
                <label for="limit">Jumlah Data (Limit)</label>
                <input type="text" id="limit" name="limit" placeholder="Contoh: 100" required>
            </div>

            <div class="button-group">
                <input type="submit" value="Tarik Data" class="btn-primary">
                <a href="dasboard.php" style="flex: 1; text-decoration: none;">
                    <button type="button" class="btn-secondary" style="width: 100%;">Kembali</button>
                </a>
            </div>
        </form>
    </div>

</body>
</html>