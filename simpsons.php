
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bölüm Seçici - Tam Ekran</title>
    <style>
        /* Sayfa ve Iframe Sıfırlama */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #content-frame {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        /* Menü Konumlandırma */
        .menu-container {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 9999;
        }

        /* Ana Buton Stili */
        #toggle-btn {
            padding: 12px 20px;
            background-color: rgba(0, 0, 0, 0.85);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        #toggle-btn:hover {
            background-color: #000;
            border-color: #fff;
        }

        /* Açılır Menü Listesi */
        .dropdown-content {
            display: none;
            position: absolute;
            top: 50px;
            left: 0;
            background-color: #1a1a1a;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.5);
            border-radius: 5px;
            overflow: hidden;
        }

        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #333;
            font-size: 14px;
        }

        .dropdown-content a:hover {
            background-color: #333;
        }

        /* Menüyü Göster */
        .show { display: block; }
    </style>
</head>
<body>

    <div class="menu-container">
        <button id="toggle-btn" onclick="menuAc()">☰ Bölüm Seçin</button>
        <div id="myDropdown" class="dropdown-content">
            <a href="#" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#ixei3', 'Bölüm 1')">Bölüm 1</a>
            <a href="#" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#sho85', 'Bölüm 2')">Bölüm 2</a>
            <a href="#" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#zvbje', 'Bölüm 3')">Bölüm 3</a>
            <a href="#" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#shifq', 'Sunucu 1')">Bölüm 4</a>
            <a href="#" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#spuae', 'Sunucu 2')">Bölüm 5</a>
            <a href="#" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#c5tjh', 'Bing')">Bölüm 6</a>
        </div>
    </div>

    <iframe id="content-frame" src="https://cloudpro01.player4me.xyz/#ixei3" allowfullscreen></iframe>

    <script>
        // Menüyü açıp kapatan fonksiyon
        function menuAc() {
            document.getElementById("myDropdown").classList.toggle("show");
        }

        // Siteyi değiştiren fonksiyon
        function siteDegistir(url, isim) {
            const frame = document.getElementById('content-frame');
            const btn = document.getElementById('toggle-btn');
            
            frame.src = url; // Iframe'i güncelle
            btn.innerText = "☰ " + isim; // Buton ismini güncelle
            
            // Menüyü kapat
            document.getElementById("myDropdown").classList.remove("show");
        }

        // Menü dışına tıklandığında menüyü kapat
        window.onclick = function(event) {
            if (!event.target.matches('#toggle-btn')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>

</body>
</html>