<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bölüm Seçici - Kesin Çözüm</title>
    <style>
        body, html {
            margin: 0; padding: 0;
            height: 100%; width: 100%;
            overflow: hidden;
            font-family: 'Segoe UI', sans-serif;
            background-color: #000;
        }

        /* Iframe alanı */
        #iframe-container {
            width: 100%;
            height: 100%;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Menü Stilleri */
        .menu-container {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 9999;
        }

        #toggle-btn {
            padding: 12px 20px;
            background-color: rgba(0, 0, 0, 0.9);
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 50px;
            left: 0;
            background-color: #1a1a1a;
            min-width: 180px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }

        .dropdown-content a {
            color: white;
            padding: 12px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #333;
        }

        .dropdown-content a:hover { background-color: #333; }
        .show { display: block; }
    </style>
</head>
<body>

    <div class="menu-container">
        <button id="toggle-btn" onclick="menuAc()">☰ Bölüm Seçin</button>
        <div id="myDropdown" class="dropdown-content">
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#n1puq', 'Bölüm 1')">Bölüm 1</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#8ecm8', 'Bölüm 2')">Bölüm 2</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#6rlrh', 'Bölüm 3')">Bölüm 3</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#hi3do', 'Bölüm 4')">Bölüm 4</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#spuae', 'Bölüm 5')">Bölüm 5</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#c5tjh', 'Bölüm 6')">Bölüm 6</a>
        </div>
    </div>

    <div id="iframe-container">
        <iframe id="content-frame" src="https://cloudpro01.player4me.xyz/#n1puq" allowfullscreen></iframe>
    </div>

    <script>
        function menuAc() {
            document.getElementById("myDropdown").classList.toggle("show");
        }

        function siteDegistir(url, isim) {
            const container = document.getElementById('iframe-container');
            const btn = document.getElementById('toggle-btn');
            
            // 1. Önce mevcut iframe'i siliyoruz (belleği boşaltır)
            container.innerHTML = '';
            
            // 2. Yeni bir iframe elementi oluşturuyoruz
            const newIframe = document.createElement('iframe');
            newIframe.id = 'content-frame';
            newIframe.allowFullscreen = true;
            
            // 3. Önce URL'yi atayıp sonra DOM'a ekliyoruz (en hızlı yükleme yöntemi)
            newIframe.src = url;
            container.appendChild(newIframe);
            
            // Arayüz güncellemeleri
            btn.innerText = "☰ " + isim;
            document.getElementById("myDropdown").classList.remove("show");
        }

        // Dışarı tıklayınca menü kapanması
        window.onclick = function(event) {
            if (!event.target.matches('#toggle-btn')) {
                document.getElementById("myDropdown").classList.remove("show");
            }
        }
    </script>

</body>
</html>