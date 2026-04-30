

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Netflix Video Player - Modern</title>
    
    <!-- HLS.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    
    <!-- Google Cast SDK -->
    <script src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Netflix Sans', 'Helvetica Neue', Arial, sans-serif;
            background: #000;
            overflow: hidden;
            width: 100vw;
            height: 100vh;
            position: fixed;
        }

        .player-container {
            position: relative;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        video::cue {
            background: transparent;
            color: white;
            text-shadow: 0 0 12px rgba(0,0,0,0.9), 2px 2px 4px rgba(0,0,0,0.8);
            font-size: 1.2em;
            font-weight: 600;
        }

        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70px;
            height: 70px;
            border: 5px solid rgba(255, 255, 255, 0.1);
            border-top-color: #e50914;
            border-radius: 50%;
            animation: spin 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
            z-index: 20;
            display: none;
            box-shadow: 0 0 30px rgba(229, 9, 20, 0.4);
        }

        .loading-spinner.active {
            display: block;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        .skip-intro-btn {
            position: absolute;
            bottom: 130px;
            right: 25px;
            background: rgba(40, 40, 40, 0.85);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            z-index: 15;
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
        }

        .skip-intro-btn.show {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .skip-intro-btn:hover {
            background: rgba(229, 9, 20, 0.9);
            border-color: #e50914;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 12px 32px rgba(229, 9, 20, 0.4);
        }

        .top-controls {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 24px 28px;
            background: linear-gradient(to bottom, 
                rgba(0,0,0,0.95) 0%, 
                rgba(0,0,0,0.8) 40%,
                rgba(0,0,0,0.4) 70%,
                transparent 100%);
            z-index: 10;
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .top-controls.hidden {
            opacity: 0;
            transform: translateY(-20px);
            pointer-events: none;
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-btn {
            background: rgba(40, 40, 40, 0.8);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.15);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .back-btn:hover {
            background: rgba(229, 9, 20, 0.9);
            border-color: #e50914;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.4);
        }

        .back-btn svg {
            width: 24px;
            height: 24px;
            fill: white;
        }

        .title-section {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo-n {
            background: linear-gradient(135deg, #e50914 0%, #b20710 100%);
            color: white;
            font-weight: 900;
            font-size: 26px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            box-shadow: 0 6px 20px rgba(229, 9, 20, 0.5);
            letter-spacing: -2px;
        }

        .episode-title {
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
            letter-spacing: 0.5px;
        }

        .cast-btn {
            background: rgba(40, 40, 40, 0.8);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.15);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .cast-btn:hover {
            background: rgba(60, 60, 60, 0.9);
            transform: scale(1.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .cast-btn.connected {
            background: rgba(0, 150, 255, 0.9);
            border-color: #0096ff;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0, 150, 255, 0.7); }
            50% { box-shadow: 0 0 0 8px rgba(0, 150, 255, 0); }
        }

        .cast-btn svg {
            width: 26px;
            height: 26px;
            fill: white;
        }

        .center-controls {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            gap: 50px;
            z-index: 8;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .center-controls.hidden {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.9);
            pointer-events: none;
        }

        .skip-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
        }

        .skip-circle:hover {
            border-color: #e50914;
            background: rgba(229, 9, 20, 0.2);
            transform: scale(1.15);
            box-shadow: 0 0 30px rgba(229, 9, 20, 0.4);
        }

        .skip-number {
            color: white;
            font-size: 20px;
            font-weight: 900;
        }

        .play-pause-btn {
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .play-pause-btn:hover {
            transform: scale(1.15);
            filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.6));
        }

        .play-pause-btn svg {
            width: 90px;
            height: 90px;
            fill: white;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.5));
        }

        .bottom-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 24px 20px;
            background: linear-gradient(to top, 
                rgba(0,0,0,0.98) 0%, 
                rgba(0,0,0,0.9) 50%,
                rgba(0,0,0,0.5) 80%,
                transparent 100%);
            z-index: 10;
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .bottom-controls.hidden {
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
        }

        .progress-container {
            width: 100%;
            height: 6px;
            background: rgba(128, 128, 128, 0.4);
            border-radius: 3px;
            margin-bottom: 18px;
            position: relative;
            cursor: pointer;
            transition: height 0.2s ease;
        }

        .progress-container:hover {
            height: 8px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #00ff8c 0%, #00ffe0 100%);
            width: 0%;
            border-radius: 3px;
            position: relative;
            box-shadow: 0 0 12px rgba(0, 255, 140, 1) ;
            transition: width 0.1s ease;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.2s ease;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
        }

        .progress-container:hover .progress-bar::after {
            opacity: 1;
        }

        .time-display {
            position: absolute;
            right: 20px;
            bottom: 75px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
            padding: 6px 12px;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 6px;
            backdrop-filter: blur(10px);
        }

        .bottom-menu {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .menu-btn {
            background: rgba(40, 40, 40, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            flex: 1;
            padding: 12px 8px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .menu-btn:hover {
            background: rgba(60, 60, 60, 0.8);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        .menu-btn svg {
            width: 26px;
            height: 26px;
            fill: white;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .menu-btn span {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .modal {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(20, 20, 20, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px 24px 0 0;
            padding: 28px;
            z-index: 100;
            transform: translateY(100%);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            max-height: 75vh;
            overflow-y: auto;
            color: white;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.6);
        }

        .modal.active {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #333;
            padding-bottom: 14px;
        }

        .modal-header h2 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .close-modal-btn {
            background: rgba(60, 60, 60, 0.8);
            border: none;
            color: white;
            font-size: 28px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-modal-btn:hover {
            background: #e50914;
            transform: rotate(90deg);
        }

        .modal-option {
            padding: 16px 18px;
            margin: 8px 0;
            background: rgba(50, 50, 50, 0.6);
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .modal-option:hover {
            background: rgba(70, 70, 70, 0.8);
            transform: translateX(5px);
        }

        .modal-option.active {
            border-color: #e50914;
            background: rgba(229, 9, 20, 0.25);
            box-shadow: 0 0 20px rgba(229, 9, 20, 0.3);
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
            z-index: 99;
            display: none;
            transition: opacity 0.3s ease;
        }

        .overlay.active {
            display: block;
        }

        .episode-item {
            display: flex;
            gap: 12px;
            padding: 16px;
            background: rgba(50, 50, 50, 0.6);
            margin-bottom: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .episode-item:hover {
            background: rgba(70, 70, 70, 0.8);
            transform: translateX(5px);
        }

        .episode-item.active {
            border-left-color: #e50914;
            background: rgba(229, 9, 20, 0.15);
            box-shadow: 0 0 20px rgba(229, 9, 20, 0.2);
        }

        .cast-status {
            position: fixed;
            top: 80px;
            right: 20px;
            background: rgba(0, 150, 255, 0.95);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(0, 150, 255, 0.4);
            pointer-events: none;
        }

        .cast-status.show {
            opacity: 1;
            transform: translateY(0);
        }

        .modal::-webkit-scrollbar {
            width: 8px;
        }

        .modal::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .modal::-webkit-scrollbar-thumb {
            background: rgba(229, 9, 20, 0.6);
            border-radius: 10px;
        }

        .modal::-webkit-scrollbar-thumb:hover {
            background: #e50914;
        }

        @media (max-width: 768px) {
            .center-controls {
                gap: 30px;
            }

            .skip-circle {
                width: 60px;
                height: 60px;
            }

            .play-pause-btn svg {
                width: 70px;
                height: 70px;
            }

            .menu-btn span {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="player-container" id="playerContainer">
        <video id="video" playsinline crossorigin="anonymous"></video>
        <div class="loading-spinner" id="loadingSpinner"></div>
        <button class="skip-intro-btn" id="skipIntroBtn">İntro'yu Atla ⏭</button>

        <div class="top-controls" id="topControls">
            <div class="top-row">
                <button class="back-btn" id="backBtn" title="Geri">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                    </svg>
                </button>
                
                <div class="title-section">
                    <div class="logo-n">N</div>
                    <div class="episode-title" id="currentEpisodeTitle">Yükleniyor...</div>
                </div>

                <button class="cast-btn" id="castBtn" title="TV'ye Yayınla">
                    <svg viewBox="0 0 24 24">
                        <path d="M21,3H3C1.9,3,1,3.9,1,5v3h2V5h18v14h-7v2h7c1.1,0,2-0.9,2-2V5C23,3.9,22.1,3,21,3z M1,18v3h3 C4,19.34,2.66,18,1,18z M1,14v2c2.76,0,5,2.24,5,5h2C8,17.13,4.87,14,1,14z M1,10v2c4.97,0,9,4.03,9,9h2C12,14.92,7.08,10,1,10z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="cast-status" id="castStatus">TV'ye Bağlanıyor...</div>

        <div class="center-controls" id="centerControls">
            <button class="icon-btn" onclick="video.currentTime -= 10" style="background:none; border:none;">
                <div class="skip-circle">
                    <span class="skip-number">10</span>
                </div>
            </button>
            
            <button class="play-pause-btn" id="playPauseBtn">
                <svg id="playIcon" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <svg id="pauseIcon" viewBox="0 0 24 24" style="display:none">
                    <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                </svg>
            </button>
            
            <button class="icon-btn" onclick="video.currentTime += 10" style="background:none; border:none;">
                <div class="skip-circle">
                    <span class="skip-number">10</span>
                </div>
            </button>
        </div>

        <div class="bottom-controls" id="bottomControls">
            <div class="progress-container" id="progCont">
                <div class="progress-bar" id="progBar"></div>
            </div>
            <div class="time-display" id="timeDisp">0:00 / 0:00</div>
            <div class="bottom-menu">
                <button class="menu-btn" id="speedBtn">
                    <svg viewBox="0 0 24 24">
                        <path d="M20.38 8.57l-1.23 1.85a8 8 0 0 1-.22 7.58H5.07A8 8 0 0 1 15.58 6.85l1.85-1.23A10 10 0 0 0 3.35 19a2 2 0 0 0 1.72 1h13.85a2 2 0 0 0 1.74-1 10 10 0 0 0-.27-10.44zm-9.79 6.84a2 2 0 0 0 2.83 0l5.66-8.49-8.49 5.66a2 2 0 0 0 0 2.83z"/>
                    </svg>
                    <span id="speedTxt">Hız (1x)</span>
                </button>
                
                <button class="menu-btn" id="episodesBtn">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                    </svg>
                    <span>Bölümler</span>
                </button>
                
                <button class="menu-btn" id="audioBtn">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2z"/>
                    </svg>
                    <span>Altyazı</span>
                </button>
                
                <button class="menu-btn" id="fullscreenBtn">
                    <svg viewBox="0 0 24 24">
                        <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
                    </svg>
                    <span>Tam Ekran</span>
                </button>
                
                <button class="menu-btn" id="nextBtn">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/>
                    </svg>
                    <span>Sonraki</span>
                </button>
            </div>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    <div class="modal" id="genericModal">
        <div class="modal-header">
            <h2 id="modalTitle">Başlık</h2>
            <button class="close-modal-btn" onclick="closeModals()">×</button>
        </div>
        <div id="modalContent"></div>
    </div>

    <script>
        const video = document.getElementById('video');
        const hls = new Hls();
        const skipIntroBtn = document.getElementById('skipIntroBtn');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const progBar = document.getElementById('progBar');
        const progCont = document.getElementById('progCont');
        const timeDisp = document.getElementById('timeDisp');
        const playIcon = document.getElementById('playIcon');
        const pauseIcon = document.getElementById('pauseIcon');
        const topControls = document.getElementById('topControls');
        const bottomControls = document.getElementById('bottomControls');
        const centerControls = document.getElementById('centerControls');
        const playerContainer = document.getElementById('playerContainer');
        const castBtn = document.getElementById('castBtn');
        const castStatus = document.getElementById('castStatus');
        const backBtn = document.getElementById('backBtn');
        
        let currentEp = 0;
        let currentSpeed = 1;
        let controlsTimeout;
        let castSession = null;
        let isCasting = false;

        const episodes = [
            { n: 1, t: "Bölüm 1", url: "https://s231.rapidrame.com/hls2/01/00021/83n2umwfezgd_,l,n,.urlset/master.m3u8?t=H2xWxm-CDuKGAi6EWUyQJJnlnWAwR4nyRm8eO4jXu1M&s=1773833884&e=86400&f=105631&srv=s429&i=0.0&sp=0&p1=s231&p2=s231", sub: "https://vs12.photoflix.org/v/Prison.Break.S01E01.Bluray.1080p.DUAL.x264-HDM/subtitle-tur-1.vtt" },
            { n: 2, t: "Bölüm 2", url: "https://s231.rapidrame.com/hls2/01/00021/498on11vpvxl_,l,n,.urlset/index-f2-v1-a2.m3u8?t=3PXhA1utpY3f1LJCSACv8A53Ossd9Fwxqk3vK6UywXE&s=1773833930&e=86400&f=105632&srv=s429&i=0.0&sp=0&p1=s231&p2=s231", sub: "https://vs10.photofunny.org/v/Prison.Break.S01E02.Bluray.1080p.DUAL.x264-HDM/subtitle-eng-3.vtt" },
            { n: 3, t: "Bölüm 3", url: "https://s231.rapidrame.com/hls2/01/00021/4700ruhkec2j_,l,n,.urlset/index-f2-v1-a2.m3u8?t=7TUk3EkRyPIBtntvyBH8O_E-L2Ag-WCeINCQxf1fWVQ&s=1773833946&e=86400&f=105633&srv=s427&i=0.0&sp=0&p1=s231&p2=s231", sub: "https://vs16.photostack.net/v/Prison.Break.S01E03.Bluray.1080p.DUAL.x264-HDM/subtitle-tur-1.vtt" },
            { n: 4, t: "Bölüm 4", url: "https://s427.rapidrame.com/hls2/01/00021/js2lpc26t7lm_,l,n,.urlset/index-f2-v1-a2.m3u8?t=OBq8mQM3Y3Vzyihep7mpVEyNMy_8RfJRoB9Owp5SEBU&s=1773833976&e=86400&f=105635&i=0.0&sp=0", sub: "https://vs12.photour.org/v/Prison.Break.S01E04.Bluray.1080p.DUAL.x264-HDM/subtitle-tur-1.vtt" },
            { n: 5,t: "Bölüm5", url: "https://s427.rapidrame.com/hls2/01/00021/js2lpc26t7lm_,l,n,.urlset/index-f2-v1-a2.m3u8?t=OBq8mQM3Y3Vzyihep7mpVEyNMy_8RfJRoB9Owp5SEBU&s=1773833976&e=86400&f=105635&i=0.0&sp=0", sub: "https://vs10.photomag.biz/v/Prison.Break.S01E05.Bluray.1080p.DUAL.x264-HDM/subtitle-tur-1.vtt" },
            { n: 6,t: "Bölüm5", url: "https://s427.rapidrame.com/hls2/01/00021/mm9sjyjrr4wa_,l,n,.urlset/index-f2-v1-a2.m3u8?t=hAeCeo4zrPfNoPPBd28tidt6S0Ue1CnVIn_s-WxLZqg&s=1773833995&e=86400&f=105634&i=0.0&sp=0", sub: "https://vs10.photomag.biz/v/Prison.Break.S01E05.Bluray.1080p.DUAL.x264-HDM/subtitle-tur-1.vtt" },
            { n: 7,t: "Bölüm5", url: "https://s427.rapidrame.com/hls2/01/00021/kqf80yayz7v8_,l,n,.urlset/index-f2-v1-a2.m3u8?t=Mqaql4lgbEeTUIkiUke1ayM_ovn20Pn1Pt6yk7elKQ0&s=1773834073&e=86400&f=105637&i=0.0&sp=0", sub: "https://vs10.photomag.biz/v/Prison.Break.S01E05.Bluray.1080p.DUAL.x264-HDM/subtitle-tur-1.vtt" },
        ];

        backBtn.addEventListener('click', () => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        });

        window['__onGCastApiAvailable'] = function(isAvailable) {
            if (isAvailable) {
                initializeCastApi();
            }
        };

        function initializeCastApi() {
            cast.framework.CastContext.getInstance().setOptions({
                receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
                autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED
            });

            const castContext = cast.framework.CastContext.getInstance();
            
            castContext.addEventListener(
                cast.framework.CastContextEventType.SESSION_STATE_CHANGED,
                (event) => {
                    switch (event.sessionState) {
                        case cast.framework.SessionState.SESSION_STARTED:
                        case cast.framework.SessionState.SESSION_RESUMED:
                            castSession = event.session;
                            onCastConnected();
                            break;
                        case cast.framework.SessionState.SESSION_ENDED:
                            castSession = null;
                            onCastDisconnected();
                            break;
                    }
                }
            );
        }

        castBtn.addEventListener('click', () => {
            const castContext = cast.framework.CastContext.getInstance();
            
            if (castSession) {
                castContext.endCurrentSession(true);
            } else {
                castContext.requestSession().then(
                    () => {
                        showCastStatus('TV\'ye Bağlandı! 📺');
                    },
                    (error) => {
                        if (error !== 'cancel') {
                            showCastStatus('Bağlantı başarısız 😞');
                        }
                    }
                );
            }
        });

        function onCastConnected() {
            isCasting = true;
            castBtn.classList.add('connected');
            showCastStatus('TV\'ye Yayınlanıyor 📺');
            loadMediaToCast();
        }

        function onCastDisconnected() {
            isCasting = false;
            castBtn.classList.remove('connected');
            showCastStatus('TV Bağlantısı Kesildi');
        }

        function loadMediaToCast() {
            if (!castSession) return;

            const ep = episodes[currentEp];
            const mediaInfo = new chrome.cast.media.MediaInfo(ep.url, 'application/x-mpegURL');
            
            mediaInfo.metadata = new chrome.cast.media.GenericMediaMetadata();
            mediaInfo.metadata.title = ep.t;
            mediaInfo.metadata.subtitle = "Squid Game Sezon 2";
            
            if (ep.sub) {
                const track = new chrome.cast.media.Track(1, chrome.cast.media.TrackType.TEXT);
                track.trackContentId = ep.sub;
                track.trackContentType = 'text/vtt';
                track.subtype = chrome.cast.media.TextTrackType.SUBTITLES;
                track.name = 'Türkçe';
                track.language = 'tr';
                mediaInfo.tracks = [track];
            }

            const request = new chrome.cast.media.LoadRequest(mediaInfo);
            request.currentTime = video.currentTime;
            request.autoplay = !video.paused;

            castSession.loadMedia(request).then(
                () => {
                    console.log('Media loaded to Chromecast');
                    video.pause();
                },
                (error) => {
                    console.error('Error loading media', error);
                }
            );
        }

        function showCastStatus(message) {
            castStatus.textContent = message;
            castStatus.classList.add('show');
            setTimeout(() => {
                castStatus.classList.remove('show');
            }, 3000);
        }

        function loadVideo(idx) {
            currentEp = idx;
            const ep = episodes[idx];
            loadingSpinner.classList.add('active');
            
            const tracks = video.querySelectorAll('track');
            tracks.forEach(t => t.remove());

            if(ep.sub) {
                const track = document.createElement('track');
                track.kind = "subtitles";
                track.label = "Türkçe";
                track.srclang = "tr";
                track.src = ep.sub;
                track.default = true;
                video.appendChild(track);
                
                track.addEventListener('load', function() {
                    this.mode = 'showing';
                });
            }

            if (Hls.isSupported()) {
                hls.detachMedia();
                hls.loadSource(ep.url);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    loadingSpinner.classList.remove('active');
                    video.playbackRate = currentSpeed;
                    video.play();
                });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = ep.url;
                video.addEventListener('loadedmetadata', () => {
                    loadingSpinner.classList.remove('active');
                    video.playbackRate = currentSpeed;
                    video.play();
                });
            }

            document.getElementById('currentEpisodeTitle').innerText = ep.t;

            if (isCasting && castSession) {
                loadMediaToCast();
            }
        }

        function showControls() {
            topControls.classList.remove('hidden');
            bottomControls.classList.remove('hidden');
            centerControls.classList.remove('hidden');
            playerContainer.style.cursor = 'default';
            
            clearTimeout(controlsTimeout);
            
            if (!video.paused) {
                controlsTimeout = setTimeout(() => {
                    hideControls();
                }, 3000);
            }
        }

        function hideControls() {
            if (!video.paused) {
                topControls.classList.add('hidden');
                bottomControls.classList.add('hidden');
                centerControls.classList.add('hidden');
                playerContainer.style.cursor = 'none';
            }
        }

        playerContainer.addEventListener('mousemove', showControls);
        playerContainer.addEventListener('touchstart', showControls);
        playerContainer.addEventListener('click', (e) => {
            if (e.target === playerContainer || e.target === video) {
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
            }
        });

        skipIntroBtn.onclick = () => {
            video.currentTime += 90;
            skipIntroBtn.classList.remove('show');
        };

        video.ontimeupdate = () => {
            const p = (video.currentTime / video.duration) * 100;
            progBar.style.width = p + '%';
            timeDisp.innerText = formatTime(video.currentTime) + " / " + formatTime(video.duration);

            if(video.currentTime >= 20 && video.currentTime <= 110) {
                skipIntroBtn.classList.add('show');
            } else {
                skipIntroBtn.classList.remove('show');
            }
        };

        video.onplay = () => {
            playIcon.style.display = 'none';
            pauseIcon.style.display = 'block';
        };

        video.onpause = () => {
            playIcon.style.display = 'block';
            pauseIcon.style.display = 'none';
        };

        progCont.addEventListener('click', (e) => {
            const rect = progCont.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            video.currentTime = percent * video.duration;
        });

        document.getElementById('playPauseBtn').onclick = () => {
            if(video.paused) {
                video.play();
            } else {
                video.pause();
            }
        };

        document.getElementById('speedBtn').onclick = () => {
            const content = document.getElementById('modalContent');
            document.getElementById('modalTitle').innerText = "Oynatma Hızı";
            content.innerHTML = '';
            
            [0.5, 0.75, 1, 1.25, 1.5, 2].forEach(s => {
                const div = document.createElement('div');
                div.className = `modal-option ${s === currentSpeed ? 'active' : ''}`;
                div.innerHTML = `<span>${s}x</span>`;
                div.onclick = () => {
                    currentSpeed = s;
                    video.playbackRate = s;
                    document.getElementById('speedTxt').innerText = `Hız (${s}x)`;
                    closeModals();
                };
                content.appendChild(div);
            });
            showModal();
        };

        document.getElementById('episodesBtn').onclick = () => {
            const content = document.getElementById('modalContent');
            document.getElementById('modalTitle').innerText = "Bölümler";
            content.innerHTML = '';
            
            episodes.forEach((ep, i) => {
                const div = document.createElement('div');
                div.className = `episode-item ${i === currentEp ? 'active' : ''}`;
                div.innerHTML = `<span style="font-weight: 700;">${ep.t}</span>`;
                div.onclick = () => {
                    loadVideo(i);
                    closeModals();
                };
                content.appendChild(div);
            });
            showModal();
        };

        document.getElementById('audioBtn').onclick = () => {
            const content = document.getElementById('modalContent');
            document.getElementById('modalTitle').innerText = "Altyazı Seçenekleri";
            content.innerHTML = '';

            const offDiv = document.createElement('div');
            offDiv.className = 'modal-option';
            const allOff = Array.from(video.textTracks).every(t => t.mode !== 'showing');
            if (allOff) offDiv.classList.add('active');
            offDiv.innerHTML = '<span>Altyazı Kapalı</span>';
            offDiv.onclick = () => {
                for (let i = 0; i < video.textTracks.length; i++) {
                    video.textTracks[i].mode = 'disabled';
                }
                closeModals();
            };
            content.appendChild(offDiv);

            for (let i = 0; i < video.textTracks.length; i++) {
                const track = video.textTracks[i];
                const div = document.createElement('div');
                div.className = `modal-option ${track.mode === 'showing' ? 'active' : ''}`;
                div.innerHTML = `<span>${track.label || 'Türkçe Altyazı'}</span>`;
                div.onclick = () => {
                    for (let j = 0; j < video.textTracks.length; j++) {
                        video.textTracks[j].mode = 'disabled';
                    }
                    track.mode = 'showing';
                    closeModals();
                };
                content.appendChild(div);
            }
            showModal();
        };

        document.getElementById('fullscreenBtn').onclick = () => {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                playerContainer.requestFullscreen();
            }
        };

        document.getElementById('nextBtn').onclick = () => {
            if(currentEp < episodes.length - 1) {
                loadVideo(currentEp + 1);
            }
        };

        function showModal() {
            document.getElementById('genericModal').classList.add('active');
            document.getElementById('overlay').classList.add('active');
        }

        function closeModals() {
            document.getElementById('genericModal').classList.remove('active');
            document.getElementById('overlay').classList.remove('active');
        }

        document.getElementById('overlay').onclick = closeModals;

        document.addEventListener('keydown', (e) => {
            switch(e.key) {
                case ' ':
                    e.preventDefault();
                    video.paused ? video.play() : video.pause();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    video.currentTime -= 10;
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    video.currentTime += 10;
                    break;
                case 'f':
                case 'F':
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    } else {
                        playerContainer.requestFullscreen();
                    }
                    break;
            }
        });

        function formatTime(s) {
            if(isNaN(s)) return "0:00";
            const hours = Math.floor(s / 3600);
            const minutes = Math.floor((s % 3600) / 60);
            const seconds = Math.floor(s % 60);
            
            if (hours > 0) {
                return `${hours}:${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            }
            return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        }

        loadVideo(0);
        showControls();
    </script>
</body>
</html>