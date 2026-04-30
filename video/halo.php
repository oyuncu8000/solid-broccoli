<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bölüm Seçici - Tam Ekran</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            background-color: #000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #content-frame {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        .menu-container {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 9999;
        }

        #toggle-btn {
            padding: 12px 20px;
            background-color: rgba(0, 0, 0, 0.85);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }

        #toggle-btn:hover {
            background-color: #000;
            border-color: #fff;
        }

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

        .show { display: block; }
    </style>
</head>
<body>
        
    
    <div class="menu-container">
        <button id="toggle-btn" onclick="menuAc(event)">☰ Bölüm Seçin</button>
        <div id="myDropdown" class="dropdown-content">
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#njwyf', 'Bölüm 1')">Bölüm 1</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#odu1d', 'Bölüm 2')">Bölüm 2</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#kwfaf', 'Bölüm 3')">Bölüm 3</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#yz1jo', 'Bölüm 4')">Bölüm 4</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#rbbpz', 'Bölüm 5')">Bölüm 5</a>
            <a href="javascript:void(0)" onclick="siteDegistir('https://cloudpro01.player4me.xyz/#rbbpz', 'Bölüm 6')">Bölüm 6</a>
        </div>
    </div>

    <iframe id="content-frame" src="https://cloudpro01.player4me.xyz/#njwyf" allowfullscreen></iframe>

    <script>
        function menuAc(event) {
            event.stopPropagation(); // Tıklamanın dışarı yayılmasını engelle
            document.getElementById("myDropdown").classList.toggle("show");
        }

        function siteDegistir(url, isim) {
            const frame = document.getElementById('content-frame');
            const btn = document.getElementById('toggle-btn');
            
            // 1. Önce iframe'i temizle veya boş bir yere yönlendir (bazı tarayıcılar için zorunlu)
            frame.src = "about:blank"; 
            
            // 2. Kısa bir gecikmeyle yeni URL'yi yükle (DOM'un yenilenmesi için)
            setTimeout(() => {
                frame.src = url;
                btn.innerHTML = "☰ " + isim;
            }, 50);
            
            document.getElementById("myDropdown").classList.remove("show");
        }

        // Menü dışına tıklandığında kapatma
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

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3529497865257703"
     crossorigin="anonymous"></script>
<!-- deneme -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-3529497865257703"
     data-ad-slot="9765453842"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
   
</body>
</html>